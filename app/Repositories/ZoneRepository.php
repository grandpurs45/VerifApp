<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class ZoneRepository
{
    private ?bool $tableExists = null;
    private ?bool $parentColumnExists = null;
    private ?bool $orderColumnExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function findAllDetailed(?int $caserneId = null): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $parentSelect = $this->hasParentColumn() ? 'z.parent_id' : 'NULL AS parent_id';
        $orderSelect = $this->hasOrderColumn() ? 'z.ordre' : '0 AS ordre';
        $sql = '
            SELECT
                z.id,
                z.vehicule_id,
                z.caserne_id,
                ' . $parentSelect . ',
                ' . $orderSelect . ',
                z.nom,
                v.nom AS vehicule_nom
            FROM zones z
            INNER JOIN vehicules v ON v.id = z.vehicule_id
            ' . ($caserneId !== null ? 'WHERE z.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC, z.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $this->withPaths($rows);
    }

    public function findByVehicleId(int $vehicleId, ?int $caserneId = null): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $parentSelect = $this->hasParentColumn() ? 'parent_id' : 'NULL AS parent_id';
        $orderSelect = $this->hasOrderColumn() ? 'ordre' : '0 AS ordre';
        $statement = $connection->prepare('
            SELECT id, vehicule_id, caserne_id, ' . $parentSelect . ', ' . $orderSelect . ', nom
            FROM zones
            WHERE vehicule_id = :vehicule_id
              ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            ORDER BY ' . ($this->hasOrderColumn() ? 'ordre ASC,' : '') . ' nom ASC
        ');

        $params = ['vehicule_id' => $vehicleId];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $this->withPaths($rows);
    }

    public function create(int $vehicleId, string $name, ?int $parentId = null, int $caserneId = 0, ?int $order = null): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $hasParentColumn = $this->hasParentColumn();
        $hasOrderColumn = $this->hasOrderColumn();
        if ($order === null) {
            $order = $this->nextOrder($vehicleId, $parentId, $caserneId);
        }

        if ($hasParentColumn && $hasOrderColumn) {
            $statement = $connection->prepare('
                INSERT INTO zones (caserne_id, vehicule_id, parent_id, nom, ordre)
                VALUES (:caserne_id, :vehicule_id, :parent_id, :nom, :ordre)
            ');
        } elseif ($hasParentColumn) {
            $statement = $connection->prepare('
                INSERT INTO zones (caserne_id, vehicule_id, parent_id, nom)
                VALUES (:caserne_id, :vehicule_id, :parent_id, :nom)
            ');
        } elseif ($hasOrderColumn) {
            $statement = $connection->prepare('
                INSERT INTO zones (caserne_id, vehicule_id, nom, ordre)
                VALUES (:caserne_id, :vehicule_id, :nom, :ordre)
            ');
        } else {
            $statement = $connection->prepare('
                INSERT INTO zones (caserne_id, vehicule_id, nom)
                VALUES (:caserne_id, :vehicule_id, :nom)
            ');
        }

        $params = [
            'caserne_id' => $caserneId,
            'vehicule_id' => $vehicleId,
            'nom' => $name,
        ];

        if ($hasParentColumn) {
            $params['parent_id'] = $parentId;
        }
        if ($hasOrderColumn) {
            $params['ordre'] = max(0, $order);
        }

        return $statement->execute($params);
    }

    public function update(int $id, int $vehicleId, string $name, ?int $parentId = null, int $caserneId = 0, ?int $order = null): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $hasParentColumn = $this->hasParentColumn();
        $hasOrderColumn = $this->hasOrderColumn();
        if ($order === null) {
            $order = 0;
        }

        if ($hasParentColumn && $hasOrderColumn) {
            $statement = $connection->prepare('
                UPDATE zones
                SET nom = :nom, parent_id = :parent_id, ordre = :ordre
                WHERE id = :id
                  AND vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
        } elseif ($hasParentColumn) {
            $statement = $connection->prepare('
                UPDATE zones
                SET nom = :nom, parent_id = :parent_id
                WHERE id = :id
                  AND vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
        } elseif ($hasOrderColumn) {
            $statement = $connection->prepare('
                UPDATE zones
                SET nom = :nom, ordre = :ordre
                WHERE id = :id
                  AND vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
        } else {
            $statement = $connection->prepare('
                UPDATE zones
                SET nom = :nom
                WHERE id = :id
                  AND vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
        }

        $params = [
            'id' => $id,
            'vehicule_id' => $vehicleId,
            'caserne_id' => $caserneId,
            'nom' => $name,
        ];

        if ($hasParentColumn) {
            $params['parent_id'] = $parentId;
        }
        if ($hasOrderColumn) {
            $params['ordre'] = max(0, $order);
        }

        return $statement->execute($params);
    }

    public function nextOrder(int $vehicleId, ?int $parentId = null, int $caserneId = 0): int
    {
        if (!$this->hasTable() || !$this->hasOrderColumn()) {
            return 0;
        }

        $connection = Database::getConnection();
        $parentCondition = '1 = 1';
        if ($this->hasParentColumn()) {
            $parentCondition = $parentId !== null && $parentId > 0 ? 'parent_id = :parent_id' : 'parent_id IS NULL';
        }
        $statement = $connection->prepare('
            SELECT COALESCE(MAX(ordre), 0) + 10
            FROM zones
            WHERE vehicule_id = :vehicule_id
              AND caserne_id = :caserne_id
              AND ' . $parentCondition . '
        ');

        $params = [
            'vehicule_id' => $vehicleId,
            'caserne_id' => $caserneId,
        ];
        if ($this->hasParentColumn() && $parentId !== null && $parentId > 0) {
            $params['parent_id'] = $parentId;
        }
        $statement->execute($params);

        return max(10, (int) $statement->fetchColumn());
    }

    public function delete(int $id, int $caserneId): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM zones WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
    }

    public function belongsToVehicle(int $zoneId, int $vehicleId, ?int $caserneId = null): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT 1
            FROM zones
            WHERE id = :id
              AND vehicule_id = :vehicule_id
              ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            LIMIT 1
        ');

        $params = [
            'id' => $zoneId,
            'vehicule_id' => $vehicleId,
        ];

        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function hasParentColumn(): bool
    {
        if ($this->parentColumnExists !== null) {
            return $this->parentColumnExists;
        }

        if (!$this->hasTable()) {
            $this->parentColumnExists = false;
            return false;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM zones LIKE 'parent_id'");
            $this->parentColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->parentColumnExists = false;
        }

        return $this->parentColumnExists;
    }

    public function hasOrderColumn(): bool
    {
        if ($this->orderColumnExists !== null) {
            return $this->orderColumnExists;
        }

        if (!$this->hasTable()) {
            $this->orderColumnExists = false;
            return false;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM zones LIKE 'ordre'");
            $this->orderColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->orderColumnExists = false;
        }

        return $this->orderColumnExists;
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'zones'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }

    private function withPaths(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $byId = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $row;
            }
        }

        $cache = [];

        $buildPath = function (int $zoneId, array $visited) use (&$buildPath, &$cache, $byId): string {
            if (isset($cache[$zoneId])) {
                return $cache[$zoneId];
            }

            if (isset($visited[$zoneId]) || !isset($byId[$zoneId])) {
                return '';
            }

            $visited[$zoneId] = true;
            $row = $byId[$zoneId];
            $name = trim((string) ($row['nom'] ?? ''));
            $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;

            if ($parentId <= 0 || !isset($byId[$parentId])) {
                $cache[$zoneId] = $name;
                return $cache[$zoneId];
            }

            $parent = $byId[$parentId];
            if ((int) ($parent['vehicule_id'] ?? 0) !== (int) ($row['vehicule_id'] ?? 0)) {
                $cache[$zoneId] = $name;
                return $cache[$zoneId];
            }

            $parentPath = $buildPath($parentId, $visited);
            $cache[$zoneId] = $parentPath === '' ? $name : ($parentPath . ' > ' . $name);
            return $cache[$zoneId];
        };

        $sortCache = [];
        $buildSortKey = function (int $zoneId, array $visited) use (&$buildSortKey, &$sortCache, $byId): string {
            if (isset($sortCache[$zoneId])) {
                return $sortCache[$zoneId];
            }

            if (isset($visited[$zoneId]) || !isset($byId[$zoneId])) {
                return '';
            }

            $visited[$zoneId] = true;
            $row = $byId[$zoneId];
            $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
            $segment = sprintf('%06d-%s-%06d', (int) ($row['ordre'] ?? 0), strtolower((string) ($row['nom'] ?? '')), (int) ($row['id'] ?? 0));

            if ($parentId <= 0 || !isset($byId[$parentId])) {
                $sortCache[$zoneId] = $segment;
                return $sortCache[$zoneId];
            }

            $parent = $byId[$parentId];
            if ((int) ($parent['vehicule_id'] ?? 0) !== (int) ($row['vehicule_id'] ?? 0)) {
                $sortCache[$zoneId] = $segment;
                return $sortCache[$zoneId];
            }

            $parentSort = $buildSortKey($parentId, $visited);
            $sortCache[$zoneId] = $parentSort === '' ? $segment : ($parentSort . '/' . $segment);
            return $sortCache[$zoneId];
        };

        foreach ($rows as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $row['chemin'] = $id > 0 ? $buildPath($id, []) : (string) ($row['nom'] ?? '');
            $row['niveau'] = max(1, substr_count((string) $row['chemin'], '>') + 1);
            $row['_sort_key'] = $id > 0 ? $buildSortKey($id, []) : '';
            $row['tri_arborescence'] = $row['_sort_key'];
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            $vehiculeCompare = strcmp((string) ($a['vehicule_nom'] ?? ''), (string) ($b['vehicule_nom'] ?? ''));
            if ($vehiculeCompare !== 0) {
                return $vehiculeCompare;
            }

            $sortCompare = strcmp((string) ($a['_sort_key'] ?? ''), (string) ($b['_sort_key'] ?? ''));
            if ($sortCompare !== 0) {
                return $sortCompare;
            }

            return strcmp((string) ($a['chemin'] ?? ''), (string) ($b['chemin'] ?? ''));
        });

        foreach ($rows as &$row) {
            unset($row['_sort_key']);
        }
        unset($row);

        return $rows;
    }
}
