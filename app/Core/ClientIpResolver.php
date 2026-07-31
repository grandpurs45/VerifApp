<?php

declare(strict_types=1);

namespace App\Core;

final class ClientIpResolver
{
    /**
     * @param array<string, mixed> $server
     * @return array{client_ip:string,proxy_ip:?string}
     */
    public static function resolve(array $server): array
    {
        $peerIp = self::normalizeIp((string) ($server['REMOTE_ADDR'] ?? ''));
        $clientIp = $peerIp;

        if ($peerIp !== null && self::isTrustedProxy($peerIp)) {
            $forwardedIp = self::resolveForwardedIp($server);
            if ($forwardedIp !== null) {
                $clientIp = $forwardedIp;
            }
        }

        return [
            'client_ip' => $clientIp ?? '0.0.0.0',
            'proxy_ip' => $clientIp !== null && $peerIp !== null && $clientIp !== $peerIp ? $peerIp : null,
        ];
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function resolveForwardedIp(array $server): ?string
    {
        $cloudflareIp = self::normalizeIp((string) ($server['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cloudflareIp !== null) {
            return $cloudflareIp;
        }

        $forwarded = (string) ($server['HTTP_FORWARDED'] ?? '');
        if ($forwarded !== '') {
            foreach (explode(',', $forwarded) as $entry) {
                if (preg_match('/(?:^|;)\s*for=("?)([^;,"]+)\1/i', $entry, $matches) === 1) {
                    $ip = self::normalizeIp($matches[2]);
                    if ($ip !== null) {
                        return $ip;
                    }
                }
            }
        }

        foreach (explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '')) as $candidate) {
            $ip = self::normalizeIp($candidate);
            if ($ip !== null) {
                return $ip;
            }
        }

        return self::normalizeIp((string) ($server['HTTP_X_REAL_IP'] ?? ''));
    }

    private static function normalizeIp(string $value): ?string
    {
        $value = trim($value, " \t\n\r\0\x0B\"");
        if (preg_match('/^\[([^]]+)](?::\d+)?$/', $value, $matches) === 1) {
            $value = $matches[1];
        } elseif (substr_count($value, ':') === 1 && preg_match('/^(.+):\d+$/', $value, $matches) === 1) {
            $value = $matches[1];
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false ? $value : null;
    }

    private static function isTrustedProxy(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
