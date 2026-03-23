<?php

declare(strict_types=1);

namespace App\Core;

final class Env
{
    private static array $variables = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('Le fichier .env est introuvable : %s', $path));
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new \RuntimeException('Impossible de lire le fichier .env');
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');

            $name = trim($name);
            $value = trim($value);

            self::$variables[$name] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$variables[$key] ?? $default;
    }
}