<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class ManagerUserController
{
    public function index(): void
    {
        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();

        $users = $userRepository->findAll();
        $roles = $roleRepository->isAvailable() ? $roleRepository->findAll() : [];
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
