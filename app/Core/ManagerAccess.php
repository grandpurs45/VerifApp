<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\RoleRepository;

final class ManagerAccess
{
    public static function hasPermission(string $roleCode, string $permissionCode): bool
    {
        $roleCode = trim($roleCode);
        $permissionCode = trim($permissionCode);

        if ($roleCode === '' || $permissionCode === '') {
            return false;
        }

        if ($roleCode === 'admin') {
            return true;
        }

        if ($roleCode === 'responsable_materiel' && $permissionCode === 'users.manage') {
            return true;
        }

        $repository = new RoleRepository();

        if (!$repository->isAvailable()) {
            return in_array($roleCode, ['admin', 'responsable_materiel'], true);
        }

        $permissions = $repository->findPermissionCodesByRoleCode($roleCode);

        return in_array($permissionCode, $permissions, true);
    }
}
