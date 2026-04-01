<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

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
}
