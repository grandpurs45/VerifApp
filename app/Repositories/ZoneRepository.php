<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class ZoneRepository
{
    private ?bool $tableExists = null;

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
        $sql = '
            SELECT
                z.id,
                z.vehicule_id,
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

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVehicleId(int $vehicleId): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT id, vehicule_id, nom
            FROM zones
            WHERE vehicule_id = :vehicule_id
            ORDER BY nom ASC
        ');
        $statement->execute(['vehicule_id' => $vehicleId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $vehicleId, string $name): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO zones (vehicule_id, nom)
            VALUES (:vehicule_id, :nom)
        ');

        return $statement->execute([
            'vehicule_id' => $vehicleId,
            'nom' => $name,
        ]);
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
}
