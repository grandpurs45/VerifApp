<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
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
}
