<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class RoleRepository
{
    private ?bool $rolesTableExists = null;
    private ?bool $rolePermissionsTableExists = null;

    /**
     * @return array<string, string>
     */
    public static function permissionCatalog(): array
    {
        return [
            'dashboard.view' => 'Acces dashboard gestionnaire',
            'verifications.history' => 'Historique et rapports de verifications',
            'anomalies.manage' => 'Consultation et suivi des anomalies',
            'assets.manage' => 'Configuration parc, zones, postes et materiel',
            'pharmacy.manage' => 'Gestion du module pharmacie',
            'users.manage' => 'Gestion des roles et acces',
        ];
    }

    public function isAvailable(): bool
    {
        return $this->hasRolesTable() && $this->hasRolePermissionsTable();
    }

    public function findAll(): array
    {
        if (!$this->hasRolesTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                r.id,
                r.code,
                r.nom,
                r.actif,
                r.is_system,
                COUNT(rp.permission_code) AS total_permissions
            FROM roles r
            LEFT JOIN role_permissions rp ON rp.role_id = r.id
            GROUP BY r.id, r.code, r.nom, r.actif, r.is_system
            ORDER BY r.is_system DESC, r.nom ASC
        ';
        $statement = $connection->query($sql);

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByCode(string $code): ?array
    {
        if (!$this->hasRolesTable()) {
            return null;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT id, code, nom, actif, is_system
            FROM roles
            WHERE code = :code
            LIMIT 1
        ');
        $statement->execute(['code' => $code]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        if (!$this->hasRolesTable()) {
            return null;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT id, code, nom, actif, is_system
            FROM roles
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $code, string $name): bool
    {
        if (!$this->hasRolesTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO roles (code, nom, actif, is_system)
            VALUES (:code, :nom, 1, 0)
        ');

        return $statement->execute([
            'code' => $code,
            'nom' => $name,
        ]);
    }

    public function delete(int $id): bool
    {
        if (!$this->hasRolesTable()) {
            return false;
        }

        $role = $this->findById($id);
        if ($role === null || (int) ($role['is_system'] ?? 0) === 1 || (string) ($role['code'] ?? '') === 'admin') {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM roles WHERE id = :id LIMIT 1');

        return $statement->execute(['id' => $id]);
    }

    public function findPermissionCodesByRoleId(int $roleId): array
    {
        if (!$this->hasRolePermissionsTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT permission_code
            FROM role_permissions
            WHERE role_id = :role_id
            ORDER BY permission_code ASC
        ');
        $statement->execute(['role_id' => $roleId]);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_map(static fn ($value): string => (string) $value, $rows ?: []);
    }

    public function findPermissionCodesByRoleCode(string $roleCode): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT rp.permission_code
            FROM roles r
            INNER JOIN role_permissions rp ON rp.role_id = r.id
            WHERE r.code = :code AND r.actif = 1
            ORDER BY rp.permission_code ASC
        ');
        $statement->execute(['code' => $roleCode]);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_map(static fn ($value): string => (string) $value, $rows ?: []);
    }

    public function updatePermissions(int $roleId, array $permissionCodes): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $role = $this->findById($roleId);
        if ($role === null || (int) ($role['is_system'] ?? 0) === 1 || (string) ($role['code'] ?? '') === 'admin') {
            return false;
        }

        $allowed = array_keys(self::permissionCatalog());
        $filtered = [];
        foreach ($permissionCodes as $permissionCode) {
            $value = trim((string) $permissionCode);
            if ($value !== '' && in_array($value, $allowed, true)) {
                $filtered[$value] = $value;
            }
        }

        $connection = Database::getConnection();
        $connection->beginTransaction();

        try {
            $deleteStatement = $connection->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
            $deleteStatement->execute(['role_id' => $roleId]);

            if ($filtered !== []) {
                $insertStatement = $connection->prepare('
                    INSERT INTO role_permissions (role_id, permission_code)
                    VALUES (:role_id, :permission_code)
                ');

                foreach ($filtered as $permissionCode) {
                    $insertStatement->execute([
                        'role_id' => $roleId,
                        'permission_code' => $permissionCode,
                    ]);
                }
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

    private function hasRolesTable(): bool
    {
        if ($this->rolesTableExists !== null) {
            return $this->rolesTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'roles'");
            $this->rolesTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->rolesTableExists = false;
        }

        return $this->rolesTableExists;
    }

    private function hasRolePermissionsTable(): bool
    {
        if ($this->rolePermissionsTableExists !== null) {
            return $this->rolePermissionsTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'role_permissions'");
            $this->rolePermissionsTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->rolePermissionsTableExists = false;
        }

        return $this->rolePermissionsTableExists;
    }
}
