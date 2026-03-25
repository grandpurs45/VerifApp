<?php

declare(strict_types=1);

use App\Core\AppVersion;
use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

header('Content-Type: application/json; charset=utf-8');

try {
    Database::getConnection();

    echo json_encode([
        'status' => 'ok',
        'version' => AppVersion::current(),
        'db' => 'ok',
        'time' => date('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'version' => AppVersion::current(),
        'db' => 'down',
        'message' => $throwable->getMessage(),
        'time' => date('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
