<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CaserneRepository;
use App\Repositories\UserRepository;

final class FieldAuthController
{
    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/index.php?controller=home&action=index');
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        require dirname(__DIR__, 2) . '/public/views/field_login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=field_auth&action=login_form');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->redirect('/index.php?controller=field_auth&action=login_form&error=missing_fields');
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findByEmail($email);

        if (
            $user === null ||
            (int) $user['actif'] !== 1 ||
            !password_verify($password, (string) $user['mot_de_passe'])
        ) {
            $this->redirect('/index.php?controller=field_auth&action=login_form&error=invalid_credentials');
        }

        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=password_change_required');
        }

        $resolvedRole = (string) $user['role'];
        $caserneId = isset($_SESSION['field_caserne_id']) ? (int) $_SESSION['field_caserne_id'] : 0;
        if ($caserneId > 0) {
            $caserneRepository = new CaserneRepository();
            $membership = $caserneRepository->findByIdForUser($caserneId, (int) $user['id']);
            if ($membership === null) {
                $this->redirect('/index.php?controller=field_auth&action=login_form&error=caserne_forbidden');
            }
            $memberRole = trim((string) ($membership['role_code'] ?? ''));
            if ($memberRole !== '') {
                $resolvedRole = $memberRole;
            }
        }

        if (!in_array($resolvedRole, ['verificateur', 'admin'], true)) {
            $this->redirect('/index.php?controller=field_auth&action=login_form&error=invalid_credentials');
        }

        $_SESSION['field_user'] = [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => $resolvedRole,
        ];

        $this->redirect('/index.php?controller=home&action=index');
    }

    public function logout(): void
    {
        unset($_SESSION['field_user']);
        $this->redirect('/index.php?controller=field_auth&action=login_form&logged_out=1');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['field_user']) && is_array($_SESSION['field_user']);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
