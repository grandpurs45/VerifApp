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
    private ?bool $outputGroupColumnExists = null;

    public function isAvailable(): bool
    {
        return $this->hasArticlesTable() && $this->hasMovementsTable();
    }

    public function findAllArticles(int $caserneId, bool $activeOnly = false): array
    {
        if (!$this->hasArticlesTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                id,
                caserne_id,
                nom,
                unite,
                stock_actuel,
                seuil_alerte,
                actif
            FROM pharmacie_articles
            WHERE caserne_id = :caserne_id
            ' . ($activeOnly ? 'AND actif = 1' : '') . '
            ORDER BY nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['caserne_id' => $caserneId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveArticle(
        int $caserneId,
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
                  AND caserne_id = :caserne_id
            ';
            $statement = $connection->prepare($sql);

            return $statement->execute([
                'id' => $id,
                'caserne_id' => $caserneId,
                'nom' => $name,
                'unite' => $unit,
                'stock_actuel' => $stock,
                'seuil_alerte' => $alertThreshold,
                'actif' => $active ? 1 : 0,
            ]);
        }

        $sql = '
            INSERT INTO pharmacie_articles (caserne_id, nom, unite, stock_actuel, seuil_alerte, actif)
            VALUES (:caserne_id, :nom, :unite, :stock_actuel, :seuil_alerte, :actif)
        ';
        $statement = $connection->prepare($sql);

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
            'unite' => $unit,
            'stock_actuel' => $stock,
            'seuil_alerte' => $alertThreshold,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function recordOutput(int $caserneId, int $articleId, float $quantity, string $declarant, ?string $comment): bool
    {
        return $this->recordOutputs($caserneId, [
            [
                'article_id' => $articleId,
                'quantite' => $quantity,
                'commentaire' => $comment,
            ],
        ], $declarant);
    }

    public function recordOutputs(int $caserneId, array $lines, string $declarant): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $declarant = trim($declarant);
        if ($lines === [] || $declarant === '') {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();
            $sortieRef = $this->generateOutputReference();

            $stockStatement = $connection->prepare(
                'SELECT stock_actuel, actif FROM pharmacie_articles WHERE id = :id AND caserne_id = :caserne_id FOR UPDATE'
            );
            $updateStatement = $connection->prepare(
                'UPDATE pharmacie_articles SET stock_actuel = stock_actuel - :quantity WHERE id = :id AND caserne_id = :caserne_id'
            );
            if ($this->hasOutputGroupColumn()) {
                $insertStatement = $connection->prepare(
                    'INSERT INTO pharmacie_mouvements (caserne_id, article_id, type, sortie_ref, quantite, commentaire, declarant)
                     VALUES (:caserne_id, :article_id, \'sortie\', :sortie_ref, :quantite, :commentaire, :declarant)'
                );
            } else {
                $insertStatement = $connection->prepare(
                    'INSERT INTO pharmacie_mouvements (caserne_id, article_id, type, quantite, commentaire, declarant)
                     VALUES (:caserne_id, :article_id, \'sortie\', :quantite, :commentaire, :declarant)'
                );
            }

            $hasOutputGroupColumn = $this->hasOutputGroupColumn();

            foreach ($lines as $line) {
                $articleId = isset($line['article_id']) ? (int) $line['article_id'] : 0;
                $quantity = isset($line['quantite']) ? (float) $line['quantite'] : 0.0;
                $comment = isset($line['commentaire']) ? trim((string) $line['commentaire']) : '';
                $isIntegerQuantity = abs($quantity - round($quantity)) < 0.00001;

                if ($articleId <= 0 || $quantity <= 0 || !$isIntegerQuantity) {
                    $connection->rollBack();
                    return false;
                }

                $stockStatement->execute([
                    'id' => $articleId,
                    'caserne_id' => $caserneId,
                ]);
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
                    'caserne_id' => $caserneId,
                ]);

                $insertParams = [
                    'caserne_id' => $caserneId,
                    'article_id' => $articleId,
                    'quantite' => $quantity,
                    'commentaire' => $comment === '' ? null : $comment,
                    'declarant' => $declarant,
                ];
                if ($hasOutputGroupColumn) {
                    $insertParams['sortie_ref'] = $sortieRef;
                }
                $insertStatement->execute($insertParams);
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

    public function getStats(int $caserneId): array
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
                        WHEN actif = 1
                             AND seuil_alerte IS NOT NULL
                             AND seuil_alerte > 0
                             AND stock_actuel < seuil_alerte THEN 1
                        ELSE 0
                    END
                ) AS alert_articles
            FROM pharmacie_articles
            WHERE actif = 1
              AND caserne_id = :caserne_id
        ';
        $statement = $connection->prepare($sql);
        $statement->execute(['caserne_id' => $caserneId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $outputsLast7Days = 0;
        if ($this->hasMovementsTable()) {
            if ($this->hasOutputGroupColumn()) {
                $movementSql = '
                    SELECT COUNT(DISTINCT COALESCE(NULLIF(sortie_ref, \'\'), CONCAT(\'legacy:\', id))) AS total
                    FROM pharmacie_mouvements
                    WHERE type = \'sortie\'
                      AND caserne_id = :caserne_id
                      AND cree_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ';
            } else {
                $movementSql = '
                    SELECT COUNT(*) AS total
                    FROM pharmacie_mouvements
                    WHERE type = \'sortie\'
                      AND caserne_id = :caserne_id
                      AND cree_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ';
            }
            $movementStatement = $connection->prepare($movementSql);
            $movementStatement->execute(['caserne_id' => $caserneId]);
            $movementRow = $movementStatement->fetch(PDO::FETCH_ASSOC);
            $outputsLast7Days = (int) ($movementRow['total'] ?? 0);
        }

        return [
            'total_articles' => (int) ($row['total_articles'] ?? 0),
            'alert_articles' => (int) ($row['alert_articles'] ?? 0),
            'outputs_last_7_days' => $outputsLast7Days,
        ];
    }

    public function findLastMovements(int $caserneId, int $limit = 100): array
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
                m.caserne_id,
                m.type,
                m.quantite,
                m.commentaire,
                m.declarant,
                m.cree_le,
                a.nom AS article_nom,
                a.unite AS article_unite
            FROM pharmacie_mouvements m
            INNER JOIN pharmacie_articles a ON a.id = m.article_id
            WHERE m.caserne_id = :caserne_id
              AND m.type = \'sortie\'
            ORDER BY m.cree_le DESC, m.id DESC
            LIMIT ' . $limit;

        $statement = $connection->prepare($sql);
        $statement->execute(['caserne_id' => $caserneId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLastOutputGroups(int $caserneId, int $limitGroups = 40): array
    {
        return $this->findOutputGroups($caserneId, [], $limitGroups);
    }

    public function findOutputGroups(int $caserneId, array $filters, int $limitGroups = 40): array
    {
        if (!$this->hasMovementsTable()) {
            return [];
        }

        $limitGroups = max(1, min(100, $limitGroups));
        $connection = Database::getConnection();
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $articleSearch = trim((string) ($filters['article'] ?? ''));
        $declarantSearch = trim((string) ($filters['declarant'] ?? ''));

        if ($this->hasOutputGroupColumn()) {
            $where = [
                'm.caserne_id = :caserne_id',
                'm.type = \'sortie\'',
            ];
            $params = ['caserne_id' => $caserneId];

            if ($dateFrom !== '') {
                $where[] = 'DATE(m.cree_le) >= :date_from';
                $params['date_from'] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = 'DATE(m.cree_le) <= :date_to';
                $params['date_to'] = $dateTo;
            }
            if ($declarantSearch !== '') {
                $where[] = 'm.declarant LIKE :declarant';
                $params['declarant'] = '%' . $declarantSearch . '%';
            }
            if ($articleSearch !== '') {
                $where[] = '
                    EXISTS (
                        SELECT 1
                        FROM pharmacie_mouvements mx
                        INNER JOIN pharmacie_articles ax ON ax.id = mx.article_id
                        WHERE mx.caserne_id = m.caserne_id
                          AND mx.type = \'sortie\'
                          AND COALESCE(NULLIF(mx.sortie_ref, \'\'), CONCAT(\'legacy:\', mx.id))
                              = COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id))
                          AND ax.nom LIKE :article
                    )
                ';
                $params['article'] = '%' . $articleSearch . '%';
            }

            $sql = '
                SELECT
                    COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id)) AS sortie_key,
                    MAX(m.cree_le) AS cree_le,
                    MAX(COALESCE(m.declarant, \'\')) AS declarant,
                    COUNT(*) AS lignes,
                    SUM(m.quantite) AS total_quantite
                FROM pharmacie_mouvements m
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id))
                ORDER BY MAX(m.cree_le) DESC
                LIMIT ' . $limitGroups;
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            $groups = $statement->fetchAll(PDO::FETCH_ASSOC);

            if ($groups === []) {
                return [];
            }

            $keys = array_map(
                static fn(array $group): string => (string) ($group['sortie_key'] ?? ''),
                $groups
            );
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $linesSql = '
                SELECT
                    COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id)) AS sortie_key,
                    m.id,
                    m.article_id,
                    m.quantite,
                    m.commentaire,
                    m.cree_le,
                    a.nom AS article_nom,
                    a.unite AS article_unite
                FROM pharmacie_mouvements m
                INNER JOIN pharmacie_articles a ON a.id = m.article_id
                WHERE m.caserne_id = ?
                  AND m.type = \'sortie\'
                  AND COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id)) IN (' . $placeholders . ')
                ORDER BY m.id ASC
            ';
            $linesStatement = $connection->prepare($linesSql);
            $lineParams = array_merge([$caserneId], $keys);
            $linesStatement->execute($lineParams);
            $rows = $linesStatement->fetchAll(PDO::FETCH_ASSOC);

            $linesByGroup = [];
            foreach ($rows as $row) {
                $key = (string) ($row['sortie_key'] ?? '');
                if ($key === '') {
                    continue;
                }
                if (!isset($linesByGroup[$key])) {
                    $linesByGroup[$key] = [];
                }
                $linesByGroup[$key][] = $row;
            }

            foreach ($groups as &$group) {
                $key = (string) ($group['sortie_key'] ?? '');
                $group['items'] = $linesByGroup[$key] ?? [];
            }
            unset($group);

            return $groups;
        }

        // Fallback legacy: une ligne = une sortie
        $rows = $this->findLastMovementsFiltered($caserneId, $filters, $limitGroups);
        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'sortie_key' => 'legacy:' . (string) ($row['id'] ?? ''),
                'cree_le' => (string) ($row['cree_le'] ?? ''),
                'declarant' => (string) ($row['declarant'] ?? ''),
                'lignes' => 1,
                'total_quantite' => (float) ($row['quantite'] ?? 0),
                'items' => [$row],
            ];
        }

        return $groups;
    }

    private function findLastMovementsFiltered(int $caserneId, array $filters, int $limit = 100): array
    {
        $limit = max(1, min(300, $limit));
        $connection = Database::getConnection();

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $articleSearch = trim((string) ($filters['article'] ?? ''));
        $declarantSearch = trim((string) ($filters['declarant'] ?? ''));

        $where = [
            'm.caserne_id = :caserne_id',
            'm.type = \'sortie\'',
        ];
        $params = ['caserne_id' => $caserneId];

        if ($dateFrom !== '') {
            $where[] = 'DATE(m.cree_le) >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = 'DATE(m.cree_le) <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if ($articleSearch !== '') {
            $where[] = 'a.nom LIKE :article';
            $params['article'] = '%' . $articleSearch . '%';
        }
        if ($declarantSearch !== '') {
            $where[] = 'm.declarant LIKE :declarant';
            $params['declarant'] = '%' . $declarantSearch . '%';
        }

        $sql = '
            SELECT
                m.id,
                m.article_id,
                m.caserne_id,
                m.type,
                m.quantite,
                m.commentaire,
                m.declarant,
                m.cree_le,
                a.nom AS article_nom,
                a.unite AS article_unite
            FROM pharmacie_mouvements m
            INNER JOIN pharmacie_articles a ON a.id = m.article_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY m.cree_le DESC, m.id DESC
            LIMIT ' . $limit;

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
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

    private function hasOutputGroupColumn(): bool
    {
        if ($this->outputGroupColumnExists !== null) {
            return $this->outputGroupColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM pharmacie_mouvements LIKE 'sortie_ref'");
            $this->outputGroupColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->outputGroupColumnExists = false;
        }

        return $this->outputGroupColumnExists;
    }

    private function generateOutputReference(): string
    {
        try {
            return 'out_' . bin2hex(random_bytes(12));
        } catch (Throwable $throwable) {
            return 'out_' . str_replace('.', '', (string) microtime(true));
        }
    }
}
