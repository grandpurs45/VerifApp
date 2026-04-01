<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CaserneRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class ManagerUserController
{
    public function index(): void
    {
        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();
        $caserneRepository = new CaserneRepository();
        $currentCaserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        $users = $userRepository->findAll();
        $filteredUsers = [];
        foreach ($users as $user) {
            $user['caserne_ids'] = $userRepository->findCaserneIdsByUserId((int) ($user['id'] ?? 0));
            if ($currentCaserneId <= 0 || in_array($currentCaserneId, (array) $user['caserne_ids'], true)) {
                $filteredUsers[] = $user;
            }
        }
        $users = $filteredUsers;
        $roles = $roleRepository->isAvailable() ? $roleRepository->findAll() : [];
        $casernes = $caserneRepository->findAllActive();
        $managerUser = $_SESSION['manager_user'] ?? null;

        if ($roles === []) {
            $roles = [
                ['code' => 'admin', 'nom' => 'Administrateur'],
                ['code' => 'responsable_materiel', 'nom' => 'Responsable materiel'],
                ['code' => 'verificateur', 'nom' => 'Verificateur'],
            ];
        }

        require dirname(__DIR__, 2) . '/public/views/manager_users.php';
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';
        $password = (string) ($_POST['password'] ?? '');
        $caserneIds = is_array($_POST['caserne_ids'] ?? null) ? array_map('intval', $_POST['caserne_ids']) : [];
        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $currentCaserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;
        if ($id > 0 && $id === $currentUserId && $currentCaserneId > 0 && !in_array($currentCaserneId, $caserneIds, true)) {
            $caserneIds[] = $currentCaserneId;
        }
        if ($caserneIds === []) {
            if ($currentCaserneId > 0) {
                $caserneIds = [$currentCaserneId];
            }
        }

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $role === '') {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $userRepository = new UserRepository();

        if ($id > 0) {
            $existing = $userRepository->findById($id);
            if ($existing === null) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }

            if ((string) ($existing['role'] ?? '') === 'admin' && !$active) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=admin_lock');
            }

            $ok = $userRepository->updateProfile($id, $name, $email, $role, $active);
            if (!$ok) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=save_failed');
            }

            if (!$userRepository->syncCasernes($id, $caserneIds)) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }

            if ($password !== '') {
                if (strlen($password) < 8) {
                    $this->redirect('/index.php?controller=manager_users&action=index&error=password_short');
                }

                $userRepository->resetPasswordWithFlag($id, password_hash($password, PASSWORD_BCRYPT));
            }

            $this->redirect('/index.php?controller=manager_users&action=index&success=updated');
        }

        if (strlen($password) < 8) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=password_short');
        }

        $ok = $userRepository->create(
            $name,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $role,
            $active
        );

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=create_failed');
        }

        $newUserId = $userRepository->findLastInsertId();
        if ($newUserId <= 0 || !$userRepository->syncCasernes($newUserId, $caserneIds)) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=created');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        if ($currentUserId > 0 && $currentUserId === $id) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=self_delete');
        }

        $userRepository = new UserRepository();
        $existing = $userRepository->findById($id);
        if ($existing === null) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        if ((string) ($existing['role'] ?? '') === 'admin') {
            $this->redirect('/index.php?controller=manager_users&action=index&error=admin_lock');
        }

        $ok = $userRepository->deactivate($id);
        if (!$ok) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=delete_failed');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=deleted');
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
