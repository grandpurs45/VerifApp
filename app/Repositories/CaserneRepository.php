<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class CaserneRepository
{
    private ?bool $casernesTableExists = null;
    private ?bool $membershipTableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasCasernesTable() && $this->hasMembershipTable();
    }

    public function findAllActive(): array
    {
        if (!$this->hasCasernesTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->query('
            SELECT id, nom, code, actif
            FROM casernes
            WHERE actif = 1
            ORDER BY nom ASC
        ');

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        if (!$this->hasCasernesTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->query('
            SELECT id, nom, code, actif
            FROM casernes
            ORDER BY actif DESC, nom ASC
        ');

        return $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $caserneId): ?array
    {
        if (!$this->hasCasernesTable()) {
            return null;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT id, nom, code, actif
            FROM casernes
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $caserneId]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByUserId(int $userId): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT
                c.id,
                c.nom,
                c.code,
                c.actif,
                uc.is_default
            FROM utilisateur_casernes uc
            INNER JOIN casernes c ON c.id = uc.caserne_id
            WHERE uc.utilisateur_id = :utilisateur_id
              AND c.actif = 1
            ORDER BY uc.is_default DESC, c.nom ASC
        ');
        $statement->execute(['utilisateur_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdForUser(int $caserneId, int $userId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT
                c.id,
                c.nom,
                c.code,
                c.actif,
                uc.is_default
            FROM utilisateur_casernes uc
            INNER JOIN casernes c ON c.id = uc.caserne_id
            WHERE uc.utilisateur_id = :utilisateur_id
              AND uc.caserne_id = :caserne_id
              AND c.actif = 1
            LIMIT 1
        ');
        $statement->execute([
            'utilisateur_id' => $userId,
            'caserne_id' => $caserneId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(string $nom, string $code, bool $active): bool
    {
        if (!$this->hasCasernesTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO casernes (nom, code, actif)
            VALUES (:nom, :code, :actif)
        ');

        return $statement->execute([
            'nom' => $nom,
            'code' => $code,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function update(int $id, string $nom, string $code, bool $active): bool
    {
        if (!$this->hasCasernesTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE casernes
            SET nom = :nom, code = :code, actif = :actif
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $id,
            'nom' => $nom,
            'code' => $code,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function countActive(): int
    {
        if (!$this->hasCasernesTable()) {
            return 0;
        }

        $connection = Database::getConnection();
        $statement = $connection->query('SELECT COUNT(*) FROM casernes WHERE actif = 1');
        $value = $statement === false ? 0 : $statement->fetchColumn();

        return (int) $value;
    }

    private function hasCasernesTable(): bool
    {
        if ($this->casernesTableExists !== null) {
            return $this->casernesTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'casernes'");
            $this->casernesTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->casernesTableExists = false;
        }

        return $this->casernesTableExists;
    }

    private function hasMembershipTable(): bool
    {
        if ($this->membershipTableExists !== null) {
            return $this->membershipTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'utilisateur_casernes'");
            $this->membershipTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->membershipTableExists = false;
        }

        return $this->membershipTableExists;
    }
}
