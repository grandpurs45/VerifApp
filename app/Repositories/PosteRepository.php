<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PosteRepository
{
    public function findAll(): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code
            FROM postes p
            ORDER BY p.nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDetailed(): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.type_vehicule_id,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv ON tv.id = p.type_vehicule_id
            ORDER BY p.nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code
            FROM postes p
            INNER JOIN vehicules v
                ON v.type_vehicule_id = p.type_vehicule_id
            WHERE v.id = :vehicle_id
            ORDER BY p.nom ASC
        ';

        $statement = $connection->prepare($sql);

        $statement->execute([
            'vehicle_id' => $vehicleId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.type_vehicule_id,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv
                ON tv.id = p.type_vehicule_id
            WHERE p.id = :id
        ';

        $statement = $connection->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $poste = $statement->fetch(PDO::FETCH_ASSOC);

        if ($poste === false) {
            return null;
        }

        return $poste;
    }

    public function findByIdForVehicle(int $posteId, int $vehicleId): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv
                ON tv.id = p.type_vehicule_id
            INNER JOIN vehicules v
                ON v.type_vehicule_id = p.type_vehicule_id
            WHERE p.id = :poste_id
              AND v.id = :vehicle_id
        ';

        $statement = $connection->prepare($sql);

        $statement->execute([
            'poste_id' => $posteId,
            'vehicle_id' => $vehicleId,
        ]);

        $poste = $statement->fetch(PDO::FETCH_ASSOC);

        if ($poste === false) {
            return null;
        }

        return $poste;
    }

    public function create(string $name, string $code, int $typeVehiculeId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            INSERT INTO postes (nom, code, type_vehicule_id)
            VALUES (:nom, :code, :type_vehicule_id)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'nom' => $name,
            'code' => $code,
            'type_vehicule_id' => $typeVehiculeId,
        ]);
    }

    public function update(int $id, string $name, string $code, int $typeVehiculeId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            UPDATE postes
            SET nom = :nom,
                code = :code,
                type_vehicule_id = :type_vehicule_id
            WHERE id = :id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'nom' => $name,
            'code' => $code,
            'type_vehicule_id' => $typeVehiculeId,
        ]);
    }

    public function delete(int $id): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM postes WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }
}
