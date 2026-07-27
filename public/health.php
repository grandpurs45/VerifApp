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

$startedAt = microtime(true);
$requestedFormat = strtolower(trim((string) ($_GET['format'] ?? '')));
$acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
$wantsHtml = $requestedFormat === 'html'
    || ($requestedFormat !== 'json' && str_contains($acceptHeader, 'text/html'));

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$httpStatus = 200;
$payload = [];

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
    date_default_timezone_set($timezone);

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

    $payload = [
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
    ];
} catch (Throwable $throwable) {
    $httpStatus = 503;
    $payload = [
        'status' => 'error',
        'version' => AppVersion::current(),
        'timezone' => date_default_timezone_get(),
        'db' => 'down',
        'message' => 'Service de base de donnees indisponible.',
        'time' => date('c'),
    ];

    $debugEnabled = in_array(
        strtolower(trim((string) (Env::get('APP_DEBUG', '0') ?? '0'))),
        ['1', 'true', 'yes', 'on'],
        true
    );
    if ($debugEnabled) {
        $payload['debug'] = $throwable->getMessage();
    }
}

$payload['response_time_ms'] = round((microtime(true) - $startedAt) * 1000, 1);
http_response_code($httpStatus);

if (!$wantsHtml) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$isHealthy = ($payload['status'] ?? '') === 'ok';
$statusTitle = $isHealthy ? 'Service operationnel' : 'Service indisponible';
$statusText = $isHealthy
    ? 'VerifApp et sa base de donnees repondent normalement.'
    : 'VerifApp ne peut pas joindre sa base de donnees.';
$smtp = is_array($payload['smtp'] ?? null) ? $payload['smtp'] : [];
$smtpStatus = (string) ($smtp['status'] ?? 'unknown');
$smtpLabels = [
    'disabled' => 'Desactivees',
    'mail_transport' => 'Configurees via PHP mail',
    'smtp_configured' => 'SMTP configure',
    'smtp_incomplete' => 'Configuration incomplete',
    'unknown' => 'Non disponible',
];
$smtpLabel = $smtpLabels[$smtpStatus] ?? $smtpStatus;

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta http-equiv="refresh" content="30">
    <title>Etat du service - VerifApp</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px;
            display: grid;
            place-items: center;
        }
        main {
            width: min(760px, 100%);
            overflow: hidden;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 24px;
            color: #ffffff;
            background: #172033;
        }
        h1 {
            margin: 4px 0 0;
            font-size: clamp(1.45rem, 4vw, 2rem);
            letter-spacing: 0;
        }
        .brand {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .json-link {
            flex: 0 0 auto;
            border: 1px solid #64748b;
            border-radius: 6px;
            padding: 8px 10px;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }
        .content {
            padding: 24px;
        }
        .summary {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 24px;
            border: 1px solid <?= $isHealthy ? '#86efac' : '#fca5a5' ?>;
            border-radius: 8px;
            padding: 16px;
            background: <?= $isHealthy ? '#f0fdf4' : '#fef2f2' ?>;
        }
        .indicator {
            width: 14px;
            height: 14px;
            flex: 0 0 auto;
            margin-top: 4px;
            border-radius: 50%;
            background: <?= $isHealthy ? '#16a34a' : '#dc2626' ?>;
            box-shadow: 0 0 0 5px <?= $isHealthy ? '#dcfce7' : '#fee2e2' ?>;
        }
        .summary strong {
            display: block;
            margin-bottom: 3px;
            font-size: 1rem;
        }
        .summary p {
            margin: 0;
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.45;
        }
        .checks {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0;
        }
        .check {
            min-width: 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            background: #f8fafc;
        }
        .check dt {
            margin-bottom: 7px;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .check dd {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .ok {
            color: #15803d;
        }
        .error {
            color: #b91c1c;
        }
        footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px 16px;
            margin-top: 22px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            color: #64748b;
            font-size: 0.78rem;
        }
        @media (max-width: 560px) {
            body {
                padding: 12px;
                place-items: start center;
            }
            header,
            .content {
                padding: 18px;
            }
            header {
                flex-direction: column;
            }
            .checks {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <p class="brand">VerifApp</p>
            <h1>Etat du service</h1>
        </div>
        <a class="json-link" href="?format=json">Voir JSON</a>
    </header>
    <div class="content">
        <section class="summary" aria-live="polite">
            <span class="indicator" aria-hidden="true"></span>
            <div>
                <strong><?= $escape($statusTitle) ?></strong>
                <p><?= $escape($statusText) ?></p>
            </div>
        </section>

        <dl class="checks">
            <div class="check">
                <dt>Application</dt>
                <dd class="<?= $isHealthy ? 'ok' : 'error' ?>"><?= $isHealthy ? 'Disponible' : 'En erreur' ?></dd>
            </div>
            <div class="check">
                <dt>Base de donnees</dt>
                <dd class="<?= ($payload['db'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
                    <?= ($payload['db'] ?? '') === 'ok' ? 'Connectee' : 'Indisponible' ?>
                </dd>
            </div>
            <div class="check">
                <dt>Notifications email</dt>
                <dd><?= $escape($smtpLabel) ?></dd>
            </div>
            <div class="check">
                <dt>Version</dt>
                <dd>v<?= $escape((string) ($payload['version'] ?? '')) ?></dd>
            </div>
            <div class="check">
                <dt>Temps de reponse</dt>
                <dd><?= $escape((string) ($payload['response_time_ms'] ?? '')) ?> ms</dd>
            </div>
            <div class="check">
                <dt>Fuseau horaire</dt>
                <dd><?= $escape((string) ($payload['timezone'] ?? '')) ?></dd>
            </div>
        </dl>

        <footer>
            <span>Dernier controle : <?= $escape((string) ($payload['time'] ?? '')) ?></span>
            <span>Actualisation automatique : 30 s</span>
        </footer>
    </div>
</main>
</body>
</html>
