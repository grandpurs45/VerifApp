<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;
    private static ?string $timezone = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_NAME');
        $username = Env::get('DB_USER');
        $password = Env::get('DB_PASSWORD', '');

        if ($host === null || $database === null || $username === null) {
            throw new RuntimeException('Configuration de base de donnees incomplete.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::applySessionTimezone(self::$connection, self::resolveTimezone());
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Connexion a la base de donnees impossible : ' . $exception->getMessage()
            );
        }

        return self::$connection;
    }

    public static function setTimezone(string $timezone): void
    {
        $timezone = trim($timezone);
        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            return;
        }

        self::$timezone = $timezone;

        if (self::$connection !== null) {
            self::applySessionTimezone(self::$connection, $timezone);
        }
    }

    private static function resolveTimezone(): string
    {
        if (self::$timezone !== null) {
            return self::$timezone;
        }

        $configured = trim((string) (Env::get('APP_TIMEZONE', 'Europe/Paris') ?? 'Europe/Paris'));
        if ($configured === '' || !in_array($configured, timezone_identifiers_list(), true)) {
            $configured = 'Europe/Paris';
        }

        self::$timezone = $configured;

        return self::$timezone;
    }

    private static function applySessionTimezone(PDO $connection, string $timezone): void
    {
        try {
            $tz = new DateTimeZone($timezone);
            $offset = (new DateTimeImmutable('now', $tz))->format('P');
            $stmt = $connection->prepare('SET time_zone = :tz');
            $stmt->execute(['tz' => $offset]);
        } catch (Throwable) {
            // Best-effort only: keep DB usable if timezone cannot be applied.
        }
    }
}
