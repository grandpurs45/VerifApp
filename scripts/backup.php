<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set('Europe/Paris');

[$options, $positionals] = parseArguments($argv);
if (isset($options['help'])) {
    printHelp();
    exit(0);
}

$rootDir = dirname(__DIR__);
$outDir = resolveOutputDirectory($rootDir, (string) ($options['out'] ?? 'backups'));
$name = sanitizeBackupName((string) ($options['name'] ?? ''));
$includeEnv = !isset($options['no-env']);

if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fwrite(STDERR, "[FAIL] Impossible de creer le dossier backup: {$outDir}\n");
    exit(1);
}

$timestamp = date('Ymd_His');
$backupBaseName = 'verifapp_backup_' . $timestamp . ($name !== '' ? '_' . $name : '');
$backupDir = $outDir . DIRECTORY_SEPARATOR . $backupBaseName;

if (!mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "[FAIL] Impossible de creer le dossier backup: {$backupDir}\n");
    exit(1);
}

try {
    $connection = Database::getConnection();
    $dbSqlPath = $backupDir . DIRECTORY_SEPARATOR . 'db.sql';
    exportDatabaseSql($connection, $dbSqlPath);

    $manifestPath = $backupDir . DIRECTORY_SEPARATOR . 'manifest.json';
    $manifest = buildManifest($rootDir, $dbSqlPath, $includeEnv);
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($includeEnv) {
        $envPath = $rootDir . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envPath)) {
            copy($envPath, $backupDir . DIRECTORY_SEPARATOR . 'env.snapshot');
        }
    }

    $settingsPath = $backupDir . DIRECTORY_SEPARATOR . 'app_settings.json';
    exportAppSettings($connection, $settingsPath);

    $zipPath = $outDir . DIRECTORY_SEPARATOR . $backupBaseName . '.zip';
    $zipCreated = createZipArchive($backupDir, $zipPath);
    if ($zipCreated) {
        deleteDirectory($backupDir);
        echo "[OK] Backup cree: {$zipPath}\n";
    } else {
        echo "[WARN] ZipArchive indisponible. Backup conserve en dossier: {$backupDir}\n";
    }
} catch (Throwable $throwable) {
    fwrite(STDERR, "[FAIL] Backup impossible: " . $throwable->getMessage() . "\n");
    exit(1);
}

function parseArguments(array $argv): array
{
    $options = [];
    $positionals = [];
    foreach ($argv as $index => $arg) {
        if ($index === 0) {
            continue;
        }
        if (!str_starts_with($arg, '--')) {
            $positionals[] = $arg;
            continue;
        }
        $raw = substr($arg, 2);
        if ($raw === '') {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $raw, 2), 2, '1');
        $options[strtolower(trim($key))] = trim($value);
    }

    return [$options, $positionals];
}

function printHelp(): void
{
    echo "Usage:\n";
    echo "  php scripts/backup.php [--out=backups] [--name=libre] [--no-env]\n\n";
    echo "Options:\n";
    echo "  --out=PATH    Dossier de sortie (defaut: backups)\n";
    echo "  --name=TEXT   Suffixe libre dans le nom du backup\n";
    echo "  --no-env      N inclut pas .env dans le backup\n";
    echo "  --help        Affiche cette aide\n";
}

function resolveOutputDirectory(string $rootDir, string $out): string
{
    $out = trim($out);
    if ($out === '') {
        return $rootDir . DIRECTORY_SEPARATOR . 'backups';
    }

    if (preg_match('/^[A-Za-z]:\\\\/', $out) === 1 || str_starts_with($out, DIRECTORY_SEPARATOR)) {
        return $out;
    }

    return $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $out);
}

function sanitizeBackupName(string $name): string
{
    $name = strtolower(trim($name));
    if ($name === '') {
        return '';
    }

    $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';
    return trim($name, '_');
}

function exportDatabaseSql(PDO $connection, string $targetPath): void
{
    $tables = listBaseTables($connection);
    $fh = fopen($targetPath, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Ouverture fichier SQL impossible: ' . $targetPath);
    }

    fwrite($fh, "-- VerifApp backup SQL\n");
    fwrite($fh, '-- Generated at ' . date('c') . "\n\n");
    fwrite($fh, "SET NAMES utf8mb4;\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $tableName) {
        $safeTable = '`' . str_replace('`', '``', $tableName) . '`';
        fwrite($fh, "-- ------------------------------\n");
        fwrite($fh, "-- Table: {$tableName}\n");
        fwrite($fh, "-- ------------------------------\n");
        fwrite($fh, "DROP TABLE IF EXISTS {$safeTable};\n");

        $createStmt = $connection->query('SHOW CREATE TABLE ' . $safeTable);
        if ($createStmt === false) {
            continue;
        }
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($createRow) || !isset($createRow['Create Table'])) {
            continue;
        }

        fwrite($fh, $createRow['Create Table'] . ";\n\n");
        dumpTableRows($connection, $tableName, $fh);
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
}

function listBaseTables(PDO $connection): array
{
    $stmt = $connection->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if ($stmt === false) {
        throw new RuntimeException('Lecture des tables impossible.');
    }

    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (!isset($row[0])) {
            continue;
        }
        $tables[] = (string) $row[0];
    }

    sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
    return $tables;
}

function dumpTableRows(PDO $connection, string $tableName, $fh): void
{
    $safeTable = '`' . str_replace('`', '``', $tableName) . '`';
    $select = $connection->query('SELECT * FROM ' . $safeTable);
    if ($select === false) {
        return;
    }

    $columnCount = $select->columnCount();
    if ($columnCount === 0) {
        return;
    }

    $columnNames = [];
    for ($i = 0; $i < $columnCount; $i++) {
        $meta = $select->getColumnMeta($i);
        $columnNames[] = isset($meta['name']) ? (string) $meta['name'] : 'col_' . $i;
    }

    $escapedColumns = array_map(
        static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`',
        $columnNames
    );
    $insertPrefix = 'INSERT INTO ' . $safeTable . ' (' . implode(', ', $escapedColumns) . ') VALUES ';
    $batch = [];
    $batchSize = 250;

    while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
        $values = [];
        foreach ($columnNames as $columnName) {
            $values[] = formatSqlValue($connection, $row[$columnName] ?? null);
        }
        $batch[] = '(' . implode(', ', $values) . ')';

        if (count($batch) >= $batchSize) {
            fwrite($fh, $insertPrefix . implode(",\n", $batch) . ";\n");
            $batch = [];
        }
    }

    if ($batch !== []) {
        fwrite($fh, $insertPrefix . implode(",\n", $batch) . ";\n");
    }
}

function formatSqlValue(PDO $connection, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    return $connection->quote((string) $value);
}

function exportAppSettings(PDO $connection, string $targetPath): void
{
    $payload = [];
    try {
        $stmt = $connection->query('SELECT setting_key, setting_value, updated_at FROM app_settings ORDER BY setting_key ASC');
        if ($stmt !== false) {
            $payload = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable) {
        $payload = [];
    }

    file_put_contents(
        $targetPath,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function buildManifest(string $rootDir, string $dbSqlPath, bool $includeEnv): array
{
    $versionPath = $rootDir . DIRECTORY_SEPARATOR . 'VERSION';
    $version = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : 'unknown';

    return [
        'generated_at' => date('c'),
        'version' => $version,
        'include_env' => $includeEnv,
        'db_sql_file' => basename($dbSqlPath),
        'app_timezone' => date_default_timezone_get(),
        'php_version' => PHP_VERSION,
    ];
}

function createZipArchive(string $sourceDir, string $zipPath): bool
{
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $sourceRealPath = realpath($sourceDir);
    if ($sourceRealPath === false) {
        $zip->close();
        return false;
    }

    foreach ($iterator as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        $fileRealPath = $fileInfo->getRealPath();
        if ($fileRealPath === false) {
            continue;
        }
        $relativePath = ltrim(str_replace($sourceRealPath, '', $fileRealPath), DIRECTORY_SEPARATOR);
        if ($fileInfo->isDir()) {
            $zip->addEmptyDir($relativePath);
            continue;
        }
        $zip->addFile($fileRealPath, $relativePath);
    }

    $zip->close();
    return true;
}

function deleteDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        /** @var SplFileInfo $item */
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }

    @rmdir($path);
}
