<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\AppSettingRepository;

final class PasswordPolicy
{
    public const DEFAULT_MIN_LENGTH = 12;
    public const MIN_MIN_LENGTH = 8;
    public const MAX_MIN_LENGTH = 64;

    /**
     * @return array{
     *   min_length: int,
     *   require_lower: bool,
     *   require_upper: bool,
     *   require_digit: bool,
     *   require_special: bool
     * }
     */
    public static function policy(): array
    {
        $repository = new AppSettingRepository();

        $minLength = self::DEFAULT_MIN_LENGTH;
        $requireLower = true;
        $requireUpper = true;
        $requireDigit = true;
        $requireSpecial = true;

        if ($repository->isAvailable()) {
            $minLengthRaw = trim((string) ($repository->get('password_min_length') ?? ''));
            if (ctype_digit($minLengthRaw)) {
                $parsed = (int) $minLengthRaw;
                if ($parsed >= self::MIN_MIN_LENGTH && $parsed <= self::MAX_MIN_LENGTH) {
                    $minLength = $parsed;
                }
            }

            $requireLower = ($repository->get('password_require_lower') ?? '1') === '1';
            $requireUpper = ($repository->get('password_require_upper') ?? '1') === '1';
            $requireDigit = ($repository->get('password_require_digit') ?? '1') === '1';
            $requireSpecial = ($repository->get('password_require_special') ?? '1') === '1';
        }

        // Safety net: keep at least one complexity requirement enabled.
        if (!$requireLower && !$requireUpper && !$requireDigit && !$requireSpecial) {
            $requireLower = true;
        }

        return [
            'min_length' => $minLength,
            'require_lower' => $requireLower,
            'require_upper' => $requireUpper,
            'require_digit' => $requireDigit,
            'require_special' => $requireSpecial,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function validate(string $password): array
    {
        $policy = self::policy();
        $minLength = (int) ($policy['min_length'] ?? self::DEFAULT_MIN_LENGTH);

        if (mb_strlen($password) < $minLength) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins ' . $minLength . ' caracteres.',
            ];
        }

        if (($policy['require_lower'] ?? true) && !preg_match('/[a-z]/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.',
            ];
        }

        if (($policy['require_upper'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
            ];
        }

        if (($policy['require_digit'] ?? true) && !preg_match('/\d/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins un chiffre.',
            ];
        }

        if (($policy['require_special'] ?? true) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins un caractere special.',
            ];
        }

        return [
            'ok' => true,
            'message' => '',
        ];
    }
}
