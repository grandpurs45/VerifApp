<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class VehicleRepository
{
    public function findAllActive(): array
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
            ORDER BY v.nom ASC
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
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.actif,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            ORDER BY v.nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.actif,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv
                ON tv.id = v.type_vehicule_id
            WHERE v.id = :id
        ';

        $statement = $connection->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $vehicle = $statement->fetch(PDO::FETCH_ASSOC);

        if ($vehicle === false) {
            return null;
        }

        return $vehicle;
    }

    public function create(string $name, int $typeVehiculeId, bool $active): bool
    {
        $connection = Database::getConnection();

        $sql = '
            INSERT INTO vehicules (nom, type_vehicule_id, actif)
            VALUES (:nom, :type_vehicule_id, :actif)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function update(int $id, string $name, int $typeVehiculeId, bool $active): bool
    {
        $connection = Database::getConnection();

        $sql = '
            UPDATE vehicules
            SET nom = :nom,
                type_vehicule_id = :type_vehicule_id,
                actif = :actif
            WHERE id = :id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM vehicules WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }
}
