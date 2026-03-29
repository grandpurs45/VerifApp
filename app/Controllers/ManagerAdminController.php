<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;

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
        $sessionTimeout = (string) (Env::get('MANAGER_SESSION_TTL_MINUTES', '120') ?? '120');
        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = trim((string) (Env::get('FIELD_QR_TOKEN', '') ?? ''));
        $pharmacyToken = trim((string) (Env::get('PHARMACY_QR_TOKEN', '') ?? ''));

        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '');
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '');

        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_app_settings.php';
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
}
