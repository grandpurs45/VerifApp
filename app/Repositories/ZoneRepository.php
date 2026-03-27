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

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function findAllDetailed(): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $parentSelect = $this->hasParentColumn() ? 'z.parent_id' : 'NULL AS parent_id';
        $sql = '
            SELECT
                z.id,
                z.vehicule_id,
                ' . $parentSelect . ',
                z.nom,
                v.nom AS vehicule_nom
            FROM zones z
            INNER JOIN vehicules v ON v.id = z.vehicule_id
            ORDER BY v.nom ASC, z.nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $this->withPaths($rows);
    }

    public function findByVehicleId(int $vehicleId): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $parentSelect = $this->hasParentColumn() ? 'parent_id' : 'NULL AS parent_id';
        $statement = $connection->prepare('
            SELECT id, vehicule_id, ' . $parentSelect . ', nom
            FROM zones
            WHERE vehicule_id = :vehicule_id
            ORDER BY nom ASC
        ');
        $statement->execute(['vehicule_id' => $vehicleId]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $this->withPaths($rows);
    }

    public function create(int $vehicleId, string $name, ?int $parentId = null): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        if ($this->hasParentColumn()) {
            $statement = $connection->prepare('
                INSERT INTO zones (vehicule_id, parent_id, nom)
                VALUES (:vehicule_id, :parent_id, :nom)
            ');
        } else {
            $statement = $connection->prepare('
                INSERT INTO zones (vehicule_id, nom)
                VALUES (:vehicule_id, :nom)
            ');
        }

        $params = [
            'vehicule_id' => $vehicleId,
            'nom' => $name,
        ];

        if ($this->hasParentColumn()) {
            $params['parent_id'] = $parentId;
        }

        return $statement->execute($params);
    }

    public function delete(int $id): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM zones WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function belongsToVehicle(int $zoneId, int $vehicleId): bool
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
            LIMIT 1
        ');
        $statement->execute([
            'id' => $zoneId,
            'vehicule_id' => $vehicleId,
        ]);

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

        foreach ($rows as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $row['chemin'] = $id > 0 ? $buildPath($id, []) : (string) ($row['nom'] ?? '');
            $row['niveau'] = max(1, substr_count((string) $row['chemin'], '>') + 1);
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            $vehiculeCompare = strcmp((string) ($a['vehicule_nom'] ?? ''), (string) ($b['vehicule_nom'] ?? ''));
            if ($vehiculeCompare !== 0) {
                return $vehiculeCompare;
            }

            return strcmp((string) ($a['chemin'] ?? ''), (string) ($b['chemin'] ?? ''));
        });

        return $rows;
    }
}
