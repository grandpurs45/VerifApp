<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

[$options, $positionals] = parseArguments($argv);
if (isset($options['help'])) {
    printHelp();
    exit(0);
}

$backupSource = (string) ($options['from'] ?? ($positionals[0] ?? ''));
if (trim($backupSource) === '') {
    fwrite(STDERR, "[FAIL] Fournir --from=<backup.zip|backup_dir>\n");
    exit(1);
}

$backupPath = resolveInputPath(dirname(__DIR__), $backupSource);
if (!file_exists($backupPath)) {
    fwrite(STDERR, "[FAIL] Backup introuvable: {$backupPath}\n");
    exit(1);
}

$force = isset($options['force']);
$restoreEnv = isset($options['restore-env']);
if (!$force) {
    fwrite(STDERR, "[FAIL] Action destructive. Relancer avec --force\n");
    exit(1);
}

$tempDir = null;
try {
    if (is_file($backupPath)) {
        $tempDir = extractZipToTemp($backupPath);
        if ($tempDir === null) {
            throw new RuntimeException('Extraction ZIP impossible.');
        }
        $workingDir = $tempDir;
    } else {
        $workingDir = $backupPath;
    }

    $dbSqlPath = $workingDir . DIRECTORY_SEPARATOR . 'db.sql';
    if (!is_file($dbSqlPath)) {
        throw new RuntimeException('Fichier db.sql absent du backup.');
    }

    $connection = Database::getConnection();
    executeSqlDump($connection, $dbSqlPath);

    if ($restoreEnv) {
        $envSnapshot = $workingDir . DIRECTORY_SEPARATOR . 'env.snapshot';
        if (is_file($envSnapshot)) {
            $targetEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
            if (!copy($envSnapshot, $targetEnv)) {
                throw new RuntimeException('Restauration .env impossible (permission).');
            }
        } else {
            echo "[WARN] env.snapshot absent: .env non restaure.\n";
        }
    }

    echo "[OK] Restore termine depuis: {$backupPath}\n";
    if ($restoreEnv) {
        echo "[INFO] .env restaure (si present). Pense a redemarrer les services PHP/Apache.\n";
    }
} catch (Throwable $throwable) {
    fwrite(STDERR, "[FAIL] Restore impossible: " . $throwable->getMessage() . "\n");
    exit(1);
} finally {
    if ($tempDir !== null) {
        deleteDirectory($tempDir);
    }
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
    echo "  php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force [--restore-env]\n\n";
    echo "Options:\n";
    echo "  --from=PATH     Archive .zip ou dossier backup\n";
    echo "  --force         Confirmation obligatoire (operation destructive)\n";
    echo "  --restore-env   Restaure aussi .env depuis env.snapshot si present\n";
    echo "  --help          Affiche cette aide\n";
}

function resolveInputPath(string $rootDir, string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return $path;
    }

    if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
        return $path;
    }

    return $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function extractZipToTemp(string $zipPath): ?string
{
    if (!class_exists('ZipArchive')) {
        return null;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return null;
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'verifapp_restore_' . uniqid('', true);
    if (!mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
        $zip->close();
        return null;
    }

    if (!$zip->extractTo($tempDir)) {
        $zip->close();
        deleteDirectory($tempDir);
        return null;
    }

    $zip->close();
    return $tempDir;
}

function executeSqlDump(PDO $connection, string $sqlPath): void
{
    $sql = file_get_contents($sqlPath);
    if ($sql === false) {
        throw new RuntimeException('Lecture db.sql impossible.');
    }

    $queries = splitSqlStatements($sql);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if ($trimmed === '') {
            continue;
        }
        $connection->exec($trimmed);
    }
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble) {
            if ($char === '-' && $next === '-') {
                $third = $i + 2 < $length ? $sql[$i + 2] : '';
                if ($third === ' ' || $third === "\t" || $third === "\n" || $third === "\r") {
                    $inLineComment = true;
                    $i += 1;
                    continue;
                }
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i += 1;
                continue;
            }
        }

        if ($char === "'" && !$inDouble) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '"' && !$inSingle) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
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
