<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AppSettingRepository
{
    private ?bool $tableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function get(string $key): ?string
    {
        if (!$this->hasTable()) {
            return null;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->prepare('
                SELECT setting_value
                FROM app_settings
                WHERE setting_key = :setting_key
                LIMIT 1
            ');
            $statement->execute(['setting_key' => $key]);
            $value = $statement->fetchColumn();

            return $value === false ? null : (string) $value;
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    public function set(string $key, string $value): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->prepare('
                INSERT INTO app_settings (setting_key, setting_value)
                VALUES (:setting_key, :setting_value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ');

            return $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    public function hasAnyWithPrefix(string $prefix): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->prepare('
                SELECT 1
                FROM app_settings
                WHERE setting_key LIKE :prefix
                LIMIT 1
            ');
            $statement->execute(['prefix' => $prefix . '%']);

            return $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->query("SHOW TABLES LIKE 'app_settings'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }
}
