<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;
use Throwable;

final class PharmacyRepository
{
    private ?bool $articlesTableExists = null;
    private ?bool $movementsTableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasArticlesTable() && $this->hasMovementsTable();
    }

    public function findAllArticles(bool $activeOnly = false): array
    {
        if (!$this->hasArticlesTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                id,
                nom,
                unite,
                stock_actuel,
                seuil_alerte,
                actif
            FROM pharmacie_articles
            ' . ($activeOnly ? 'WHERE actif = 1' : '') . '
            ORDER BY nom ASC
        ';

        $statement = $connection->query($sql);

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveArticle(
        int $id,
        string $name,
        string $unit,
        float $stock,
        ?float $alertThreshold,
        bool $active
    ): bool {
        if (!$this->hasArticlesTable()) {
            return false;
        }

        $connection = Database::getConnection();

        if ($id > 0) {
            $sql = '
                UPDATE pharmacie_articles
                SET
                    nom = :nom,
                    unite = :unite,
                    stock_actuel = :stock_actuel,
                    seuil_alerte = :seuil_alerte,
                    actif = :actif
                WHERE id = :id
            ';
            $statement = $connection->prepare($sql);

            return $statement->execute([
                'id' => $id,
                'nom' => $name,
                'unite' => $unit,
                'stock_actuel' => $stock,
                'seuil_alerte' => $alertThreshold,
                'actif' => $active ? 1 : 0,
            ]);
        }

        $sql = '
            INSERT INTO pharmacie_articles (nom, unite, stock_actuel, seuil_alerte, actif)
            VALUES (:nom, :unite, :stock_actuel, :seuil_alerte, :actif)
        ';
        $statement = $connection->prepare($sql);

        return $statement->execute([
            'nom' => $name,
            'unite' => $unit,
            'stock_actuel' => $stock,
            'seuil_alerte' => $alertThreshold,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function recordOutput(int $articleId, float $quantity, string $declarant, ?string $comment): bool
    {
        return $this->recordOutputs([
            [
                'article_id' => $articleId,
                'quantite' => $quantity,
                'commentaire' => $comment,
            ],
        ], $declarant);
    }

    public function recordOutputs(array $lines, string $declarant): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($lines === []) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $stockStatement = $connection->prepare(
                'SELECT stock_actuel, actif FROM pharmacie_articles WHERE id = :id FOR UPDATE'
            );
            $updateStatement = $connection->prepare(
                'UPDATE pharmacie_articles SET stock_actuel = stock_actuel - :quantity WHERE id = :id'
            );
            $insertStatement = $connection->prepare(
                'INSERT INTO pharmacie_mouvements (article_id, type, quantite, commentaire, declarant)
                 VALUES (:article_id, \'sortie\', :quantite, :commentaire, :declarant)'
            );

            foreach ($lines as $line) {
                $articleId = isset($line['article_id']) ? (int) $line['article_id'] : 0;
                $quantity = isset($line['quantite']) ? (float) $line['quantite'] : 0.0;
                $comment = isset($line['commentaire']) ? trim((string) $line['commentaire']) : '';

                if ($articleId <= 0 || $quantity <= 0) {
                    $connection->rollBack();
                    return false;
                }

                $stockStatement->execute(['id' => $articleId]);
                $article = $stockStatement->fetch(PDO::FETCH_ASSOC);

                if ($article === false || (int) ($article['actif'] ?? 0) !== 1) {
                    $connection->rollBack();
                    return false;
                }

                $currentStock = (float) ($article['stock_actuel'] ?? 0);
                if ($currentStock < $quantity) {
                    $connection->rollBack();
                    return false;
                }

                $updateStatement->execute([
                    'quantity' => $quantity,
                    'id' => $articleId,
                ]);

                $insertStatement->execute([
                    'article_id' => $articleId,
                    'quantite' => $quantity,
                    'commentaire' => $comment === '' ? null : $comment,
                    'declarant' => $declarant === '' ? null : $declarant,
                ]);
            }

            $connection->commit();
            return true;
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            return false;
        }
    }

    public function getStats(): array
    {
        if (!$this->hasArticlesTable()) {
            return [
                'total_articles' => 0,
                'alert_articles' => 0,
                'outputs_last_7_days' => 0,
            ];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                COUNT(*) AS total_articles,
                SUM(
                    CASE
                        WHEN seuil_alerte IS NOT NULL AND stock_actuel <= seuil_alerte THEN 1
                        ELSE 0
                    END
                ) AS alert_articles
            FROM pharmacie_articles
            WHERE actif = 1
        ';
        $statement = $connection->query($sql);
        $row = $statement === false ? null : $statement->fetch(PDO::FETCH_ASSOC);

        $outputsLast7Days = 0;
        if ($this->hasMovementsTable()) {
            $movementSql = '
                SELECT COUNT(*) AS total
                FROM pharmacie_mouvements
                WHERE type = \'sortie\'
                  AND cree_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ';
            $movementStatement = $connection->query($movementSql);
            $movementRow = $movementStatement === false ? null : $movementStatement->fetch(PDO::FETCH_ASSOC);
            $outputsLast7Days = (int) ($movementRow['total'] ?? 0);
        }

        return [
            'total_articles' => (int) ($row['total_articles'] ?? 0),
            'alert_articles' => (int) ($row['alert_articles'] ?? 0),
            'outputs_last_7_days' => $outputsLast7Days,
        ];
    }

    public function findLastMovements(int $limit = 100): array
    {
        if (!$this->hasMovementsTable()) {
            return [];
        }

        $limit = max(1, min(300, $limit));
        $connection = Database::getConnection();
        $sql = '
            SELECT
                m.id,
                m.article_id,
                m.type,
                m.quantite,
                m.commentaire,
                m.declarant,
                m.cree_le,
                a.nom AS article_nom,
                a.unite AS article_unite
            FROM pharmacie_mouvements m
            INNER JOIN pharmacie_articles a ON a.id = m.article_id
            ORDER BY m.cree_le DESC, m.id DESC
            LIMIT ' . $limit;

        $statement = $connection->query($sql);

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasArticlesTable(): bool
    {
        if ($this->articlesTableExists !== null) {
            return $this->articlesTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'pharmacie_articles'");
            $this->articlesTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->articlesTableExists = false;
        }

        return $this->articlesTableExists;
    }

    private function hasMovementsTable(): bool
    {
        if ($this->movementsTableExists !== null) {
            return $this->movementsTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'pharmacie_mouvements'");
            $this->movementsTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->movementsTableExists = false;
        }

        return $this->movementsTableExists;
    }
}
