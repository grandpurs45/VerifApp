<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PosteRepository
{
    public function findAll(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.caserne_id
            FROM postes p
            ' . ($caserneId !== null ? 'WHERE p.caserne_id = :caserne_id' : '') . '
            ORDER BY p.nom ASC
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
                p.id,
                p.nom,
                p.code,
                p.type_vehicule_id,
                p.caserne_id,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv ON tv.id = p.type_vehicule_id
            ' . ($caserneId !== null ? 'WHERE p.caserne_id = :caserne_id' : '') . '
            ORDER BY p.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVehicleId(int $vehicleId, ?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.caserne_id
            FROM postes p
            INNER JOIN vehicules v
                ON v.type_vehicule_id = p.type_vehicule_id
            WHERE v.id = :vehicle_id
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id AND p.caserne_id = :caserne_id' : '') . '
            ORDER BY p.nom ASC
        ';

        $statement = $connection->prepare($sql);

        $params = ['vehicle_id' => $vehicleId];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.type_vehicule_id,
                p.caserne_id,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv
                ON tv.id = p.type_vehicule_id
            WHERE p.id = :id
              ' . ($caserneId !== null ? 'AND p.caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);

        $params = ['id' => $id];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $poste = $statement->fetch(PDO::FETCH_ASSOC);

        if ($poste === false) {
            return null;
        }

        return $poste;
    }

    public function findByIdForVehicle(int $posteId, int $vehicleId, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                p.id,
                p.nom,
                p.code,
                p.caserne_id,
                tv.nom AS type_vehicule
            FROM postes p
            INNER JOIN type_vehicules tv
                ON tv.id = p.type_vehicule_id
            INNER JOIN vehicules v
                ON v.type_vehicule_id = p.type_vehicule_id
            WHERE p.id = :poste_id
              AND v.id = :vehicle_id
              ' . ($caserneId !== null ? 'AND p.caserne_id = :caserne_id AND v.caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);

        $params = [
            'poste_id' => $posteId,
            'vehicle_id' => $vehicleId,
        ];

        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $poste = $statement->fetch(PDO::FETCH_ASSOC);

        if ($poste === false) {
            return null;
        }

        return $poste;
    }

    public function create(string $name, string $code, int $typeVehiculeId, int $caserneId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            INSERT INTO postes (caserne_id, nom, code, type_vehicule_id)
            VALUES (:caserne_id, :nom, :code, :type_vehicule_id)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
            'code' => $code,
            'type_vehicule_id' => $typeVehiculeId,
        ]);
    }

    public function update(int $id, string $name, string $code, int $typeVehiculeId, int $caserneId): bool
    {
        $connection = Database::getConnection();

        $sql = '
            UPDATE postes
            SET nom = :nom,
                code = :code,
                type_vehicule_id = :type_vehicule_id
            WHERE id = :id
              AND caserne_id = :caserne_id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'caserne_id' => $caserneId,
            'nom' => $name,
            'code' => $code,
            'type_vehicule_id' => $typeVehiculeId,
        ]);
    }

    public function delete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM postes WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
    }
}
