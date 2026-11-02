<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Repositories\NotificationRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$repo = new NotificationRepository();
$build = new ReflectionMethod($repo, 'buildAnomalyEmailBodies');
$multipart = new ReflectionMethod($repo, 'buildMultipartBody');
$smtp = new ReflectionMethod($repo, 'prepareSmtpData');
$normalize = static fn(string $value): string => str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $value));
foreach (['new', 'reminder', 'update'] as $kind) {
    $bodies = $build->invoke($repo, 'INGRE', 'Anomalie - VSAV 75 - bouteille O2 5l', 'Non-conformite signalee.', 'https://example.invalid/anomalies', [
        'notification_kind' => $kind,
        'vehicle' => 'VSAV 75',
        'poste' => "Chef d'agres VSAV",
        'notifier' => 'OUDARD',
        'anomalies' => [['label' => 'bouteille O2 5l', 'zone' => 'Cellule', 'value' => '50 Bars', 'comment' => str_repeat('Observation <test> & "detail" ', 120)]],
    ]);
    $check(strlen($bodies['html']) > 998 && str_contains($bodies['html'], '<div><strong>Valeur:</strong> 50 Bars</div>'), 'Fixture must reproduce the long HTML line and measured value');
    $boundary = 'test_notification_boundary';
    $mime = $multipart->invoke($repo, $boundary, $bodies['text'], $bodies['html']);
    $parts = explode('--' . $boundary, $mime);
    foreach ([1 => 'text', 2 => 'html'] as $index => $format) {
        [$headers, $encoded] = explode("\r\n\r\n", $parts[$index], 2);
        $check(str_contains($headers, 'Content-Transfer-Encoding: quoted-printable'), 'Missing transport encoding');
        $encoded = substr($encoded, 0, -2);
        foreach (explode("\r\n", $encoded) as $line) $check(strlen($line) <= 76, 'Encoded body line too long');
        $check(quoted_printable_decode($encoded) === $normalize($bodies[$format]), 'Content changed during transport encoding');
    }
    $wire = $smtp->invoke($repo, "MIME-Version: 1.0\r\n\r\n" . $mime);
    $check(!str_contains($wire, "\r\r\n"), 'Doubled CR in SMTP headers');
    $check(!preg_match('/(?<!\r)\n/', $wire), 'Bare LF in SMTP payload');
}
$check($smtp->invoke($repo, "A\r\n.\n.test\rB") === "A\r\n..\r\n..test\r\nB", 'SMTP dot escaping or newline normalization failed');
$unicode = "Accent: \xC3\xA9\n" . str_repeat("\xC3\xA8=", 200);
$mime = $multipart->invoke($repo, 'unicode_boundary', $unicode, $unicode);
$parts = explode('--unicode_boundary', $mime);
[, $encoded] = explode("\r\n\r\n", $parts[1], 2);
$check(quoted_printable_decode(substr($encoded, 0, -2)) === $normalize($unicode), 'UTF-8 or equals sign corruption');
echo "OK: anomaly/reminder MIME round-trip, short encoded lines, escaped content, UTF-8 and SMTP framing (no email sent)\n";
