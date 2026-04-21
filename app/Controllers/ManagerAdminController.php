<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\PasswordPolicy;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\LoginEventRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use Throwable;

final class ManagerAdminController
{
    public function menu(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $isPlatformAdmin = $this->isPlatformAdmin();

        require dirname(__DIR__, 2) . '/public/views/manager_admin.php';
    }

    public function settings(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $isPlatformAdmin = $this->isPlatformAdmin();
        $caserneId = $this->resolveManagerCaserneId();
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $appTimezone = $this->getSettingValue('app_timezone', 'APP_TIMEZONE', 'Europe/Paris');
        if (!in_array($appTimezone, timezone_identifiers_list(), true)) {
            $appTimezone = 'Europe/Paris';
        }
        $appDebugMode = $this->getSettingValue('app_debug_mode', 'APP_DEBUG', '0') === '1';
        $sessionTimeout = $this->getScopedSettingValue('manager_session_ttl_minutes', 'MANAGER_SESSION_TTL_MINUTES', $caserneId, '120');
        $verificationEveningHour = $this->getScopedSettingValue('verification_evening_hour', 'VERIFICATION_EVENING_HOUR', $caserneId, '18');
        $terrainMobileDensity = $this->getScopedSettingValue('terrain_mobile_density', 'TERRAIN_MOBILE_DENSITY', $caserneId, 'normal');
        if (!in_array($terrainMobileDensity, ['compact', 'normal'], true)) {
            $terrainMobileDensity = 'normal';
        }
        $terrainStickyProgressEnabled = $this->getScopedSettingValue('terrain_sticky_progress_enabled', 'TERRAIN_STICKY_PROGRESS_ENABLED', $caserneId, '1') !== '0';
        $terrainDraftEnabled = $this->getScopedSettingValue('terrain_draft_enabled', 'TERRAIN_DRAFT_ENABLED', $caserneId, '1') !== '0';
        $terrainDraftTtlHours = (int) $this->getScopedSettingValue('terrain_draft_ttl_hours', 'TERRAIN_DRAFT_TTL_HOURS', $caserneId, '12');
        if ($terrainDraftTtlHours < 1 || $terrainDraftTtlHours > 48) {
            $terrainDraftTtlHours = 12;
        }
        $terrainScrollMissingEnabled = $this->getScopedSettingValue('terrain_scroll_missing_enabled', 'TERRAIN_SCROLL_MISSING_ENABLED', $caserneId, '1') !== '0';
        $dashboardAnomaliesEnabled = $this->getScopedSettingValue('dashboard_anomalies_enabled', 'DASHBOARD_ANOMALIES_ENABLED', $caserneId, '1') !== '0';
        $dashboardVerificationsEnabled = $this->getScopedSettingValue('dashboard_verifications_enabled', 'DASHBOARD_VERIFICATIONS_ENABLED', $caserneId, '1') !== '0';
        $dashboardPharmacyEnabled = $this->getScopedSettingValue('dashboard_pharmacy_enabled', 'DASHBOARD_PHARMACY_ENABLED', $caserneId, '1') !== '0';
        $dashboardAnomaliesOrder = $this->readDashboardOrder('dashboard_anomalies_order', $caserneId, 10);
        $dashboardVerificationsOrder = $this->readDashboardOrder('dashboard_verifications_order', $caserneId, 20);
        $dashboardPharmacyOrder = $this->readDashboardOrder('dashboard_pharmacy_order', $caserneId, 30);
        $fieldQrPrintHint = $this->getScopedSettingValue(
            'field_qr_print_hint',
            'FIELD_QR_PRINT_HINT',
            $caserneId,
            'Scanner pour ouvrir le formulaire de verification.'
        );
        $pharmacyQrPrintHint = $this->getScopedSettingValue(
            'pharmacy_qr_print_hint',
            'PHARMACY_QR_PRINT_HINT',
            $caserneId,
            'Pour toute sortie de materiel, merci de la declarer via ce QR code.'
        );
        $inventoryQrPrintHint = $this->getScopedSettingValue(
            'inventory_qr_print_hint',
            'INVENTORY_QR_PRINT_HINT',
            $caserneId,
            'Pour realiser un inventaire mobile, scanner ce QR code.'
        );
        $notificationsEmailEnabled = $this->getSettingValue('notifications_email_enabled', 'NOTIFICATIONS_EMAIL_ENABLED', '0') === '1';
        $notificationsEmailFrom = $this->getSettingValue('notifications_email_from', 'NOTIFICATIONS_EMAIL_FROM', 'no-reply@verifapp.local');
        $notificationsEmailFromName = $this->getSettingValue('notifications_email_from_name', 'NOTIFICATIONS_EMAIL_FROM_NAME', 'VerifApp');
        $notificationsEmailTransport = strtolower($this->getSettingValue('notifications_email_transport', 'NOTIFICATIONS_EMAIL_TRANSPORT', 'mail'));
        if (!in_array($notificationsEmailTransport, ['mail', 'smtp'], true)) {
            $notificationsEmailTransport = 'mail';
        }
        $notificationsEmailSmtpHost = $this->getSettingValue('notifications_email_smtp_host', 'NOTIFICATIONS_EMAIL_SMTP_HOST', '');
        $notificationsEmailSmtpPort = $this->getSettingValue('notifications_email_smtp_port', 'NOTIFICATIONS_EMAIL_SMTP_PORT', '587');
        $notificationsEmailSmtpSecurity = strtolower($this->getSettingValue('notifications_email_smtp_security', 'NOTIFICATIONS_EMAIL_SMTP_SECURITY', 'tls'));
        if (!in_array($notificationsEmailSmtpSecurity, ['none', 'tls', 'ssl'], true)) {
            $notificationsEmailSmtpSecurity = 'tls';
        }
        $notificationsEmailSmtpAuth = $this->getSettingValue('notifications_email_smtp_auth', 'NOTIFICATIONS_EMAIL_SMTP_AUTH', '1') === '1';
        $notificationsEmailSmtpUser = $this->getSettingValue('notifications_email_smtp_user', 'NOTIFICATIONS_EMAIL_SMTP_USER', '');
        $notificationsEmailSmtpPass = $this->getSettingValue('notifications_email_smtp_pass', 'NOTIFICATIONS_EMAIL_SMTP_PASS', '');
        $notificationsEmailSmtpTimeout = $this->getSettingValue('notifications_email_smtp_timeout', 'NOTIFICATIONS_EMAIL_SMTP_TIMEOUT', '12');
        $notificationsEmailTestTo = is_array($managerUser) ? trim((string) ($managerUser['email'] ?? '')) : '';
        $passwordPolicy = PasswordPolicy::policy();
        $appUrl = $this->resolvePublicBaseUrl();
        $fieldToken = trim($this->getScopedSettingValue('field_qr_token', 'FIELD_QR_TOKEN', $caserneId, ''));
        $pharmacyToken = trim($this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, ''));
        $inventoryToken = trim($this->getScopedSettingValue('inventory_qr_token', 'INVENTORY_QR_TOKEN', $caserneId, ''));
        $settingsStorage = $this->getSettingsStorageMode();
        $caserneRepository = new CaserneRepository();
        $casernes = $isPlatformAdmin ? $caserneRepository->findAll() : [];

        $caserneParam = $caserneId !== null ? '&caserne_id=' . $caserneId : '';
        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '') . $caserneParam;
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '') . $caserneParam;
        $inventoryGuestPath = '/index.php?controller=pharmacy&action=access&next=inventory_form' . ($inventoryToken !== '' ? '&token=' . rawurlencode($inventoryToken) : '') . $caserneParam;

        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;
        $inventoryGuestUrl = $appUrl !== '' ? $appUrl . $inventoryGuestPath : $inventoryGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_app_settings.php';
    }

    public function passwordPolicySave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $minLengthRaw = trim((string) ($_POST['password_min_length'] ?? ''));
        if (!ctype_digit($minLengthRaw)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=password_policy_invalid');
        }
        $minLength = (int) $minLengthRaw;
        if ($minLength < PasswordPolicy::MIN_MIN_LENGTH || $minLength > PasswordPolicy::MAX_MIN_LENGTH) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=password_policy_invalid');
        }

        $requireLower = isset($_POST['password_require_lower']) && (string) $_POST['password_require_lower'] === '1';
        $requireUpper = isset($_POST['password_require_upper']) && (string) $_POST['password_require_upper'] === '1';
        $requireDigit = isset($_POST['password_require_digit']) && (string) $_POST['password_require_digit'] === '1';
        $requireSpecial = isset($_POST['password_require_special']) && (string) $_POST['password_require_special'] === '1';
        if (!$requireLower && !$requireUpper && !$requireDigit && !$requireSpecial) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=password_policy_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $saveMap = [
            'password_min_length' => (string) $minLength,
            'password_require_lower' => $requireLower ? '1' : '0',
            'password_require_upper' => $requireUpper ? '1' : '0',
            'password_require_digit' => $requireDigit ? '1' : '0',
            'password_require_special' => $requireSpecial ? '1' : '0',
        ];

        foreach ($saveMap as $key => $value) {
            if (!$repository->set($key, $value)) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=password_policy_save_failed');
            }
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=password_policy_saved');
    }

    public function securityAudit(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $isPlatformAdmin = $this->isPlatformAdmin();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
        }

        $scopeCaserneId = $isPlatformAdmin ? null : $caserneId;
        $selectedCaserneId = $scopeCaserneId;
        if ($isPlatformAdmin) {
            $requestedCaserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;
            $selectedCaserneId = $requestedCaserneId > 0 ? $requestedCaserneId : null;
            $scopeCaserneId = $selectedCaserneId;
        }

        $filters = [
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'event_type' => trim((string) ($_GET['event_type'] ?? '')),
            'identifier' => trim((string) ($_GET['identifier'] ?? '')),
            'ip_address' => trim((string) ($_GET['ip_address'] ?? '')),
        ];

        $repository = new LoginEventRepository();
        $events = $repository->findAll($filters, $scopeCaserneId, 400);

        $casernes = [];
        if ($isPlatformAdmin) {
            $casernes = (new CaserneRepository())->findAll();
        }

        $summary = [
            'total' => count($events),
            'success' => 0,
            'failure' => 0,
        ];
        foreach ($events as $event) {
            if ((string) ($event['event_type'] ?? '') === 'success') {
                $summary['success']++;
            } else {
                $summary['failure']++;
            }
        }

        require dirname(__DIR__, 2) . '/public/views/manager_security_audit.php';
    }

    public function securityAuditExportCsv(): void
    {
        $isPlatformAdmin = $this->isPlatformAdmin();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
        }

        $scopeCaserneId = $isPlatformAdmin ? null : $caserneId;
        if ($isPlatformAdmin) {
            $requestedCaserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;
            if ($requestedCaserneId > 0) {
                $scopeCaserneId = $requestedCaserneId;
            }
        }

        $filters = [
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'event_type' => trim((string) ($_GET['event_type'] ?? '')),
            'identifier' => trim((string) ($_GET['identifier'] ?? '')),
            'ip_address' => trim((string) ($_GET['ip_address'] ?? '')),
        ];

        $repository = new LoginEventRepository();
        $events = $repository->findAll($filters, $scopeCaserneId, 2000);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="audit_connexions.csv"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['date_heure', 'caserne', 'type', 'identifiant_saisi', 'utilisateur', 'email', 'ip', 'raison', 'user_agent'], ';');
        foreach ($events as $event) {
            fputcsv($output, [
                (string) ($event['created_at'] ?? ''),
                (string) ($event['caserne_nom'] ?? ''),
                (string) ($event['event_type'] ?? ''),
                (string) ($event['identifier'] ?? ''),
                (string) ($event['user_nom'] ?? ''),
                (string) ($event['user_email'] ?? ''),
                (string) ($event['ip_address'] ?? ''),
                (string) ($event['reason'] ?? ''),
                (string) ($event['user_agent'] ?? ''),
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function notificationsEmailSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $enabled = isset($_POST['notifications_email_enabled']) && (string) $_POST['notifications_email_enabled'] === '1';
        $from = trim((string) ($_POST['notifications_email_from'] ?? ''));
        $fromName = trim((string) ($_POST['notifications_email_from_name'] ?? ''));
        $transport = strtolower(trim((string) ($_POST['notifications_email_transport'] ?? 'mail')));
        $smtpHost = trim((string) ($_POST['notifications_email_smtp_host'] ?? ''));
        $smtpPortRaw = trim((string) ($_POST['notifications_email_smtp_port'] ?? '587'));
        $smtpSecurity = strtolower(trim((string) ($_POST['notifications_email_smtp_security'] ?? 'tls')));
        $smtpAuth = isset($_POST['notifications_email_smtp_auth']) && (string) $_POST['notifications_email_smtp_auth'] === '1';
        $smtpUser = trim((string) ($_POST['notifications_email_smtp_user'] ?? ''));
        $smtpPassPosted = (string) ($_POST['notifications_email_smtp_pass'] ?? '');
        $smtpTimeoutRaw = trim((string) ($_POST['notifications_email_smtp_timeout'] ?? '12'));

        if ($from === '' || filter_var($from, FILTER_VALIDATE_EMAIL) === false || mb_strlen($from) > 190) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if ($fromName === '' || mb_strlen($fromName) > 120) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if (!in_array($transport, ['mail', 'smtp'], true)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if ($smtpPortRaw === '' || !ctype_digit($smtpPortRaw)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        $smtpPort = (int) $smtpPortRaw;
        if ($smtpPort < 1 || $smtpPort > 65535) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if (!in_array($smtpSecurity, ['none', 'tls', 'ssl'], true)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if ($smtpTimeoutRaw === '' || !ctype_digit($smtpTimeoutRaw)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        $smtpTimeout = (int) $smtpTimeoutRaw;
        if ($smtpTimeout < 3 || $smtpTimeout > 60) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if ($transport === 'smtp' && $smtpHost === '') {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }
        if ($transport === 'smtp' && $smtpAuth && $smtpUser === '') {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $smtpPass = trim($smtpPassPosted);
        if ($smtpPass === '') {
            $existingPass = (string) ($repository->get('notifications_email_smtp_pass') ?? '');
            $smtpPass = trim($existingPass);
        }
        if ($transport === 'smtp' && $smtpAuth && $smtpPass === '') {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_invalid');
        }

        $saveMap = [
            'notifications_email_enabled' => $enabled ? '1' : '0',
            'notifications_email_from' => $from,
            'notifications_email_from_name' => $fromName,
            'notifications_email_transport' => $transport,
            'notifications_email_smtp_host' => $smtpHost,
            'notifications_email_smtp_port' => (string) $smtpPort,
            'notifications_email_smtp_security' => $smtpSecurity,
            'notifications_email_smtp_auth' => $smtpAuth ? '1' : '0',
            'notifications_email_smtp_user' => $smtpUser,
            'notifications_email_smtp_pass' => $smtpPass,
            'notifications_email_smtp_timeout' => (string) $smtpTimeout,
        ];
        foreach ($saveMap as $key => $value) {
            if (!$repository->set($key, $value)) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_settings_save_failed');
            }
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=email_settings_saved');
    }

    public function notificationsEmailTest(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $recipient = trim((string) ($_POST['notifications_email_test_to'] ?? ''));
        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_test_invalid');
        }

        $enabled = $this->getSettingValue('notifications_email_enabled', 'NOTIFICATIONS_EMAIL_ENABLED', '0') === '1';
        if (!$enabled) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_test_disabled');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_test_failed');
        }

        $repository = new NotificationRepository();
        $ok = $repository->sendTestEmail($caserneId, $recipient);
        if (!$ok) {
            $detail = trim($repository->getLastEmailError());
            if ($detail !== '') {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_test_failed&detail=' . rawurlencode($detail));
            }
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=email_test_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=email_test_sent');
    }

    public function appTimezoneSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $timezone = trim((string) ($_POST['app_timezone'] ?? ''));
        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timezone_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        if (!$repository->set('app_timezone', $timezone)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=timezone_save_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=timezone_saved');
    }

    public function debugModeSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $debugModeRaw = trim((string) ($_POST['app_debug_mode'] ?? ''));
        if (!in_array($debugModeRaw, ['0', '1'], true)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=debug_mode_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        if (!$repository->set('app_debug_mode', $debugModeRaw)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=debug_mode_save_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=debug_mode_saved');
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

    public function sessionTimeoutSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=session_timeout_save_failed');
        }

        $ttlRaw = trim((string) ($_POST['manager_session_ttl_minutes'] ?? ''));
        if ($ttlRaw === '' || ctype_digit($ttlRaw) === false) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=session_timeout_invalid');
        }

        $ttl = (int) $ttlRaw;
        if ($ttl < 5 || $ttl > 1440) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=session_timeout_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $key = 'manager_session_ttl_minutes_caserne_' . $caserneId;
        if (!$repository->set($key, (string) $ttl)) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=session_timeout_save_failed');
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=session_timeout_saved');
    }

    public function terrainUxSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=terrain_ux_save_failed');
        }

        $density = strtolower(trim((string) ($_POST['terrain_mobile_density'] ?? 'normal')));
        if (!in_array($density, ['compact', 'normal'], true)) {
            $density = 'normal';
        }

        $stickyEnabled = isset($_POST['terrain_sticky_progress_enabled']) && (string) $_POST['terrain_sticky_progress_enabled'] === '1' ? '1' : '0';
        $draftEnabled = isset($_POST['terrain_draft_enabled']) && (string) $_POST['terrain_draft_enabled'] === '1' ? '1' : '0';
        $scrollMissingEnabled = isset($_POST['terrain_scroll_missing_enabled']) && (string) $_POST['terrain_scroll_missing_enabled'] === '1' ? '1' : '0';

        $ttlRaw = trim((string) ($_POST['terrain_draft_ttl_hours'] ?? '12'));
        if ($ttlRaw === '' || ctype_digit($ttlRaw) === false) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=terrain_ux_invalid');
        }

        $ttl = (int) $ttlRaw;
        if ($ttl < 1 || $ttl > 48) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=terrain_ux_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $saveMap = [
            'terrain_mobile_density_caserne_' . $caserneId => $density,
            'terrain_sticky_progress_enabled_caserne_' . $caserneId => $stickyEnabled,
            'terrain_draft_enabled_caserne_' . $caserneId => $draftEnabled,
            'terrain_draft_ttl_hours_caserne_' . $caserneId => (string) $ttl,
            'terrain_scroll_missing_enabled_caserne_' . $caserneId => $scrollMissingEnabled,
        ];

        foreach ($saveMap as $key => $value) {
            if (!$repository->set($key, $value)) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=terrain_ux_save_failed');
            }
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=terrain_ux_saved');
    }

    public function dashboardConfigSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=dashboard_config_save_failed');
        }

        $anomaliesEnabled = isset($_POST['dashboard_anomalies_enabled']) && (string) $_POST['dashboard_anomalies_enabled'] === '1' ? '1' : '0';
        $verificationsEnabled = isset($_POST['dashboard_verifications_enabled']) && (string) $_POST['dashboard_verifications_enabled'] === '1' ? '1' : '0';
        $pharmacyEnabled = isset($_POST['dashboard_pharmacy_enabled']) && (string) $_POST['dashboard_pharmacy_enabled'] === '1' ? '1' : '0';

        $anomaliesOrder = trim((string) ($_POST['dashboard_anomalies_order'] ?? '10'));
        $verificationsOrder = trim((string) ($_POST['dashboard_verifications_order'] ?? '20'));
        $pharmacyOrder = trim((string) ($_POST['dashboard_pharmacy_order'] ?? '30'));
        foreach ([$anomaliesOrder, $verificationsOrder, $pharmacyOrder] as $orderValue) {
            if ($orderValue === '' || ctype_digit($orderValue) === false) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=dashboard_config_invalid');
            }
            $parsed = (int) $orderValue;
            if ($parsed < 1 || $parsed > 999) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=dashboard_config_invalid');
            }
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $saveMap = [
            'dashboard_anomalies_enabled_caserne_' . $caserneId => $anomaliesEnabled,
            'dashboard_verifications_enabled_caserne_' . $caserneId => $verificationsEnabled,
            'dashboard_pharmacy_enabled_caserne_' . $caserneId => $pharmacyEnabled,
            'dashboard_anomalies_order_caserne_' . $caserneId => (string) ((int) $anomaliesOrder),
            'dashboard_verifications_order_caserne_' . $caserneId => (string) ((int) $verificationsOrder),
            'dashboard_pharmacy_order_caserne_' . $caserneId => (string) ((int) $pharmacyOrder),
        ];

        foreach ($saveMap as $key => $value) {
            if (!$repository->set($key, $value)) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=dashboard_config_save_failed');
            }
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=dashboard_config_saved');
    }

    public function qrPrintHintsSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_admin&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=qr_hint_save_failed');
        }

        $fieldHint = trim((string) ($_POST['field_qr_print_hint'] ?? ''));
        $pharmacyHint = trim((string) ($_POST['pharmacy_qr_print_hint'] ?? ''));
        $inventoryHint = trim((string) ($_POST['inventory_qr_print_hint'] ?? ''));
        if ($fieldHint === '' || $pharmacyHint === '' || $inventoryHint === '') {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=qr_hint_invalid');
        }
        if (mb_strlen($fieldHint) > 180 || mb_strlen($pharmacyHint) > 180 || mb_strlen($inventoryHint) > 180) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=qr_hint_invalid');
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_admin&action=settings&error=settings_store_unavailable');
        }

        $saveMap = [
            'field_qr_print_hint_caserne_' . $caserneId => $fieldHint,
            'pharmacy_qr_print_hint_caserne_' . $caserneId => $pharmacyHint,
            'inventory_qr_print_hint_caserne_' . $caserneId => $inventoryHint,
        ];
        foreach ($saveMap as $key => $value) {
            if (!$repository->set($key, $value)) {
                $this->redirect('/index.php?controller=manager_admin&action=settings&error=qr_hint_save_failed');
            }
        }

        $this->redirect('/index.php?controller=manager_admin&action=settings&success=qr_hint_saved');
    }

    public function caserneSave(): void
    {
        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

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
            'inventory' => 'inventory_qr_token',
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

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }

    private function isPlatformAdmin(): bool
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        if (!is_array($managerUser) || !isset($managerUser['id'])) {
            return false;
        }

        $userRepository = new UserRepository();
        $currentManager = $userRepository->findById((int) $managerUser['id']);
        if ($currentManager === null) {
            return false;
        }

        return strtolower((string) ($currentManager['role'] ?? '')) === 'admin'
            || (int) ($currentManager['id'] ?? 0) === 1;
    }
}
