<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;

final class ManagerAdminController
{
    public function menu(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_admin.php';
    }

    public function settings(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $sessionTimeout = $this->getSettingValue('manager_session_ttl_minutes', 'MANAGER_SESSION_TTL_MINUTES', '120');
        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = trim($this->getSettingValue('field_qr_token', 'FIELD_QR_TOKEN', ''));
        $pharmacyToken = trim($this->getSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', ''));
        $settingsStorage = $this->getSettingsStorageMode();

        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '');
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '');

        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_app_settings.php';
    }

    public function regenerateQrToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $target = strtolower(trim((string) ($_POST['target'] ?? '')));
        $allowedTargets = [
            'field' => 'field_qr_token',
            'pharmacy' => 'pharmacy_qr_token',
        ];

        if (!isset($allowedTargets[$target])) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=invalid_target');
        }

        $token = $this->generateToken();
        $settingKey = $allowedTargets[$target];
        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $saved = $settingRepository->set($settingKey, $token);
        if (!$saved) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=token_regenerated&target=' . rawurlencode($target));
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

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
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

    private function getSettingsStorageMode(): string
    {
        $repository = new AppSettingRepository();

        return $repository->isAvailable() ? 'database' : 'env';
    }
}
