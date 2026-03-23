<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class ControleRepository
{
    private ?bool $isHierarchicalSchema = null;

    public function findByPosteId(int $posteId): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                libelle,
                zone,
                ordre
            FROM controles
            WHERE poste_id = :poste_id
              AND actif = 1
            ORDER BY zone ASC, ordre ASC, libelle ASC
        ';

        $statement = $connection->prepare($sql);

        $statement->execute([
            'poste_id' => $posteId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVehicleAndPosteId(int $vehicleId, int $posteId): array
    {
        if (!$this->hasHierarchicalSchema()) {
            return $this->findByPosteId($posteId);
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                c.id,
                c.libelle,
                z.nom AS zone,
                c.ordre
            FROM controles c
            INNER JOIN zones z ON z.id = c.zone_id
            WHERE c.vehicule_id = :vehicule_id
              AND c.poste_id = :poste_id
              AND c.actif = 1
            ORDER BY z.nom ASC, c.ordre ASC, c.libelle ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute([
            'vehicule_id' => $vehicleId,
            'poste_id' => $posteId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDetailed(): array
    {
        $connection = Database::getConnection();

        if ($this->hasHierarchicalSchema()) {
            $sql = '
                SELECT
                    c.id,
                    c.libelle,
                    c.poste_id,
                    c.vehicule_id,
                    c.zone_id,
                    z.nom AS zone,
                    c.ordre,
                    c.actif,
                    p.nom AS poste_nom,
                    v.nom AS vehicule_nom
                FROM controles c
                INNER JOIN postes p ON p.id = c.poste_id
                INNER JOIN vehicules v ON v.id = c.vehicule_id
                INNER JOIN zones z ON z.id = c.zone_id
                ORDER BY v.nom ASC, p.nom ASC, z.nom ASC, c.ordre ASC, c.libelle ASC
            ';
        } else {
            $sql = '
                SELECT
                    c.id,
                    c.libelle,
                    c.poste_id,
                    NULL AS vehicule_id,
                    NULL AS zone_id,
                    c.zone AS zone,
                    c.ordre,
                    c.actif,
                    p.nom AS poste_nom,
                    NULL AS vehicule_nom
                FROM controles c
                INNER JOIN postes p ON p.id = c.poste_id
                ORDER BY p.nom ASC, c.zone ASC, c.ordre ASC, c.libelle ASC
            ';
        }

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        string $label,
        int $posteId,
        string $zone,
        int $order,
        bool $active,
        ?int $vehicleId = null,
        ?int $zoneId = null
    ): bool {
        $connection = Database::getConnection();

        if ($this->hasHierarchicalSchema() && $vehicleId !== null && $zoneId !== null) {
            $sql = '
                INSERT INTO controles (libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif)
                VALUES (:libelle, :poste_id, :vehicule_id, :zone_id, :zone, :ordre, :actif)
            ';

            $statement = $connection->prepare($sql);

            return $statement->execute([
                'libelle' => $label,
                'poste_id' => $posteId,
                'vehicule_id' => $vehicleId,
                'zone_id' => $zoneId,
                'zone' => $zone,
                'ordre' => $order,
                'actif' => $active ? 1 : 0,
            ]);
        }

        $sql = '
            INSERT INTO controles (libelle, poste_id, zone, ordre, actif)
            VALUES (:libelle, :poste_id, :zone, :ordre, :actif)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'libelle' => $label,
            'poste_id' => $posteId,
            'zone' => $zone,
            'ordre' => $order,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function update(
        int $id,
        string $label,
        int $posteId,
        string $zone,
        int $order,
        bool $active,
        ?int $vehicleId = null,
        ?int $zoneId = null
    ): bool {
        $connection = Database::getConnection();

        if ($this->hasHierarchicalSchema() && $vehicleId !== null && $zoneId !== null) {
            $sql = '
                UPDATE controles
                SET libelle = :libelle,
                    poste_id = :poste_id,
                    vehicule_id = :vehicule_id,
                    zone_id = :zone_id,
                    zone = :zone,
                    ordre = :ordre,
                    actif = :actif
                WHERE id = :id
            ';

            $statement = $connection->prepare($sql);

            return $statement->execute([
                'id' => $id,
                'libelle' => $label,
                'poste_id' => $posteId,
                'vehicule_id' => $vehicleId,
                'zone_id' => $zoneId,
                'zone' => $zone,
                'ordre' => $order,
                'actif' => $active ? 1 : 0,
            ]);
        }

        $sql = '
            UPDATE controles
            SET libelle = :libelle,
                poste_id = :poste_id,
                zone = :zone,
                ordre = :ordre,
                actif = :actif
            WHERE id = :id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'libelle' => $label,
            'poste_id' => $posteId,
            'zone' => $zone,
            'ordre' => $order,
            'actif' => $active ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM controles WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function hasHierarchicalSchema(): bool
    {
        if ($this->isHierarchicalSchema !== null) {
            return $this->isHierarchicalSchema;
        }

        $connection = Database::getConnection();

        try {
            $vehiculeColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'vehicule_id'");
            $zoneColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'zone_id'");
            $zonesTable = $connection->query("SHOW TABLES LIKE 'zones'");

            $this->isHierarchicalSchema =
                $vehiculeColumn !== false && $vehiculeColumn->fetchColumn() !== false &&
                $zoneColumn !== false && $zoneColumn->fetchColumn() !== false &&
                $zonesTable !== false && $zonesTable->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->isHierarchicalSchema = false;
        }

        return $this->isHierarchicalSchema;
    }
}
