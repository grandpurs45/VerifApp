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
    private ?bool $membershipRoleColumnExists = null;
    private ?bool $verificationsUserColumnExists = null;
    private ?bool $anomaliesAssigneeColumnExists = null;

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

    public function updateActive(int $id, bool $active): bool
    {
        if (!$this->hasTable() || $id <= 0) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE utilisateurs
            SET actif = :actif
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $id,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function deletePermanently(int $id): bool
    {
        if (!$this->hasTable() || $id <= 0) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $fallbackUserId = $this->findFallbackUserId($id);

            // Preserve verification history while removing FK dependency.
            if ($this->hasVerificationsUserColumn()) {
                if ($this->isColumnNullable('verifications', 'utilisateur_id')) {
                    $clearVerifications = $connection->prepare('
                        UPDATE verifications
                        SET utilisateur_id = NULL
                        WHERE utilisateur_id = :id
                    ');
                    $clearVerifications->execute(['id' => $id]);
                } else {
                    if ($fallbackUserId <= 0) {
                        $connection->rollBack();
                        return false;
                    }
                    $reassignVerifications = $connection->prepare('
                        UPDATE verifications
                        SET utilisateur_id = :fallback_id
                        WHERE utilisateur_id = :id
                    ');
                    $reassignVerifications->execute([
                        'fallback_id' => $fallbackUserId,
                        'id' => $id,
                    ]);
                }
            }

            // Keep anomaly records but unassign/reassign deleted user.
            if ($this->hasAnomaliesAssigneeColumn()) {
                if ($this->isColumnNullable('anomalies', 'assigne_a')) {
                    $clearAnomalies = $connection->prepare('
                        UPDATE anomalies
                        SET assigne_a = NULL
                        WHERE assigne_a = :id
                    ');
                    $clearAnomalies->execute(['id' => $id]);
                } else {
                    if ($fallbackUserId <= 0) {
                        $connection->rollBack();
                        return false;
                    }
                    $reassignAnomalies = $connection->prepare('
                        UPDATE anomalies
                        SET assigne_a = :fallback_id
                        WHERE assigne_a = :id
                    ');
                    $reassignAnomalies->execute([
                        'fallback_id' => $fallbackUserId,
                        'id' => $id,
                    ]);
                }
            }

            $deleteUser = $connection->prepare('DELETE FROM utilisateurs WHERE id = :id');
            $deleteUser->execute(['id' => $id]);

            if ($deleteUser->rowCount() !== 1) {
                $connection->rollBack();
                return false;
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

    private function findFallbackUserId(int $excludeUserId): int
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT id
            FROM utilisateurs
            WHERE id <> :exclude_id
            ORDER BY CASE WHEN role = :admin_role THEN 0 ELSE 1 END, actif DESC, id ASC
            LIMIT 1
        ');
        $statement->execute([
            'exclude_id' => $excludeUserId,
            'admin_role' => 'admin',
        ]);
        $id = $statement->fetchColumn();

        return $id === false ? 0 : (int) $id;
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

    public function findMembershipsByUserId(int $userId): array
    {
        if (!$this->hasMembershipTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $selectRole = $this->hasMembershipRoleColumn() ? 'role_code' : 'NULL AS role_code';
        $statement = $connection->prepare('
            SELECT
                caserne_id,
                is_default,
                ' . $selectRole . '
            FROM utilisateur_casernes
            WHERE utilisateur_id = :utilisateur_id
            ORDER BY is_default DESC, caserne_id ASC
        ');
        $statement->execute(['utilisateur_id' => $userId]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === false) {
            return [];
        }

        return $rows;
    }

    public function detachCaserneFromUser(int $userId, int $caserneId): bool
    {
        if (!$this->hasMembershipTable() || $userId <= 0 || $caserneId <= 0) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $delete = $connection->prepare('
                DELETE FROM utilisateur_casernes
                WHERE utilisateur_id = :utilisateur_id
                  AND caserne_id = :caserne_id
            ');
            $delete->execute([
                'utilisateur_id' => $userId,
                'caserne_id' => $caserneId,
            ]);

            if ($delete->rowCount() <= 0) {
                $connection->rollBack();
                return false;
            }

            $remaining = $connection->prepare('
                SELECT caserne_id
                FROM utilisateur_casernes
                WHERE utilisateur_id = :utilisateur_id
                ORDER BY caserne_id ASC
            ');
            $remaining->execute(['utilisateur_id' => $userId]);
            $rows = $remaining->fetchAll(PDO::FETCH_COLUMN);

            if ($rows !== false && count($rows) > 0) {
                $resetDefault = $connection->prepare('
                    UPDATE utilisateur_casernes
                    SET is_default = 0
                    WHERE utilisateur_id = :utilisateur_id
                ');
                $resetDefault->execute(['utilisateur_id' => $userId]);

                $firstCaserneId = (int) $rows[0];
                $setDefault = $connection->prepare('
                    UPDATE utilisateur_casernes
                    SET is_default = 1
                    WHERE utilisateur_id = :utilisateur_id
                      AND caserne_id = :caserne_id
                ');
                $setDefault->execute([
                    'utilisateur_id' => $userId,
                    'caserne_id' => $firstCaserneId,
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

    public function syncCasernes(int $userId, array $caserneAssignments, string $defaultRole = 'verificateur'): bool
    {
        if (!$this->hasMembershipTable()) {
            return false;
        }

        $normalizedAssignments = [];
        foreach ($caserneAssignments as $key => $value) {
            if (is_int($key) || ctype_digit((string) $key)) {
                $caserneId = (int) $key;
                $roleCode = trim((string) $value);
                if ($caserneId > 0 && $roleCode !== '') {
                    $normalizedAssignments[$caserneId] = $roleCode;
                    continue;
                }
            }

            $caserneId = (int) $value;
            if ($caserneId > 0) {
                $normalizedAssignments[$caserneId] = $defaultRole;
            }
        }

        if ($normalizedAssignments === []) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $delete = $connection->prepare('DELETE FROM utilisateur_casernes WHERE utilisateur_id = :utilisateur_id');
            $delete->execute(['utilisateur_id' => $userId]);

            if ($this->hasMembershipRoleColumn()) {
                $insert = $connection->prepare('
                    INSERT INTO utilisateur_casernes (utilisateur_id, caserne_id, role_code, is_default)
                    VALUES (:utilisateur_id, :caserne_id, :role_code, :is_default)
                ');
            } else {
                $insert = $connection->prepare('
                    INSERT INTO utilisateur_casernes (utilisateur_id, caserne_id, is_default)
                    VALUES (:utilisateur_id, :caserne_id, :is_default)
                ');
            }

            $index = 0;
            foreach ($normalizedAssignments as $caserneId => $roleCode) {
                $params = [
                    'utilisateur_id' => $userId,
                    'caserne_id' => $caserneId,
                    'is_default' => $index === 0 ? 1 : 0,
                ];
                if ($this->hasMembershipRoleColumn()) {
                    $params['role_code'] = $roleCode;
                }
                $insert->execute($params);
                $index++;
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

    public function attachCaserneToAdmins(int $caserneId): bool
    {
        if (!$this->hasMembershipTable() || !$this->hasTable() || $caserneId <= 0) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            if ($this->hasMembershipRoleColumn()) {
                $insert = $connection->prepare('
                    INSERT IGNORE INTO utilisateur_casernes (utilisateur_id, caserne_id, role_code, is_default)
                    SELECT u.id, :caserne_id, :role_admin, 0
                    FROM utilisateurs u
                    WHERE u.role = :role_admin
                ');
            } else {
                $insert = $connection->prepare('
                    INSERT IGNORE INTO utilisateur_casernes (utilisateur_id, caserne_id, is_default)
                    SELECT u.id, :caserne_id, 0
                    FROM utilisateurs u
                    WHERE u.role = :role_admin
                ');
            }
            $insert->execute([
                'caserne_id' => $caserneId,
                'role_admin' => 'admin',
            ]);

            if ($this->hasMembershipRoleColumn()) {
                $backfill = $connection->prepare('
                    UPDATE utilisateur_casernes uc
                    INNER JOIN utilisateurs u ON u.id = uc.utilisateur_id
                    SET uc.role_code = :role_admin
                    WHERE u.role = :role_admin
                      AND (uc.role_code IS NULL OR uc.role_code = \'\')
                ');
                $backfill->execute(['role_admin' => 'admin']);
            }

            $defaultFix = $connection->prepare('
                UPDATE utilisateur_casernes uc
                INNER JOIN (
                    SELECT utilisateur_id, MIN(caserne_id) AS first_caserne_id
                    FROM utilisateur_casernes
                    GROUP BY utilisateur_id
                ) first_link ON first_link.utilisateur_id = uc.utilisateur_id
                SET uc.is_default = CASE WHEN uc.caserne_id = first_link.first_caserne_id THEN 1 ELSE 0 END
                WHERE uc.utilisateur_id IN (
                    SELECT id FROM utilisateurs WHERE role = :role_admin
                )
            ');
            $defaultFix->execute(['role_admin' => 'admin']);

            $connection->commit();
            return true;
        } catch (\Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            return false;
        }
    }

    public function setDefaultCaserneForUser(int $userId, int $caserneId): bool
    {
        if (!$this->hasMembershipTable() || $userId <= 0 || $caserneId <= 0) {
            return false;
        }

        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            $exists = $connection->prepare('
                SELECT 1
                FROM utilisateur_casernes
                WHERE utilisateur_id = :utilisateur_id
                  AND caserne_id = :caserne_id
                LIMIT 1
            ');
            $exists->execute([
                'utilisateur_id' => $userId,
                'caserne_id' => $caserneId,
            ]);
            if ($exists->fetchColumn() === false) {
                $connection->rollBack();
                return false;
            }

            $reset = $connection->prepare('
                UPDATE utilisateur_casernes
                SET is_default = 0
                WHERE utilisateur_id = :utilisateur_id
            ');
            $reset->execute(['utilisateur_id' => $userId]);

            $set = $connection->prepare('
                UPDATE utilisateur_casernes
                SET is_default = 1
                WHERE utilisateur_id = :utilisateur_id
                  AND caserne_id = :caserne_id
            ');
            $set->execute([
                'utilisateur_id' => $userId,
                'caserne_id' => $caserneId,
            ]);

            $connection->commit();
            return true;
        } catch (\Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            return false;
        }
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

    private function hasMembershipRoleColumn(): bool
    {
        if ($this->membershipRoleColumnExists !== null) {
            return $this->membershipRoleColumnExists;
        }

        if (!$this->hasMembershipTable()) {
            $this->membershipRoleColumnExists = false;
            return false;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM utilisateur_casernes LIKE 'role_code'");
            $this->membershipRoleColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->membershipRoleColumnExists = false;
        }

        return $this->membershipRoleColumnExists;
    }

    private function hasVerificationsUserColumn(): bool
    {
        if ($this->verificationsUserColumnExists !== null) {
            return $this->verificationsUserColumnExists;
        }

        $connection = Database::getConnection();
        try {
            $statement = $connection->query("SHOW COLUMNS FROM verifications LIKE 'utilisateur_id'");
            $this->verificationsUserColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->verificationsUserColumnExists = false;
        }

        return $this->verificationsUserColumnExists;
    }

    private function hasAnomaliesAssigneeColumn(): bool
    {
        if ($this->anomaliesAssigneeColumnExists !== null) {
            return $this->anomaliesAssigneeColumnExists;
        }

        $connection = Database::getConnection();
        try {
            $statement = $connection->query("SHOW COLUMNS FROM anomalies LIKE 'assigne_a'");
            $this->anomaliesAssigneeColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->anomaliesAssigneeColumnExists = false;
        }

        return $this->anomaliesAssigneeColumnExists;
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
            LIMIT 1
        ');
        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        $value = $statement->fetchColumn();

        return is_string($value) && strtoupper($value) === 'YES';
    }
}
