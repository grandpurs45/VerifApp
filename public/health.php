<?php

declare(strict_types=1);

use App\Core\AppVersion;
use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppSettingRepository;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

header('Content-Type: application/json; charset=utf-8');

try {
    Database::getConnection();
    $appSettings = new AppSettingRepository();
    $readSetting = static function (string $settingKey, string $envKey, string $default = '') use ($appSettings): string {
        if ($appSettings->isAvailable()) {
            $value = $appSettings->get($settingKey);
            if ($value !== null && trim($value) !== '') {
                return trim((string) $value);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    };

    $timezone = $readSetting('app_timezone', 'APP_TIMEZONE', date_default_timezone_get());
    if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
        $timezone = date_default_timezone_get();
    }

    $transport = strtolower($readSetting('notifications_email_transport', 'NOTIFICATIONS_EMAIL_TRANSPORT', 'mail'));
    if (!in_array($transport, ['mail', 'smtp'], true)) {
        $transport = 'mail';
    }
    $emailEnabled = $readSetting('notifications_email_enabled', 'NOTIFICATIONS_EMAIL_ENABLED', '0') === '1';
    $smtpHost = $readSetting('notifications_email_smtp_host', 'NOTIFICATIONS_EMAIL_SMTP_HOST', '');
    $smtpPort = (int) $readSetting('notifications_email_smtp_port', 'NOTIFICATIONS_EMAIL_SMTP_PORT', '587');
    $smtpAuth = $readSetting('notifications_email_smtp_auth', 'NOTIFICATIONS_EMAIL_SMTP_AUTH', '1') === '1';
    $smtpUser = $readSetting('notifications_email_smtp_user', 'NOTIFICATIONS_EMAIL_SMTP_USER', '');
    $smtpPass = $readSetting('notifications_email_smtp_pass', 'NOTIFICATIONS_EMAIL_SMTP_PASS', '');

    $smtpStatus = 'disabled';
    if ($emailEnabled) {
        if ($transport === 'mail') {
            $smtpStatus = 'mail_transport';
        } else {
            $smtpStatus = 'smtp_incomplete';
            $smtpPortValid = $smtpPort > 0 && $smtpPort <= 65535;
            $smtpAuthValid = !$smtpAuth || ($smtpUser !== '' && $smtpPass !== '');
            if ($smtpHost !== '' && $smtpPortValid && $smtpAuthValid) {
                $smtpStatus = 'smtp_configured';
            }
        }
    }

    echo json_encode([
        'status' => 'ok',
        'version' => AppVersion::current(),
        'timezone' => $timezone,
        'db' => 'ok',
        'smtp' => [
            'enabled' => $emailEnabled ? '1' : '0',
            'transport' => $transport,
            'status' => $smtpStatus,
        ],
        'time' => date('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'version' => AppVersion::current(),
        'timezone' => date_default_timezone_get(),
        'db' => 'down',
        'message' => $throwable->getMessage(),
        'time' => date('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
