<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Throwable;

final class VehicleRepository
{
    public function findAllActive(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            WHERE v.actif = 1
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDetailed(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.caserne_id,
                v.actif,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            ' . ($caserneId !== null ? 'WHERE v.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.caserne_id,
                v.actif,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv
                ON tv.id = v.type_vehicule_id
            WHERE v.id = :id
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);

        $params = ['id' => $id];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $vehicle = $statement->fetch(PDO::FETCH_ASSOC);

        if ($vehicle === false) {
            return null;
        }

        return $vehicle;
    }

    public function create(string $name, int $typeVehiculeId, bool $active, int $caserneId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            INSERT INTO vehicules (caserne_id, nom, type_vehicule_id, actif)
            VALUES (:caserne_id, :nom, :type_vehicule_id, :actif)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function createAndReturnId(string $name, int $typeVehiculeId, bool $active, int $caserneId): ?int
    {
        $created = $this->create($name, $typeVehiculeId, $active, $caserneId);
        if (!$created) {
            return null;
        }

        $connection = Database::getConnection();
        $id = (int) $connection->lastInsertId();

        return $id > 0 ? $id : null;
    }

    public function update(int $id, string $name, int $typeVehiculeId, bool $active, int $caserneId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            UPDATE vehicules
            SET nom = :nom,
                type_vehicule_id = :type_vehicule_id,
                actif = :actif
            WHERE id = :id
              AND caserne_id = :caserne_id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'caserne_id' => $caserneId,
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function delete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM vehicules WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
    }

    public function existsByTypeAndName(int $typeVehiculeId, string $name, int $caserneId, ?int $excludeId = null): bool
    {
        $connection = Database::getConnection();
        $sql = '
            SELECT 1
            FROM vehicules
            WHERE type_vehicule_id = :type_vehicule_id
              AND nom = :nom
              AND caserne_id = :caserne_id
        ';

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= ' LIMIT 1';

        $statement = $connection->prepare($sql);
        $params = [
            'type_vehicule_id' => $typeVehiculeId,
            'nom' => $name,
            'caserne_id' => $caserneId,
        ];
        if ($excludeId !== null && $excludeId > 0) {
            $params['exclude_id'] = $excludeId;
        }

        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function forceDelete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            // 1) Supprime les sessions de verification du vehicule
            // (verification_lignes + anomalies sont supprimees en cascade).
            $deleteVerifications = $connection->prepare('
                DELETE FROM verifications
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $deleteVerifications->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);

            // 2) Supprime le materiel/controles du vehicule.
            $deleteControles = $connection->prepare('
                DELETE FROM controles
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $deleteControles->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);

            // 3) Supprime les zones restantes (enfants -> parents).
            // Le FK zones.parent_id est en RESTRICT, donc on supprime les feuilles par vagues.
            while (true) {
                $deleteLeafZones = $connection->prepare('
                    DELETE z
                    FROM zones z
                    LEFT JOIN zones c ON c.parent_id = z.id
                    WHERE z.vehicule_id = :vehicule_id
                      AND z.caserne_id = :caserne_id
                      AND c.id IS NULL
                ');
                $deleteLeafZones->execute([
                    'vehicule_id' => $id,
                    'caserne_id' => $caserneId,
                ]);

                $deletedRows = $deleteLeafZones->rowCount();
                if ($deletedRows === 0) {
                    break;
                }
            }

            $remainingZones = $connection->prepare('
                SELECT COUNT(*) FROM zones
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $remainingZones->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);
            if ((int) $remainingZones->fetchColumn() > 0) {
                throw new \RuntimeException('vehicle_force_delete_zones_failed');
            }

            // 4) Supprime le vehicule.
            $deleteVehicle = $connection->prepare('
                DELETE FROM vehicules
                WHERE id = :id
                  AND caserne_id = :caserne_id
            ');
            $ok = $deleteVehicle->execute([
                'id' => $id,
                'caserne_id' => $caserneId,
            ]);

            if (!$ok) {
                throw new \RuntimeException('vehicle_force_delete_failed');
            }

            $connection->commit();
            return true;
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $throwable;
        }
    }
}
