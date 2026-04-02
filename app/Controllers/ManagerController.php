<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\AnomalyRepository;
use App\Repositories\UserRepository;
use App\Repositories\VerificationRepository;

final class ManagerController
{
    public function dashboard(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        $verificationRepository = new VerificationRepository();
        $anomalyRepository = new AnomalyRepository();

        $stats = $verificationRepository->getDashboardStats($caserneId);
        $anomalyStats = $anomalyRepository->getStatusStats($caserneId);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $assignmentStats = $anomalyRepository->getAssignmentStats(
            is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : null,
            $caserneId
        );
        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = $this->getScopedSettingValue('field_qr_token', 'FIELD_QR_TOKEN', $caserneId, '');
        $pharmacyToken = $this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, '');
        $caserneParam = $caserneId !== null ? '&caserne_id=' . $caserneId : '';
        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '') . $caserneParam;
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '') . $caserneParam;
        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_dashboard.php';
    }

    public function forbidden(): void
    {
        require dirname(__DIR__, 2) . '/public/views/manager_forbidden.php';
    }

    public function account(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $updated = isset($_GET['updated']) ? (string) $_GET['updated'] : '';
        require dirname(__DIR__, 2) . '/public/views/manager_account.php';
    }

    public function accountSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager&action=account');
        }

        $managerUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        if ($managerUserId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=session_expired');
        }

        $name = trim((string) ($_POST['nom'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/index.php?controller=manager&action=account&error=invalid_profile');
        }

        $userRepository = new UserRepository();
        $currentUser = $userRepository->findById($managerUserId);
        if ($currentUser === null) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=session_expired');
        }

        $existingByEmail = $userRepository->findByEmail($email);
        if ($existingByEmail !== null && (int) $existingByEmail['id'] !== $managerUserId) {
            $this->redirect('/index.php?controller=manager&action=account&error=email_taken');
        }

        $saved = $userRepository->updateProfile(
            $managerUserId,
            $name,
            $email,
            (string) $currentUser['role'],
            (int) $currentUser['actif'] === 1
        );

        if (!$saved) {
            $this->redirect('/index.php?controller=manager&action=account&error=save_failed');
        }

        $_SESSION['manager_user'] = [
            'id' => $managerUserId,
            'nom' => $name,
            'email' => $email,
            'role' => (string) $currentUser['role'],
            'caserne_id' => isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0,
            'caserne_nom' => isset($_SESSION['manager_user']['caserne_nom']) ? (string) $_SESSION['manager_user']['caserne_nom'] : '',
        ];
        $_SESSION['manager_last_activity'] = time();

        $this->redirect('/index.php?controller=manager&action=account&updated=1');
    }

    private function resolvePublicBaseUrl(): string
    {
        $requestHost = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        if ($requestHost !== '') {
            $isHttps =
                (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
            $scheme = $isHttps ? 'https' : 'http';

            return $scheme . '://' . $requestHost;
        }

        return rtrim((string) (Env::get('APP_URL', '') ?? ''), '/');
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function getSettingValue(string $settingKey, string $envKey, string $default): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            $value = $repository->get($settingKey);
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    }

    private function getScopedSettingValue(string $settingKey, string $envKey, ?int $caserneId, string $default): string
    {
        if ($caserneId !== null && $caserneId > 0) {
            $scoped = $this->getSettingValue($settingKey . '_caserne_' . $caserneId, $envKey, '');
            if ($scoped !== '') {
                return $scoped;
            }
        }

        return $this->getSettingValue($settingKey, $envKey, $default);
    }

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }
}
