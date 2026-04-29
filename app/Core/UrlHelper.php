<?php

declare(strict_types=1);

namespace App\Core;

final class UrlHelper
{
    public static function isHttpsRequest(): bool
    {
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto !== '') {
            $first = trim(explode(',', $forwardedProto)[0] ?? '');
            if ($first === 'https') {
                return true;
            }
            if ($first === 'http') {
                return false;
            }
        }

        $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
        if ($forwardedSsl === 'on' || $forwardedSsl === '1') {
            return true;
        }

        $frontEndHttps = strtolower(trim((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')));
        if ($frontEndHttps === 'on' || $frontEndHttps === '1') {
            return true;
        }

        $forwarded = strtolower(trim((string) ($_SERVER['HTTP_FORWARDED'] ?? '')));
        if ($forwarded !== '' && str_contains($forwarded, 'proto=https')) {
            return true;
        }

        return (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    }

    public static function resolvePublicBaseUrl(string $fallback = ''): string
    {
        $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        if ($host !== '') {
            $host = trim(explode(',', $host)[0] ?? '');
        }

        if ($host === '') {
            $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        }

        if ($host !== '') {
            $scheme = self::isHttpsRequest() ? 'https' : 'http';

            return $scheme . '://' . $host;
        }

        return rtrim($fallback, '/');
    }

    public static function shouldForceHttps(): bool
    {
        $envValue = strtolower(trim((string) (Env::get('APP_FORCE_HTTPS', '0') ?? '0')));

        return in_array($envValue, ['1', 'true', 'yes', 'on'], true);
    }

    public static function redirectToHttpsIfNeeded(): void
    {
        if (!self::shouldForceHttps() || self::isHttpsRequest()) {
            return;
        }

        $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        if ($host !== '') {
            $host = trim(explode(',', $host)[0] ?? '');
        }
        if ($host === '') {
            $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        }
        if ($host === '') {
            return;
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if (!headers_sent()) {
            header('Location: https://' . $host . $requestUri, true, 301);
        }
        exit;
    }
}
