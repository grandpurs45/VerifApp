<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

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
            throw new RuntimeException('Configuration de base de données incomplète.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Connexion à la base de données impossible : ' . $exception->getMessage()
            );
        }

        return self::$connection;
    }
}