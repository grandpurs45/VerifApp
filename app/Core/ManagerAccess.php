<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\RoleRepository;

final class ManagerAccess
{
    public static function hasPermission(string $roleCode, string $permissionCode): bool
    {
        $roleCode = self::normalizeRoleCode($roleCode);
        $permissionCode = trim($permissionCode);

        if ($roleCode === '' || $permissionCode === '') {
            return false;
        }

        if (in_array($roleCode, ['admin', 'administrateur'], true)) {
            return true;
        }

        if ($roleCode === 'responsable_materiel' && $permissionCode === 'users.manage') {
            return true;
        }

        $repository = new RoleRepository();

        if (!$repository->isAvailable()) {
            return self::hasLegacyPermission($roleCode, $permissionCode);
        }

        $permissions = $repository->findPermissionCodesByRoleCode($roleCode);
        if ($permissions === []) {
            return self::hasLegacyPermission($roleCode, $permissionCode);
        }

        return in_array($permissionCode, $permissions, true);
    }

    private static function normalizeRoleCode(string $roleCode): string
    {
        return strtolower(trim($roleCode));
    }

    private static function hasLegacyPermission(string $roleCode, string $permissionCode): bool
    {
        $legacyMap = [
            'responsable_materiel' => [
                'dashboard.view',
                'verifications.history',
                'anomalies.manage',
                'assets.manage',
                'pharmacy.manage',
                'users.manage',
            ],
            'resp_pharma' => [
                'dashboard.view',
                'pharmacy.manage',
            ],
            'responsable_pharmacie' => [
                'dashboard.view',
                'pharmacy.manage',
            ],
            'verificateur' => [
                'dashboard.view',
            ],
        ];

        return in_array($permissionCode, $legacyMap[$roleCode] ?? [], true);
    }
}
