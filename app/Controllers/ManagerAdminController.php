<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\UserRepository;
use Throwable;

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
        $caserneId = $this->resolveManagerCaserneId();
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $sessionTimeout = $this->getSettingValue('manager_session_ttl_minutes', 'MANAGER_SESSION_TTL_MINUTES', '120');
        $verificationEveningHour = $this->getScopedSettingValue('verification_evening_hour', 'VERIFICATION_EVENING_HOUR', $caserneId, '18');
        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = trim($this->getScopedSettingValue('field_qr_token', 'FIELD_QR_TOKEN', $caserneId, ''));
        $pharmacyToken = trim($this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, ''));
        $settingsStorage = $this->getSettingsStorageMode();
        $caserneRepository = new CaserneRepository();
        $casernes = $caserneRepository->findAll();

        $caserneParam = $caserneId !== null ? '&caserne_id=' . $caserneId : '';
        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '') . $caserneParam;
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '') . $caserneParam;

        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_app_settings.php';
    }

    public function verificationTimingSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timing_save_failed');
        }

        $hourRaw = trim((string) ($_POST['verification_evening_hour'] ?? ''));
        if ($hourRaw === '' || ctype_digit($hourRaw) === false) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timing_invalid');
        }

        $hour = (int) $hourRaw;
        if ($hour < 0 || $hour > 23) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timing_invalid');
        }

        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $key = 'verification_evening_hour_caserne_' . $caserneId;
        $saved = $settingRepository->set($key, (string) $hour);
        if (!$saved) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timing_save_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=timing_saved');
    }

    public function caserneSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $code = strtolower(trim((string) ($_POST['code'] ?? '')));
        $code = preg_replace('/[^a-z0-9_\\-]/', '_', $code ?? '');
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($nom === '' || $code === '') {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_invalid');
        }

        $repository = new CaserneRepository();

        try {
            if ($id > 0) {
                $existing = $repository->findById($id);
                if ($existing === null) {
                    $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_invalid');
                }

                if ((int) ($existing['actif'] ?? 0) === 1 && !$active && $repository->countActive() <= 1) {
                    $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_last_active');
                }

                $ok = $repository->update($id, $nom, $code, $active);
                if (!$ok) {
                    $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_save_failed');
                }

                $managerCaserneId = $this->resolveManagerCaserneId();
                if ($managerCaserneId !== null && $managerCaserneId === $id) {
                    $_SESSION['manager_user']['caserne_nom'] = $nom;
                }

                $this->redirect('/index.php?controller=manager_admin&action=settings&success=caserne_updated');
            }

            $ok = $repository->create($nom, $code, $active);
            if (!$ok) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_save_failed');
            }

            $newCaserneId = $repository->findLastInsertId();
            if ($newCaserneId > 0) {
                $userRepository = new UserRepository();
                $userRepository->attachCaserneToAdmins($newCaserneId);
            }

            $this->redirect('/index.php?controller=manager_admin&action=settings&success=caserne_created');
        } catch (Throwable $throwable) {
            $message = strtolower($throwable->getMessage());
            if (str_contains($message, 'duplicate') || str_contains($message, 'unique')) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_duplicate');
            }
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=caserne_save_failed');
        }
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

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=invalid_target');
        }

        $token = $this->generateToken();
        $settingKey = $allowedTargets[$target] . '_caserne_' . $caserneId;
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

    private function getSettingsStorageMode(): string
    {
        $repository = new AppSettingRepository();

        return $repository->isAvailable() ? 'database' : 'env';
    }

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }
}
