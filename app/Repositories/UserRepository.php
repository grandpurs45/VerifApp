<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    public function findById(int $id): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                nom,
                email,
                mot_de_passe,
                role,
                actif
            FROM utilisateurs
            WHERE id = :id
            LIMIT 1
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['id' => $id]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $user;
    }

    public function findByEmail(string $email): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                nom,
                email,
                mot_de_passe,
                role,
                actif
            FROM utilisateurs
            WHERE email = :email
            LIMIT 1
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['email' => $email]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $user;
    }

    public function findAllActiveByRoles(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        $connection = Database::getConnection();

        $placeholders = [];
        $params = [];

        foreach (array_values($roles) as $index => $role) {
            $key = 'role_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $role;
        }

        $sql = '
            SELECT
                id,
                nom,
                email,
                role
            FROM utilisateurs
            WHERE actif = 1
              AND role IN (' . implode(', ', $placeholders) . ')
            ORDER BY nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
