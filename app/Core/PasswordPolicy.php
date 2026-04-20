<?php

declare(strict_types=1);

namespace App\Core;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /**
     * @return array{ok: bool, message: string}
     */
    public static function validate(string $password): array
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins ' . self::MIN_LENGTH . ' caracteres.',
            ];
        }

        if (!preg_match('/[a-z]/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.',
            ];
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
            ];
        }

        if (!preg_match('/\d/', $password)) {
            return [
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins un chiffre.',
            ];
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
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

