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
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
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

    public function regenerateQrToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $target = strtolower(trim((string) ($_POST['target'] ?? '')));
        $allowedTargets = [
            'field' => 'FIELD_QR_TOKEN',
            'pharmacy' => 'PHARMACY_QR_TOKEN',
        ];

        if (!isset($allowedTargets[$target])) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=invalid_target');
        }

        $token = $this->generateToken();
        $envKey = $allowedTargets[$target];
        $envPath = dirname(__DIR__, 2) . '/.env';

        $saved = $this->setEnvValue($envPath, $envKey, $token);
        if (!$saved) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=env_write_failed');
        }

        // Keep runtime in sync without requiring a PHP restart.
        $_ENV[$envKey] = $token;

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

    private function setEnvValue(string $envPath, string $key, string $value): bool
    {
        if (!is_file($envPath) || !is_readable($envPath) || !is_writable($envPath)) {
            return false;
        }

        $content = @file_get_contents($envPath);
        if ($content === false) {
            return false;
        }

        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line = $key . '=' . $value;

        if (preg_match($pattern, $content) === 1) {
            $newContent = preg_replace($pattern, $line, $content, 1);
            if (!is_string($newContent)) {
                return false;
            }
        } else {
            $separator = str_ends_with($content, PHP_EOL) ? '' : PHP_EOL;
            $newContent = $content . $separator . $line . PHP_EOL;
        }

        return @file_put_contents($envPath, $newContent) !== false;
    }
}
