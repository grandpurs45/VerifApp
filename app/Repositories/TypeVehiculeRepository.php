<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TypeVehiculeRepository
{
    public function findById(int $id): ?array
    {
        $connection = Database::getConnection();

        $statement = $connection->prepare('
            SELECT id, nom
            FROM type_vehicules
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);

        $type = $statement->fetch(PDO::FETCH_ASSOC);

        if ($type === false) {
            return null;
        }

        return $type;
    }

    public function findAll(): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                nom
            FROM type_vehicules
            ORDER BY nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO type_vehicules (nom)
            VALUES (:nom)
        ');

        return $statement->execute(['nom' => $name]);
    }

    public function update(int $id, string $name): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE type_vehicules
            SET nom = :nom
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $id,
            'nom' => $name,
        ]);
    }

    public function delete(int $id): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM type_vehicules WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }
}
