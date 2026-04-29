<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\UrlHelper;
use App\Core\ManagerAccess;
use App\Repositories\AppSettingRepository;
use App\Repositories\AnomalyRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PharmacyRepository;
use App\Repositories\UserRepository;
use App\Repositories\VerificationRepository;

final class ManagerController
{
    public function dashboard(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerRole = is_array($managerUser) ? (string) ($managerUser['role'] ?? '') : '';
        $canAnomalies = ManagerAccess::hasPermission($managerRole, 'anomalies.manage');
        $canHistory = ManagerAccess::hasPermission($managerRole, 'verifications.history');
        $canAssets = ManagerAccess::hasPermission($managerRole, 'assets.manage');
        $canPharmacy = ManagerAccess::hasPermission($managerRole, 'pharmacy.manage');
        $canUsers = ManagerAccess::hasPermission($managerRole, 'users.manage');
        $canVerificationDashboard = $canHistory;
        $dashboardConfig = $this->resolveDashboardConfig($caserneId);
        $canAnomaliesDashboard = $canAnomalies && $dashboardConfig['anomalies']['enabled'];
        $canVerificationDashboard = $canVerificationDashboard && $dashboardConfig['verifications']['enabled'];
        $canPharmacyDashboard = $canPharmacy && $dashboardConfig['pharmacy']['enabled'];
        $verificationEveningHour = (int) $this->getScopedSettingValue('verification_evening_hour', 'VERIFICATION_EVENING_HOUR', $caserneId, '18');
        if ($verificationEveningHour < 0 || $verificationEveningHour > 23) {
            $verificationEveningHour = 18;
        }

        $verificationRepository = new VerificationRepository();
        $anomalyRepository = new AnomalyRepository();

        $stats = $canVerificationDashboard ? $verificationRepository->getDashboardStats($caserneId, $verificationEveningHour) : [
            'total_today' => 0,
            'conformes_today' => 0,
            'non_conformes_today' => 0,
            'total_all' => 0,
            'month_slots_done' => 0,
            'month_slots_expected' => 0,
            'month_coverage_rate' => 0,
        ];
        $anomalyStats = $canAnomaliesDashboard ? $anomalyRepository->getStatusStats($caserneId) : [];
        $assignmentStats = $canAnomaliesDashboard
            ? $anomalyRepository->getAssignmentStats(
                is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : null,
                $caserneId
            )
            : ['mes_anomalies' => 0, 'non_assignees' => 0];
        $pharmacyStats = $canPharmacyDashboard
            ? (new PharmacyRepository())->getStats($caserneId ?? 0)
            : ['total_articles' => 0, 'alert_articles' => 0, 'outputs_last_7_days' => 0];
        $dashboardModules = [
            'anomalies' => $canAnomaliesDashboard ? (int) $dashboardConfig['anomalies']['order'] : 10000,
            'verifications' => $canVerificationDashboard ? (int) $dashboardConfig['verifications']['order'] : 10000,
            'pharmacy' => $canPharmacyDashboard ? (int) $dashboardConfig['pharmacy']['order'] : 10000,
        ];
        asort($dashboardModules, SORT_NUMERIC);
        $dashboardModuleOrder = [];
        foreach ($dashboardModules as $moduleKey => $order) {
            if ($order < 10000) {
                $dashboardModuleOrder[] = $moduleKey;
            }
        }

        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = $canVerificationDashboard ? $this->getScopedSettingValue('field_qr_token', 'FIELD_QR_TOKEN', $caserneId, '') : '';
        $pharmacyToken = $canPharmacy ? $this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, '') : '';
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
        $managerUserId = is_array($managerUser) ? (int) ($managerUser['id'] ?? 0) : 0;
        $caserneOptions = [];
        if (is_array($managerUser) && isset($managerUser['id'])) {
            $caserneRepository = new CaserneRepository();
            $caserneOptions = $caserneRepository->findByUserId((int) $managerUser['id']);
        }
        $notificationRepository = new NotificationRepository();
        $notificationCatalog = NotificationRepository::eventCatalog();
        $notificationSubscriptions = $notificationRepository->findSubscriptionsByUser($managerUserId);
        $notificationsAvailable = $notificationRepository->isAvailable();
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $updated = isset($_GET['updated']) ? (string) $_GET['updated'] : '';
        $updatedNotif = isset($_GET['updated_notif']) ? (string) $_GET['updated_notif'] : '';
        $passwordError = isset($_GET['password_error']) ? (string) $_GET['password_error'] : '';
        $passwordChanged = isset($_GET['password_changed']) ? (string) $_GET['password_changed'] : '';
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
        $defaultCaserneId = isset($_POST['default_caserne_id']) ? (int) $_POST['default_caserne_id'] : 0;

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

        if ($defaultCaserneId > 0) {
            if (!$userRepository->setDefaultCaserneForUser($managerUserId, $defaultCaserneId)) {
                $this->redirect('/index.php?controller=manager&action=account&error=default_caserne_invalid');
            }

            $caserneRepository = new CaserneRepository();
            $defaultCaserne = $caserneRepository->findByIdForUser($defaultCaserneId, $managerUserId);
            if ($defaultCaserne !== null) {
                $_SESSION['manager_user']['caserne_id'] = (int) ($defaultCaserne['id'] ?? 0);
                $_SESSION['manager_user']['caserne_nom'] = (string) ($defaultCaserne['nom'] ?? '');
                $roleCode = trim((string) ($defaultCaserne['role_code'] ?? ''));
                if ($roleCode !== '') {
                    $_SESSION['manager_user']['role'] = $roleCode;
                }
            }
        }

        $_SESSION['manager_user'] = [
            'id' => $managerUserId,
            'nom' => $name,
            'email' => $email,
            'role' => (string) ($_SESSION['manager_user']['role'] ?? $currentUser['role']),
            'global_role' => (string) ($_SESSION['manager_user']['global_role'] ?? $currentUser['role']),
            'is_platform_admin' => (int) ($_SESSION['manager_user']['is_platform_admin'] ?? (strtolower((string) $currentUser['role']) === 'admin' ? 1 : 0)),
            'caserne_id' => isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0,
            'caserne_nom' => isset($_SESSION['manager_user']['caserne_nom']) ? (string) $_SESSION['manager_user']['caserne_nom'] : '',
        ];
        $_SESSION['manager_last_activity'] = time();

        $this->redirect('/index.php?controller=manager&action=account&updated=1');
    }

    private function resolvePublicBaseUrl(): string
    {
        return UrlHelper::resolvePublicBaseUrl((string) (Env::get('APP_URL', '') ?? ''));
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

    /**
     * @return array<string, array{enabled: bool, order: int}>
     */
    private function resolveDashboardConfig(?int $caserneId): array
    {
        return [
            'anomalies' => [
                'enabled' => $this->getScopedSettingValue('dashboard_anomalies_enabled', 'DASHBOARD_ANOMALIES_ENABLED', $caserneId, '1') !== '0',
                'order' => $this->readDashboardOrder('dashboard_anomalies_order', $caserneId, 10),
            ],
            'verifications' => [
                'enabled' => $this->getScopedSettingValue('dashboard_verifications_enabled', 'DASHBOARD_VERIFICATIONS_ENABLED', $caserneId, '1') !== '0',
                'order' => $this->readDashboardOrder('dashboard_verifications_order', $caserneId, 20),
            ],
            'pharmacy' => [
                'enabled' => $this->getScopedSettingValue('dashboard_pharmacy_enabled', 'DASHBOARD_PHARMACY_ENABLED', $caserneId, '1') !== '0',
                'order' => $this->readDashboardOrder('dashboard_pharmacy_order', $caserneId, 30),
            ],
        ];
    }

    private function readDashboardOrder(string $settingKey, ?int $caserneId, int $default): int
    {
        $value = $this->getScopedSettingValue($settingKey, strtoupper($settingKey), $caserneId, (string) $default);
        if ($value === '' || ctype_digit($value) === false) {
            return $default;
        }

        $parsed = (int) $value;
        if ($parsed < 1 || $parsed > 999) {
            return $default;
        }

        return $parsed;
    }
}
