<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Env;
use PDO;

final class LoginAttemptRepository
{
    private ?bool $tableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    /**
     * @return array{locked: bool, remaining_seconds: int}
     */
    public function checkLock(string $identifier, string $ipAddress): array
    {
        if (!$this->hasTable()) {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $identifier = $this->normalizeIdentifier($identifier);
        $ipAddress = $this->normalizeIp($ipAddress);
        if ($identifier === '' || $ipAddress === '') {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT locked_until
            FROM auth_login_attempts
            WHERE identifier = :identifier AND ip_address = :ip_address
            LIMIT 1
        ');
        $statement->execute([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || trim((string) ($row['locked_until'] ?? '')) === '') {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $lockedUntilTs = strtotime((string) $row['locked_until']);
        if ($lockedUntilTs === false) {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $remaining = $lockedUntilTs - time();
        if ($remaining <= 0) {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        return [
            'locked' => true,
            'remaining_seconds' => $remaining,
        ];
    }

    /**
     * @return array{locked: bool, remaining_seconds: int}
     */
    public function registerFailure(string $identifier, string $ipAddress): array
    {
        if (!$this->hasTable()) {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $identifier = $this->normalizeIdentifier($identifier);
        $ipAddress = $this->normalizeIp($ipAddress);
        if ($identifier === '' || $ipAddress === '') {
            return ['locked' => false, 'remaining_seconds' => 0];
        }

        $maxAttempts = $this->maxAttempts();
        $lockMinutes = $this->lockMinutes();
        $windowMinutes = $this->windowMinutes();

        $connection = Database::getConnection();
        try {
            $connection->beginTransaction();

            $select = $connection->prepare('
                SELECT id, attempts, first_attempt_at, locked_until
                FROM auth_login_attempts
                WHERE identifier = :identifier AND ip_address = :ip_address
                FOR UPDATE
            ');
            $select->execute([
                'identifier' => $identifier,
                'ip_address' => $ipAddress,
            ]);
            $row = $select->fetch(PDO::FETCH_ASSOC);

            $now = time();
            $locked = false;
            $remaining = 0;
            if (!is_array($row)) {
                $insert = $connection->prepare('
                    INSERT INTO auth_login_attempts (identifier, ip_address, attempts, first_attempt_at, last_attempt_at, locked_until)
                    VALUES (:identifier, :ip_address, 1, NOW(), NOW(), NULL)
                ');
                $insert->execute([
                    'identifier' => $identifier,
                    'ip_address' => $ipAddress,
                ]);
            } else {
                $attempts = (int) ($row['attempts'] ?? 0);
                $firstAttemptAt = strtotime((string) ($row['first_attempt_at'] ?? '')) ?: $now;
                $lockedUntil = trim((string) ($row['locked_until'] ?? ''));
                $lockedUntilTs = $lockedUntil !== '' ? (strtotime($lockedUntil) ?: 0) : 0;

                if ($lockedUntilTs > $now) {
                    $locked = true;
                    $remaining = $lockedUntilTs - $now;
                    $attempts++;
                    $update = $connection->prepare('
                        UPDATE auth_login_attempts
                        SET attempts = :attempts, last_attempt_at = NOW()
                        WHERE id = :id
                    ');
                    $update->execute([
                        'attempts' => $attempts,
                        'id' => (int) ($row['id'] ?? 0),
                    ]);
                } else {
                    if (($now - $firstAttemptAt) > ($windowMinutes * 60)) {
                        $attempts = 0;
                        $firstAttemptAt = $now;
                    }
                    $attempts++;
                    if ($attempts >= $maxAttempts) {
                        $locked = true;
                        $remaining = $lockMinutes * 60;
                        $update = $connection->prepare('
                            UPDATE auth_login_attempts
                            SET attempts = :attempts,
                                first_attempt_at = FROM_UNIXTIME(:first_attempt_at),
                                last_attempt_at = NOW(),
                                locked_until = DATE_ADD(NOW(), INTERVAL :lock_minutes MINUTE)
                            WHERE id = :id
                        ');
                        $update->execute([
                            'attempts' => $attempts,
                            'first_attempt_at' => $firstAttemptAt,
                            'lock_minutes' => $lockMinutes,
                            'id' => (int) ($row['id'] ?? 0),
                        ]);
                    } else {
                        $update = $connection->prepare('
                            UPDATE auth_login_attempts
                            SET attempts = :attempts,
                                first_attempt_at = FROM_UNIXTIME(:first_attempt_at),
                                last_attempt_at = NOW(),
                                locked_until = NULL
                            WHERE id = :id
                        ');
                        $update->execute([
                            'attempts' => $attempts,
                            'first_attempt_at' => $firstAttemptAt,
                            'id' => (int) ($row['id'] ?? 0),
                        ]);
                    }
                }
            }

            $cleanup = $connection->prepare('
                DELETE FROM auth_login_attempts
                WHERE last_attempt_at < DATE_SUB(NOW(), INTERVAL :history_days DAY)
            ');
            $cleanup->execute(['history_days' => 30]);

            $connection->commit();
            return ['locked' => $locked, 'remaining_seconds' => $remaining];
        } catch (\Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            return ['locked' => false, 'remaining_seconds' => 0];
        }
    }

    public function clearOnSuccess(string $identifier, string $ipAddress): void
    {
        if (!$this->hasTable()) {
            return;
        }

        $identifier = $this->normalizeIdentifier($identifier);
        $ipAddress = $this->normalizeIp($ipAddress);
        if ($identifier === '' || $ipAddress === '') {
            return;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            DELETE FROM auth_login_attempts
            WHERE identifier = :identifier AND ip_address = :ip_address
        ');
        $statement->execute([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
        ]);
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->query("SHOW TABLES LIKE 'auth_login_attempts'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }

    private function maxAttempts(): int
    {
        $value = trim((string) (Env::get('MANAGER_LOGIN_MAX_ATTEMPTS', '5') ?? '5'));
        $parsed = ctype_digit($value) ? (int) $value : 5;
        if ($parsed < 3 || $parsed > 12) {
            return 5;
        }
        return $parsed;
    }

    private function lockMinutes(): int
    {
        $value = trim((string) (Env::get('MANAGER_LOGIN_LOCK_MINUTES', '15') ?? '15'));
        $parsed = ctype_digit($value) ? (int) $value : 15;
        if ($parsed < 1 || $parsed > 180) {
            return 15;
        }
        return $parsed;
    }

    private function windowMinutes(): int
    {
        $value = trim((string) (Env::get('MANAGER_LOGIN_WINDOW_MINUTES', '15') ?? '15'));
        $parsed = ctype_digit($value) ? (int) $value : 15;
        if ($parsed < 1 || $parsed > 180) {
            return 15;
        }
        return $parsed;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return mb_strtolower(trim($identifier), 'UTF-8');
    }

    private function normalizeIp(string $ipAddress): string
    {
        return trim($ipAddress) !== '' ? trim($ipAddress) : '0.0.0.0';
    }
}

