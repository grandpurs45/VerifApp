<?php

declare(strict_types=1);

namespace App\Core;

final class AppVersion
{
    private static ?string $current = null;

    public static function current(): string
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $envVersion = Env::get('APP_VERSION');
        if ($envVersion !== null && trim($envVersion) !== '') {
            self::$current = trim($envVersion);
            return self::$current;
        }

        $versionFile = dirname(__DIR__, 2) . '/VERSION';
        if (is_file($versionFile)) {
            $value = trim((string) file_get_contents($versionFile));
            if ($value !== '') {
                self::$current = $value;
                return self::$current;
            }
        }

        self::$current = 'dev';
        return self::$current;
    }
}
