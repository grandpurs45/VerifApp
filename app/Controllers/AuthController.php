<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ManagerAccess;
use App\Repositories\UserRepository;

final class AuthController
{
    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }
        if (isset($_SESSION['manager_password_reset_user']['id'])) {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form');
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        require dirname(__DIR__, 2) . '/public/views/manager_login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=missing_fields');
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findByLoginIdentifier($identifier);

        if (
            $user === null ||
            (int) $user['actif'] !== 1 ||
            !ManagerAccess::hasPermission((string) $user['role'], 'dashboard.view') ||
            !password_verify($password, (string) $user['mot_de_passe'])
        ) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=invalid_credentials');
        }

        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            $_SESSION['manager_password_reset_user'] = [
                'id' => (int) $user['id'],
            ];
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form');
        }

        $_SESSION['manager_user'] = [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        $_SESSION['manager_last_activity'] = time();

        $this->redirect('/index.php?controller=manager&action=dashboard');
    }

    public function logout(): void
    {
        unset($_SESSION['manager_user']);
        unset($_SESSION['manager_password_reset_user']);
        unset($_SESSION['manager_last_activity']);
        $this->redirect('/index.php?controller=manager_auth&action=login_form&logged_out=1');
    }

    public function changePasswordForm(): void
    {
        $isForcedChange = isset($_SESSION['manager_password_reset_user']['id']);
        if (!$isForcedChange && !$this->isAuthenticated()) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        require dirname(__DIR__, 2) . '/public/views/manager_change_password.php';
    }

    public function changePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form');
        }

        $pendingUserId = (int) ($_SESSION['manager_password_reset_user']['id'] ?? 0);
        $managerUserId = (int) ($_SESSION['manager_user']['id'] ?? 0);
        $targetUserId = $pendingUserId > 0 ? $pendingUserId : $managerUserId;
        if ($targetUserId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=session_expired');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form&error=missing_fields');
        }

        if (strlen($newPassword) < 8) {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form&error=password_too_short');
        }

        if ($newPassword !== $confirmPassword) {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form&error=password_mismatch');
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findById($targetUserId);

        if ($user === null || !password_verify($currentPassword, (string) $user['mot_de_passe'])) {
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form&error=invalid_current_password');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $userRepository->updatePasswordAndClearFlag($targetUserId, $passwordHash);

        $_SESSION['manager_user'] = [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        $_SESSION['manager_last_activity'] = time();
        unset($_SESSION['manager_password_reset_user']);

        if ($pendingUserId > 0) {
            $this->redirect('/index.php?controller=manager&action=dashboard&password_changed=1');
        }

        $this->redirect('/index.php?controller=manager&action=account&password_changed=1');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['manager_user']) && is_array($_SESSION['manager_user']);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
