<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;

final class AuthController
{
    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        require dirname(__DIR__, 2) . '/public/views/manager_login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=missing_fields');
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findByEmail($email);

        if (
            $user === null ||
            (int) $user['actif'] !== 1 ||
            !in_array((string) $user['role'], ['responsable_materiel', 'admin'], true) ||
            !password_verify($password, (string) $user['mot_de_passe'])
        ) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=invalid_credentials');
        }

        $_SESSION['manager_user'] = [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];

        $this->redirect('/index.php?controller=manager&action=dashboard');
    }

    public function logout(): void
    {
        unset($_SESSION['manager_user']);
        $this->redirect('/index.php?controller=manager_auth&action=login_form&logged_out=1');
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
