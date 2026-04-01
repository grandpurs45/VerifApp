<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TypeVehiculeRepository
{
    public function findById(int $id, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();

        $statement = $connection->prepare('
            SELECT id, nom, caserne_id
            FROM type_vehicules
            WHERE id = :id
              ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            LIMIT 1
        ');

        $params = ['id' => $id];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $type = $statement->fetch(PDO::FETCH_ASSOC);

        if ($type === false) {
            return null;
        }

        return $type;
    }

    public function findAll(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                nom,
                caserne_id
            FROM type_vehicules
            ' . ($caserneId !== null ? 'WHERE caserne_id = :caserne_id' : '') . '
            ORDER BY nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO type_vehicules (caserne_id, nom)
            VALUES (:caserne_id, :nom)
        ');

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
        ]);
    }

    public function update(int $id, string $name, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE type_vehicules
            SET nom = :nom
            WHERE id = :id
              AND caserne_id = :caserne_id
        ');

        return $statement->execute([
            'id' => $id,
            'caserne_id' => $caserneId,
            'nom' => $name,
        ]);
    }

    public function delete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM type_vehicules WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
    }
}
