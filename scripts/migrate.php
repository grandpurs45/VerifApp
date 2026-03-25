<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();

$envPath = dirname(__DIR__) . '/.env';
if (isset($argv[1]) && $argv[1] !== '') {
    $envPath = $argv[1];
}

Env::load($envPath);

$connection = Database::getConnection();

$connection->exec(
    '
    CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) PRIMARY KEY,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    '
);

$executed = $connection->query('SELECT filename FROM schema_migrations');
$executedFiles = [];
if ($executed !== false) {
    $executedFiles = $executed->fetchAll(PDO::FETCH_COLUMN);
}
$executedLookup = array_flip(array_map('strval', $executedFiles));

$migrationPaths = glob(dirname(__DIR__) . '/database/migrations/*.sql');
if ($migrationPaths === false) {
    fwrite(STDERR, "Impossible de lire les migrations.\n");
    exit(1);
}
sort($migrationPaths, SORT_NATURAL);

$applied = 0;

foreach ($migrationPaths as $path) {
    $filename = basename($path);

    if (isset($executedLookup[$filename])) {
        echo "[SKIP] $filename\n";
        continue;
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "[FAIL] Lecture impossible: $filename\n");
        exit(1);
    }

    try {
        $connection->exec($sql);

        $insert = $connection->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');
        $insert->execute(['filename' => $filename]);

        $applied++;
        echo "[OK]   $filename\n";
    } catch (Throwable $throwable) {
        fwrite(STDERR, "[FAIL] $filename\n" . $throwable->getMessage() . "\n");
        exit(1);
    }
}

echo "Migrations terminees. Nouvelles appliquees: $applied\n";
