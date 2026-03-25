<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private ?bool $mustChangePasswordColumnExists = null;

    public function findById(int $id): ?array
    {
        $connection = Database::getConnection();
        $mustChangeSelect = $this->hasMustChangePasswordColumn() ? 'must_change_password' : '0 AS must_change_password';

        $sql = '
            SELECT
                id,
                nom,
                email,
                mot_de_passe,
                role,
                actif,
                ' . $mustChangeSelect . '
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
        $mustChangeSelect = $this->hasMustChangePasswordColumn() ? 'must_change_password' : '0 AS must_change_password';

        $sql = '
            SELECT
                id,
                nom,
                email,
                mot_de_passe,
                role,
                actif,
                ' . $mustChangeSelect . '
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

    public function findByLoginIdentifier(string $identifier): ?array
    {
        $connection = Database::getConnection();
        $mustChangeSelect = $this->hasMustChangePasswordColumn() ? 'must_change_password' : '0 AS must_change_password';

        $sql = '
            SELECT
                id,
                nom,
                email,
                mot_de_passe,
                role,
                actif,
                ' . $mustChangeSelect . '
            FROM utilisateurs
            WHERE email = :identifier OR nom = :identifier
            ORDER BY id ASC
            LIMIT 1
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['identifier' => $identifier]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $user;
    }

    public function updatePasswordAndClearFlag(int $id, string $passwordHash): bool
    {
        $connection = Database::getConnection();

        if ($this->hasMustChangePasswordColumn()) {
            $statement = $connection->prepare('
                UPDATE utilisateurs
                SET mot_de_passe = :mot_de_passe, must_change_password = 0
                WHERE id = :id
            ');
        } else {
            $statement = $connection->prepare('
                UPDATE utilisateurs
                SET mot_de_passe = :mot_de_passe
                WHERE id = :id
            ');
        }

        return $statement->execute([
            'id' => $id,
            'mot_de_passe' => $passwordHash,
        ]);
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

    private function hasMustChangePasswordColumn(): bool
    {
        if ($this->mustChangePasswordColumnExists !== null) {
            return $this->mustChangePasswordColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM utilisateurs LIKE 'must_change_password'");
            $this->mustChangePasswordColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->mustChangePasswordColumnExists = false;
        }

        return $this->mustChangePasswordColumnExists;
    }
}
