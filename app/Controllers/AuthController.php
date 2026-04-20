<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ManagerAccess;
use App\Core\PasswordPolicy;
use App\Repositories\CaserneRepository;
use App\Repositories\LoginAttemptRepository;
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
        if (isset($_SESSION['manager_caserne_pending']['user_id'])) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
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
        $ipAddress = $this->resolveClientIp();

        if ($identifier === '' || $password === '') {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=missing_fields');
        }

        $loginAttemptRepository = new LoginAttemptRepository();
        $lockStatus = $loginAttemptRepository->checkLock($identifier, $ipAddress);
        if (($lockStatus['locked'] ?? false) === true) {
            $retryIn = max(1, (int) ($lockStatus['remaining_seconds'] ?? 0));
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=too_many_attempts&retry_in=' . $retryIn);
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findByLoginIdentifier($identifier);

        if (
            $user === null ||
            (int) $user['actif'] !== 1 ||
            !password_verify($password, (string) $user['mot_de_passe'])
        ) {
            $failure = $loginAttemptRepository->registerFailure($identifier, $ipAddress);
            if (($failure['locked'] ?? false) === true) {
                $retryIn = max(1, (int) ($failure['remaining_seconds'] ?? 0));
                $this->redirect('/index.php?controller=manager_auth&action=login_form&error=too_many_attempts&retry_in=' . $retryIn);
            }
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=invalid_credentials');
        }

        $loginAttemptRepository->clearOnSuccess($identifier, $ipAddress);

        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            $_SESSION['manager_password_reset_user'] = [
                'id' => (int) $user['id'],
            ];
            $this->redirect('/index.php?controller=manager_auth&action=change_password_form');
        }

        $this->startManagerSessionForUser((int) $user['id'], false);
    }

    public function logout(): void
    {
        unset($_SESSION['manager_user']);
        unset($_SESSION['manager_password_reset_user']);
        unset($_SESSION['manager_caserne_pending']);
        unset($_SESSION['manager_last_activity']);
        $this->redirect('/index.php?controller=manager_auth&action=login_form&logged_out=1');
    }

    public function changePasswordForm(): void
    {
        $isForcedChange = isset($_SESSION['manager_password_reset_user']['id']);
        if (!$isForcedChange && !$this->isAuthenticated()) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }
        if (!$isForcedChange && $this->isAuthenticated()) {
            $this->redirect('/index.php?controller=manager&action=account');
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
        $isForcedChange = $pendingUserId > 0;
        $managerUserId = (int) ($_SESSION['manager_user']['id'] ?? 0);
        $targetUserId = $pendingUserId > 0 ? $pendingUserId : $managerUserId;
        if ($targetUserId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=session_expired');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->redirect(
                $isForcedChange
                    ? '/index.php?controller=manager_auth&action=change_password_form&error=missing_fields'
                    : '/index.php?controller=manager&action=account&password_error=missing_fields'
            );
        }

        $policy = PasswordPolicy::validate($newPassword);
        if (($policy['ok'] ?? false) !== true) {
            $this->redirect(
                $isForcedChange
                    ? '/index.php?controller=manager_auth&action=change_password_form&error=password_policy'
                    : '/index.php?controller=manager&action=account&password_error=password_policy'
            );
        }

        if ($newPassword !== $confirmPassword) {
            $this->redirect(
                $isForcedChange
                    ? '/index.php?controller=manager_auth&action=change_password_form&error=password_mismatch'
                    : '/index.php?controller=manager&action=account&password_error=password_mismatch'
            );
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findById($targetUserId);

        if ($user === null || !password_verify($currentPassword, (string) $user['mot_de_passe'])) {
            $this->redirect(
                $isForcedChange
                    ? '/index.php?controller=manager_auth&action=change_password_form&error=invalid_current_password'
                    : '/index.php?controller=manager&action=account&password_error=invalid_current_password'
            );
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $userRepository->updatePasswordAndClearFlag($targetUserId, $passwordHash);

        unset($_SESSION['manager_password_reset_user']);

        if ($pendingUserId > 0) {
            $this->startManagerSessionForUser($targetUserId, true);
        }

        $this->redirect('/index.php?controller=manager&action=account&password_changed=1');
    }

    public function selectCaserneForm(): void
    {
        $pending = $_SESSION['manager_caserne_pending'] ?? null;
        if (!is_array($pending) || !isset($pending['user_id']) || !isset($pending['casernes']) || !is_array($pending['casernes'])) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $casernes = $pending['casernes'];
        $userName = (string) ($pending['nom'] ?? '');

        require dirname(__DIR__, 2) . '/public/views/manager_select_caserne.php';
    }

    public function selectCaserne(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
        }

        $pending = $_SESSION['manager_caserne_pending'] ?? null;
        if (!is_array($pending) || !isset($pending['user_id'])) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $caserneId = isset($_POST['caserne_id']) ? (int) $_POST['caserne_id'] : 0;
        if ($caserneId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form&error=missing_caserne');
        }

        $selectedCaserne = null;
        foreach (($pending['casernes'] ?? []) as $caserne) {
            if ((int) ($caserne['id'] ?? 0) === $caserneId) {
                $selectedCaserne = $caserne;
                break;
            }
        }

        if (!is_array($selectedCaserne)) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form&error=invalid_caserne');
        }

        $sessionUser = [
            'id' => (int) $pending['user_id'],
            'nom' => (string) ($pending['nom'] ?? ''),
            'email' => (string) ($pending['email'] ?? ''),
            'role' => (string) ($pending['role'] ?? ''),
        ];
        $this->createAuthenticatedSession($sessionUser, $selectedCaserne);

        $afterPasswordChange = ((int) ($pending['after_password_change'] ?? 0) === 1);
        unset($_SESSION['manager_caserne_pending']);

        $this->redirect('/index.php?controller=manager&action=dashboard' . ($afterPasswordChange ? '&password_changed=1' : ''));
    }

    public function switchCaserne(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $managerUser = $_SESSION['manager_user'] ?? null;
        if (!is_array($managerUser) || !isset($managerUser['id'])) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form');
        }

        $caserneId = isset($_POST['caserne_id']) ? (int) $_POST['caserne_id'] : 0;
        if ($caserneId <= 0) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $caserneRepository = new CaserneRepository();
        $caserne = $caserneRepository->findByIdForUser($caserneId, (int) $managerUser['id']);
        if ($caserne === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $resolvedRole = $this->resolveRoleForCaserne($managerUser, $caserne);
        if (!ManagerAccess::hasPermission($resolvedRole, 'dashboard.view')) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $_SESSION['manager_user']['caserne_id'] = (int) $caserne['id'];
        $_SESSION['manager_user']['caserne_nom'] = (string) $caserne['nom'];
        $_SESSION['manager_user']['role'] = $resolvedRole;
        $_SESSION['manager_last_activity'] = time();

        $this->redirect('/index.php?controller=manager&action=dashboard');
    }

    public function ping(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'expired' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['manager_last_activity'] = time();
        echo json_encode(['ok' => true, 'ts' => (int) $_SESSION['manager_last_activity']], JSON_UNESCAPED_UNICODE);
        exit;
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

    private function startManagerSessionForUser(int $userId, bool $afterPasswordChange): void
    {
        $userRepository = new UserRepository();
        $caserneRepository = new CaserneRepository();
        $user = $userRepository->findById($userId);

        if ($user === null || (int) ($user['actif'] ?? 0) !== 1) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=invalid_credentials');
        }

        $casernes = $caserneRepository->findByUserId($userId);
        if ($casernes === []) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=no_caserne');
        }

        $accessibleCasernes = [];
        foreach ($casernes as $caserne) {
            $resolvedRole = $this->resolveRoleForCaserne($user, $caserne);
            if (ManagerAccess::hasPermission($resolvedRole, 'dashboard.view')) {
                $caserne['resolved_role'] = $resolvedRole;
                $accessibleCasernes[] = $caserne;
            }
        }

        if ($accessibleCasernes === []) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=invalid_credentials');
        }

        if (count($accessibleCasernes) === 1) {
            $this->createAuthenticatedSession($user, $accessibleCasernes[0]);
            $this->redirect('/index.php?controller=manager&action=dashboard' . ($afterPasswordChange ? '&password_changed=1' : ''));
        }

        $_SESSION['manager_caserne_pending'] = [
            'user_id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'casernes' => $accessibleCasernes,
            'after_password_change' => $afterPasswordChange ? 1 : 0,
        ];

        $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
    }

    private function createAuthenticatedSession(array $user, array $caserne): void
    {
        $resolvedRole = $this->resolveRoleForCaserne($user, $caserne);
        $globalRole = trim((string) ($user['role'] ?? ''));
        $isPlatformAdmin = strtolower($globalRole) === 'admin' || (int) ($user['id'] ?? 0) === 1;
        $_SESSION['manager_user'] = [
            'id' => (int) ($user['id'] ?? 0),
            'nom' => (string) ($user['nom'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => $resolvedRole,
            'global_role' => $globalRole,
            'is_platform_admin' => $isPlatformAdmin ? 1 : 0,
            'caserne_id' => (int) ($caserne['id'] ?? 0),
            'caserne_nom' => (string) ($caserne['nom'] ?? ''),
        ];
        $_SESSION['manager_last_activity'] = time();
        unset($_SESSION['manager_password_reset_user']);
        unset($_SESSION['manager_caserne_pending']);
    }

    private function resolveRoleForCaserne(array $user, array $caserne): string
    {
        $role = trim((string) ($caserne['resolved_role'] ?? $caserne['role_code'] ?? $user['role'] ?? ''));
        return $role;
    }

    private function resolveClientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            $parts = explode(',', $candidate);
            foreach ($parts as $part) {
                $ip = trim($part);
                if ($ip !== '') {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
