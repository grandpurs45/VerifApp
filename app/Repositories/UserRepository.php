<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private ?bool $mustChangePasswordColumnExists = null;
    private ?bool $tableExists = null;
    private ?bool $membershipTableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

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

    public function findAllActiveByRoles(array $roles, ?int $caserneId = null): array
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
                u.id,
                u.nom,
                u.email,
                u.role
            FROM utilisateurs u
            ' . ($caserneId !== null ? 'INNER JOIN utilisateur_casernes uc ON uc.utilisateur_id = u.id AND uc.caserne_id = :caserne_id' : '') . '
            WHERE u.actif = 1
              AND u.role IN (' . implode(', ', $placeholders) . ')
            ORDER BY u.nom ASC
        ';

        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $mustChangeSelect = $this->hasMustChangePasswordColumn() ? 'must_change_password' : '0 AS must_change_password';
        $sql = '
            SELECT
                id,
                nom,
                email,
                role,
                actif,
                ' . $mustChangeSelect . ',
                created_at
            FROM utilisateurs
            ORDER BY actif DESC, nom ASC
        ';
        $statement = $connection->query($sql);

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, string $email, string $passwordHash, string $role, bool $active): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();

        if ($this->hasMustChangePasswordColumn()) {
            $statement = $connection->prepare('
                INSERT INTO utilisateurs (nom, email, mot_de_passe, role, actif, must_change_password)
                VALUES (:nom, :email, :mot_de_passe, :role, :actif, 1)
            ');
        } else {
            $statement = $connection->prepare('
                INSERT INTO utilisateurs (nom, email, mot_de_passe, role, actif)
                VALUES (:nom, :email, :mot_de_passe, :role, :actif)
            ');
        }

        return $statement->execute([
            'nom' => $name,
            'email' => $email,
            'mot_de_passe' => $passwordHash,
            'role' => $role,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function updateProfile(int $id, string $name, string $email, string $role, bool $active): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE utilisateurs
            SET
                nom = :nom,
                email = :email,
                role = :role,
                actif = :actif
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $id,
            'nom' => $name,
            'email' => $email,
            'role' => $role,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function deactivate(int $id): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function resetPasswordWithFlag(int $id, string $passwordHash): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();

        if ($this->hasMustChangePasswordColumn()) {
            $statement = $connection->prepare('
                UPDATE utilisateurs
                SET mot_de_passe = :mot_de_passe, must_change_password = 1
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

    public function findCaserneIdsByUserId(int $userId): array
    {
        if (!$this->hasMembershipTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT caserne_id
            FROM utilisateur_casernes
            WHERE utilisateur_id = :utilisateur_id
            ORDER BY is_default DESC, caserne_id ASC
        ');
        $statement->execute(['utilisateur_id' => $userId]);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_map('intval', $rows));
    }

    public function syncCasernes(int $userId, array $caserneIds): bool
    {
        if (!$this->hasMembershipTable()) {
            return false;
        }

        $caserneIds = array_values(array_unique(array_filter(array_map('intval', $caserneIds), static fn (int $id): bool => $id > 0)));
        if ($caserneIds === []) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $delete = $connection->prepare('DELETE FROM utilisateur_casernes WHERE utilisateur_id = :utilisateur_id');
            $delete->execute(['utilisateur_id' => $userId]);

            $insert = $connection->prepare('
                INSERT INTO utilisateur_casernes (utilisateur_id, caserne_id, is_default)
                VALUES (:utilisateur_id, :caserne_id, :is_default)
            ');

            foreach (array_values($caserneIds) as $index => $caserneId) {
                $insert->execute([
                    'utilisateur_id' => $userId,
                    'caserne_id' => $caserneId,
                    'is_default' => $index === 0 ? 1 : 0,
                ]);
            }

            $connection->commit();
            return true;
        } catch (\Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            return false;
        }
    }

    public function findLastInsertId(): int
    {
        $connection = Database::getConnection();
        return (int) $connection->lastInsertId();
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'utilisateurs'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
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

    private function hasMembershipTable(): bool
    {
        if ($this->membershipTableExists !== null) {
            return $this->membershipTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'utilisateur_casernes'");
            $this->membershipTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->membershipTableExists = false;
        }

        return $this->membershipTableExists;
    }
}
