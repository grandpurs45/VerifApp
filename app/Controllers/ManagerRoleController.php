<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class ManagerRoleController
{
    public function index(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        $repository = new RoleRepository();
        $roles = $repository->findAll();
        $catalog = RoleRepository::permissionCatalog();
        $selectedRoleId = isset($_GET['role_id']) ? (int) $_GET['role_id'] : 0;

        if ($selectedRoleId <= 0 && $roles !== []) {
            $selectedRoleId = (int) $roles[0]['id'];
        }

        $selectedRole = $selectedRoleId > 0 ? $repository->findById($selectedRoleId) : null;
        $selectedPermissions = $selectedRoleId > 0 ? $repository->findPermissionCodesByRoleId($selectedRoleId) : [];

        require dirname(__DIR__, 2) . '/public/views/manager_roles.php';
    }

    public function roleSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_roles&action=index');
        }

        $name = trim((string) ($_POST['nom'] ?? ''));
        if ($name === '') {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=invalid_role');
        }

        $code = strtolower(trim((string) ($_POST['code'] ?? '')));
        if ($code === '') {
            $code = $this->slugify($name);
        }

        if (!preg_match('/^[a-z0-9_]{3,50}$/', $code)) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=invalid_role_code');
        }

        $repository = new RoleRepository();
        $ok = $repository->create($code, $name);

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=role_save_failed');
        }

        $created = $repository->findByCode($code);
        $roleIdParam = $created !== null ? '&role_id=' . (int) $created['id'] : '';
        $this->redirect('/index.php?controller=manager_roles&action=index&success=role_saved' . $roleIdParam);
    }

    public function roleDelete(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_roles&action=index');
        }

        $roleId = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
        if ($roleId <= 0) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=invalid_role');
        }

        $repository = new RoleRepository();
        $ok = $repository->delete($roleId);

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=role_delete_forbidden');
        }

        $this->redirect('/index.php?controller=manager_roles&action=index&success=role_deleted');
    }

    public function permissionsSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_roles&action=index');
        }

        $roleId = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
        $permissions = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];

        if ($roleId <= 0) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=invalid_role');
        }

        $repository = new RoleRepository();
        $ok = $repository->updatePermissions($roleId, $permissions);

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_roles&action=index&error=permissions_save_failed&role_id=' . $roleId);
        }

        $this->redirect('/index.php?controller=manager_roles&action=index&success=permissions_saved&role_id=' . $roleId);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        $value = trim($value, '_');

        return $value;
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function isPlatformAdmin(): bool
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        if (!is_array($managerUser) || !isset($managerUser['id'])) {
            return false;
        }

        $userRepository = new UserRepository();
        $currentManager = $userRepository->findById((int) $managerUser['id']);
        if ($currentManager === null) {
            return false;
        }

        return strtolower((string) ($currentManager['role'] ?? '')) === 'admin'
            || (int) ($currentManager['id'] ?? 0) === 1;
    }
}
