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
    private ?bool $freeLabelColumnExists = null;
    private ?bool $articleIdNullable = null;
    private ?bool $ackColumnExists = null;

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
                actif,
                motif_sortie_obligatoire
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
        bool $active,
        bool $outputReasonRequired = false
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
                    actif = :actif,
                    motif_sortie_obligatoire = :motif_sortie_obligatoire
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
                'motif_sortie_obligatoire' => $outputReasonRequired ? 1 : 0,
            ]);
        }

        $sql = '
            INSERT INTO pharmacie_articles (caserne_id, nom, unite, stock_actuel, seuil_alerte, actif, motif_sortie_obligatoire)
            VALUES (:caserne_id, :nom, :unite, :stock_actuel, :seuil_alerte, :actif, :motif_sortie_obligatoire)
        ';
        $statement = $connection->prepare($sql);

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
            'unite' => $unit,
            'stock_actuel' => $stock,
            'seuil_alerte' => $alertThreshold,
            'actif' => $active ? 1 : 0,
            'motif_sortie_obligatoire' => $outputReasonRequired ? 1 : 0,
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
            $hasOutputGroupColumn = $this->hasOutputGroupColumn();
            $hasFreeLabelColumn = $this->hasFreeLabelColumn();
            $supportsFreeLabelOutputs = $this->supportsFreeLabelOutputs();

            $stockStatement = $connection->prepare(
                'SELECT stock_actuel, actif FROM pharmacie_articles WHERE id = :id AND caserne_id = :caserne_id FOR UPDATE'
            );
            $updateStatement = $connection->prepare(
                'UPDATE pharmacie_articles SET stock_actuel = stock_actuel - :quantity WHERE id = :id AND caserne_id = :caserne_id'
            );
            if ($hasOutputGroupColumn) {
                if ($hasFreeLabelColumn) {
                    $insertStatement = $connection->prepare(
                        'INSERT INTO pharmacie_mouvements (caserne_id, article_id, article_libre_nom, type, sortie_ref, quantite, commentaire, declarant)
                         VALUES (:caserne_id, :article_id, :article_libre_nom, \'sortie\', :sortie_ref, :quantite, :commentaire, :declarant)'
                    );
                } else {
                    $insertStatement = $connection->prepare(
                        'INSERT INTO pharmacie_mouvements (caserne_id, article_id, type, sortie_ref, quantite, commentaire, declarant)
                         VALUES (:caserne_id, :article_id, \'sortie\', :sortie_ref, :quantite, :commentaire, :declarant)'
                    );
                }
            } else {
                if ($hasFreeLabelColumn) {
                    $insertStatement = $connection->prepare(
                        'INSERT INTO pharmacie_mouvements (caserne_id, article_id, article_libre_nom, type, quantite, commentaire, declarant)
                         VALUES (:caserne_id, :article_id, :article_libre_nom, \'sortie\', :quantite, :commentaire, :declarant)'
                    );
                } else {
                    $insertStatement = $connection->prepare(
                        'INSERT INTO pharmacie_mouvements (caserne_id, article_id, type, quantite, commentaire, declarant)
                         VALUES (:caserne_id, :article_id, \'sortie\', :quantite, :commentaire, :declarant)'
                    );
                }
            }

            foreach ($lines as $line) {
                $articleId = isset($line['article_id']) ? (int) $line['article_id'] : 0;
                $quantity = isset($line['quantite']) ? (float) $line['quantite'] : 0.0;
                $comment = isset($line['commentaire']) ? trim((string) $line['commentaire']) : '';
                $freeLabel = isset($line['article_libre_nom']) ? trim((string) $line['article_libre_nom']) : '';
                $isIntegerQuantity = abs($quantity - round($quantity)) < 0.00001;

                if ($quantity <= 0 || !$isIntegerQuantity) {
                    $connection->rollBack();
                    return false;
                }

                if ($articleId > 0) {
                    $stockStatement->execute([
                        'id' => $articleId,
                        'caserne_id' => $caserneId,
                    ]);
                    $article = $stockStatement->fetch(PDO::FETCH_ASSOC);

                    if ($article === false || (int) ($article['actif'] ?? 0) !== 1) {
                        $connection->rollBack();
                        return false;
                    }

                    $updateStatement->execute([
                        'quantity' => $quantity,
                        'id' => $articleId,
                        'caserne_id' => $caserneId,
                    ]);
                } else {
                    if (!$supportsFreeLabelOutputs || $freeLabel === '') {
                        $connection->rollBack();
                        return false;
                    }
                }

                $insertParams = [
                    'caserne_id' => $caserneId,
                    'article_id' => $articleId > 0 ? $articleId : null,
                    'quantite' => $quantity,
                    'commentaire' => $comment === '' ? null : $comment,
                    'declarant' => $declarant,
                ];
                if ($hasFreeLabelColumn) {
                    $insertParams['article_libre_nom'] = $freeLabel !== '' ? $freeLabel : null;
                }
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
        $freeNameExpr = $this->hasFreeLabelColumn() ? 'm.article_libre_nom' : "NULL";
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
                m.acquitte_le,
                m.acquitte_par,
                COALESCE(a.nom, ' . $freeNameExpr . ', \'Autre\') AS article_nom,
                COALESCE(a.unite, \'u\') AS article_unite
            FROM pharmacie_mouvements m
            LEFT JOIN pharmacie_articles a ON a.id = m.article_id
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
        $hasFreeLabelColumn = $this->hasFreeLabelColumn();
        $hasAckColumn = $this->hasAckColumn();
        $freeNameExpr = $hasFreeLabelColumn ? 'm.article_libre_nom' : "NULL";
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $articleSearch = trim((string) ($filters['article'] ?? ''));
        $declarantSearch = trim((string) ($filters['declarant'] ?? ''));
        $ackStatus = (string) ($filters['ack_status'] ?? 'pending');
        if (!in_array($ackStatus, ['all', 'pending', 'ack'], true)) {
            $ackStatus = 'pending';
        }

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
            if ($hasAckColumn) {
                if ($ackStatus === 'pending') {
                    $where[] = 'm.acquitte_le IS NULL';
                } elseif ($ackStatus === 'ack') {
                    $where[] = 'm.acquitte_le IS NOT NULL';
                }
            } elseif ($ackStatus === 'ack') {
                return [];
            }
            if ($articleSearch !== '') {
                $articlePredicate = $hasFreeLabelColumn
                    ? '(ax.nom LIKE :article OR mx.article_libre_nom LIKE :article)'
                    : 'ax.nom LIKE :article';
                $where[] = '
                    EXISTS (
                        SELECT 1
                        FROM pharmacie_mouvements mx
                        LEFT JOIN pharmacie_articles ax ON ax.id = mx.article_id
                        WHERE mx.caserne_id = m.caserne_id
                          AND mx.type = \'sortie\'
                          AND COALESCE(NULLIF(mx.sortie_ref, \'\'), CONCAT(\'legacy:\', mx.id))
                              = COALESCE(NULLIF(m.sortie_ref, \'\'), CONCAT(\'legacy:\', m.id))
                          AND ' . $articlePredicate . '
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
                    SUM(m.quantite) AS total_quantite,
                    ' . ($hasAckColumn ? 'MIN(CASE WHEN m.acquitte_le IS NOT NULL THEN 1 ELSE 0 END)' : '0') . ' AS acquitte,
                    ' . ($hasAckColumn ? 'MAX(m.acquitte_le)' : 'NULL') . ' AS acquitte_le,
                    ' . ($hasAckColumn ? 'MAX(COALESCE(m.acquitte_par, \'\'))' : "''") . ' AS acquitte_par
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
                    COALESCE(a.nom, ' . $freeNameExpr . ', \'Autre\') AS article_nom,
                    COALESCE(a.unite, \'u\') AS article_unite
                FROM pharmacie_mouvements m
                LEFT JOIN pharmacie_articles a ON a.id = m.article_id
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
                'acquitte' => isset($row['acquitte_le']) && $row['acquitte_le'] !== null ? 1 : 0,
                'acquitte_le' => (string) ($row['acquitte_le'] ?? ''),
                'acquitte_par' => (string) ($row['acquitte_par'] ?? ''),
                'items' => [$row],
            ];
        }

        return $groups;
    }

    private function findLastMovementsFiltered(int $caserneId, array $filters, int $limit = 100): array
    {
        $limit = max(1, min(300, $limit));
        $connection = Database::getConnection();
        $hasFreeLabelColumn = $this->hasFreeLabelColumn();
        $hasAckColumn = $this->hasAckColumn();
        $freeNameExpr = $hasFreeLabelColumn ? 'm.article_libre_nom' : "NULL";

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $articleSearch = trim((string) ($filters['article'] ?? ''));
        $declarantSearch = trim((string) ($filters['declarant'] ?? ''));
        $ackStatus = (string) ($filters['ack_status'] ?? 'pending');
        if (!in_array($ackStatus, ['all', 'pending', 'ack'], true)) {
            $ackStatus = 'pending';
        }

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
            $where[] = $hasFreeLabelColumn
                ? '(a.nom LIKE :article OR m.article_libre_nom LIKE :article)'
                : 'a.nom LIKE :article';
            $params['article'] = '%' . $articleSearch . '%';
        }
        if ($declarantSearch !== '') {
            $where[] = 'm.declarant LIKE :declarant';
            $params['declarant'] = '%' . $declarantSearch . '%';
        }
        if ($hasAckColumn) {
            if ($ackStatus === 'pending') {
                $where[] = 'm.acquitte_le IS NULL';
            } elseif ($ackStatus === 'ack') {
                $where[] = 'm.acquitte_le IS NOT NULL';
            }
        } elseif ($ackStatus === 'ack') {
            return [];
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
                m.acquitte_le,
                m.acquitte_par,
                COALESCE(a.nom, ' . $freeNameExpr . ', \'Autre\') AS article_nom,
                COALESCE(a.unite, \'u\') AS article_unite
            FROM pharmacie_mouvements m
            LEFT JOIN pharmacie_articles a ON a.id = m.article_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY m.cree_le DESC, m.id DESC
            LIMIT ' . $limit;

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acknowledgeOutputGroup(int $caserneId, string $sortieKey, string $managerName): bool
    {
        if (!$this->hasMovementsTable() || !$this->hasAckColumn()) {
            return false;
        }

        $sortieKey = trim($sortieKey);
        $managerName = trim($managerName);
        if ($sortieKey === '' || $managerName === '') {
            return false;
        }

        $connection = Database::getConnection();
        $params = [
            'caserne_id' => $caserneId,
            'acquitte_par' => $managerName,
        ];

        if (str_starts_with($sortieKey, 'legacy:')) {
            $legacyId = (int) substr($sortieKey, 7);
            if ($legacyId <= 0) {
                return false;
            }

            $sql = '
                UPDATE pharmacie_mouvements
                SET acquitte_le = NOW(), acquitte_par = :acquitte_par
                WHERE caserne_id = :caserne_id
                  AND id = :legacy_id
                  AND type = \'sortie\'
                  AND acquitte_le IS NULL
            ';
            $params['legacy_id'] = $legacyId;
            $statement = $connection->prepare($sql);
            $statement->execute($params);

            return $statement->rowCount() > 0;
        }

        if (!$this->hasOutputGroupColumn()) {
            return false;
        }

        $sql = '
            UPDATE pharmacie_mouvements
            SET acquitte_le = NOW(), acquitte_par = :acquitte_par
            WHERE caserne_id = :caserne_id
              AND type = \'sortie\'
              AND sortie_ref = :sortie_ref
              AND acquitte_le IS NULL
        ';
        $params['sortie_ref'] = $sortieKey;
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function createOrderMark(int $caserneId, string $managerName, ?string $note = null): bool
    {
        if (!$this->hasOrdersTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare(
            'INSERT INTO pharmacie_commandes (caserne_id, commande_le, note, cree_par)
             VALUES (:caserne_id, NOW(), :note, :cree_par)'
        );

        return $statement->execute([
            'caserne_id' => $caserneId,
            'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            'cree_par' => trim($managerName) !== '' ? trim($managerName) : null,
        ]);
    }

    public function findLastOrder(int $caserneId): ?array
    {
        if (!$this->hasOrdersTable()) {
            return null;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare(
            'SELECT id, caserne_id, commande_le, note, cree_par
             FROM pharmacie_commandes
             WHERE caserne_id = :caserne_id
             ORDER BY commande_le DESC, id DESC
             LIMIT 1'
        );
        $statement->execute(['caserne_id' => $caserneId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function findSummarySinceLastOrder(int $caserneId, bool $onlyPendingAcknowledge = false): array
    {
        if (!$this->hasMovementsTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $hasAckColumn = $this->hasAckColumn();
        $hasFreeLabelColumn = $this->hasFreeLabelColumn();
        $freeNameExpr = $hasFreeLabelColumn ? 'm.article_libre_nom' : "NULL";
        $lastOrder = $this->findLastOrder($caserneId);

        $where = [
            'm.caserne_id = :caserne_id',
            'm.type = \'sortie\'',
        ];
        $params = ['caserne_id' => $caserneId];
        if ($lastOrder !== null && isset($lastOrder['commande_le'])) {
            $where[] = 'm.cree_le > :last_order';
            $params['last_order'] = (string) $lastOrder['commande_le'];
        }
        if ($onlyPendingAcknowledge && $hasAckColumn) {
            $where[] = 'm.acquitte_le IS NULL';
        }

        $sql = '
            SELECT
                COALESCE(a.nom, ' . $freeNameExpr . ', \'Autre\') AS article_nom,
                COALESCE(a.unite, \'u\') AS article_unite,
                SUM(m.quantite) AS quantite_totale,
                COUNT(*) AS lignes
            FROM pharmacie_mouvements m
            LEFT JOIN pharmacie_articles a ON a.id = m.article_id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY COALESCE(a.nom, ' . $freeNameExpr . ', \'Autre\'), COALESCE(a.unite, \'u\')
            ORDER BY article_nom ASC
        ';
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

    private function hasFreeLabelColumn(): bool
    {
        if ($this->freeLabelColumnExists !== null) {
            return $this->freeLabelColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM pharmacie_mouvements LIKE 'article_libre_nom'");
            $this->freeLabelColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->freeLabelColumnExists = false;
        }

        return $this->freeLabelColumnExists;
    }

    public function supportsFreeLabelOutputs(): bool
    {
        return $this->hasFreeLabelColumn() && $this->isMovementArticleIdNullable();
    }

    private function hasAckColumn(): bool
    {
        if ($this->ackColumnExists !== null) {
            return $this->ackColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM pharmacie_mouvements LIKE 'acquitte_le'");
            $this->ackColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->ackColumnExists = false;
        }

        return $this->ackColumnExists;
    }

    private function hasOrdersTable(): bool
    {
        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'pharmacie_commandes'");
            return $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            return false;
        }
    }

    private function isMovementArticleIdNullable(): bool
    {
        if ($this->articleIdNullable !== null) {
            return $this->articleIdNullable;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM pharmacie_mouvements LIKE 'article_id'");
            $row = $statement !== false ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            $this->articleIdNullable = is_array($row) && strtoupper((string) ($row['Null'] ?? 'NO')) === 'YES';
        } catch (PDOException $exception) {
            $this->articleIdNullable = false;
        }

        return $this->articleIdNullable;
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
