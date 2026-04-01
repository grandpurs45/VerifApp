<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class ControleRepository
{
    private ?bool $isHierarchicalSchema = null;
    private ?bool $hasInputSchema = null;

    public function findByPosteId(int $posteId, ?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                libelle,
                caserne_id,
                ' . ($this->hasInputSchema() ? 'type_saisie,' : '\'statut\' AS type_saisie,') . '
                ' . ($this->hasInputSchema() ? 'valeur_attendue,' : 'NULL AS valeur_attendue,') . '
                ' . ($this->hasInputSchema() ? 'unite,' : 'NULL AS unite,') . '
                ' . ($this->hasInputSchema() ? 'seuil_min,' : 'NULL AS seuil_min,') . '
                ' . ($this->hasInputSchema() ? 'seuil_max,' : 'NULL AS seuil_max,') . '
                zone,
                ordre
            FROM controles
            WHERE poste_id = :poste_id
              AND actif = 1
              ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            ORDER BY zone ASC, ordre ASC, libelle ASC
        ';

        $statement = $connection->prepare($sql);
        $params = ['poste_id' => $posteId];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVehicleAndPosteId(int $vehicleId, int $posteId, ?int $caserneId = null): array
    {
        if (!$this->hasHierarchicalSchema()) {
            return $this->findByPosteId($posteId, $caserneId);
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                c.id,
                c.libelle,
                c.caserne_id,
                c.zone_id,
                ' . ($this->hasInputSchema() ? 'c.type_saisie,' : '\'statut\' AS type_saisie,') . '
                ' . ($this->hasInputSchema() ? 'c.valeur_attendue,' : 'NULL AS valeur_attendue,') . '
                ' . ($this->hasInputSchema() ? 'c.unite,' : 'NULL AS unite,') . '
                ' . ($this->hasInputSchema() ? 'c.seuil_min,' : 'NULL AS seuil_min,') . '
                ' . ($this->hasInputSchema() ? 'c.seuil_max,' : 'NULL AS seuil_max,') . '
                z.nom AS zone,
                c.ordre
            FROM controles c
            INNER JOIN zones z ON z.id = c.zone_id
            WHERE c.vehicule_id = :vehicule_id
              AND c.poste_id = :poste_id
              AND c.actif = 1
              ' . ($caserneId !== null ? 'AND c.caserne_id = :caserne_id' : '') . '
            ORDER BY z.nom ASC, c.ordre ASC, c.libelle ASC
        ';

        $statement = $connection->prepare($sql);

        $params = [
            'vehicule_id' => $vehicleId,
            'poste_id' => $posteId,
        ];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDetailed(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        if ($this->hasHierarchicalSchema()) {
            $sql = '
                SELECT
                    c.id,
                    c.libelle,
                    c.caserne_id,
                    ' . ($this->hasInputSchema() ? 'c.type_saisie,' : '\'statut\' AS type_saisie,') . '
                    ' . ($this->hasInputSchema() ? 'c.valeur_attendue,' : 'NULL AS valeur_attendue,') . '
                    ' . ($this->hasInputSchema() ? 'c.unite,' : 'NULL AS unite,') . '
                    ' . ($this->hasInputSchema() ? 'c.seuil_min,' : 'NULL AS seuil_min,') . '
                    ' . ($this->hasInputSchema() ? 'c.seuil_max,' : 'NULL AS seuil_max,') . '
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
                ' . ($caserneId !== null ? 'WHERE c.caserne_id = :caserne_id' : '') . '
                ORDER BY v.nom ASC, p.nom ASC, z.nom ASC, c.ordre ASC, c.libelle ASC
            ';
        } else {
            $sql = '
                SELECT
                    c.id,
                    c.libelle,
                    c.caserne_id,
                    ' . ($this->hasInputSchema() ? 'c.type_saisie,' : '\'statut\' AS type_saisie,') . '
                    ' . ($this->hasInputSchema() ? 'c.valeur_attendue,' : 'NULL AS valeur_attendue,') . '
                    ' . ($this->hasInputSchema() ? 'c.unite,' : 'NULL AS unite,') . '
                    ' . ($this->hasInputSchema() ? 'c.seuil_min,' : 'NULL AS seuil_min,') . '
                    ' . ($this->hasInputSchema() ? 'c.seuil_max,' : 'NULL AS seuil_max,') . '
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
                ' . ($caserneId !== null ? 'WHERE c.caserne_id = :caserne_id' : '') . '
                ORDER BY p.nom ASC, c.zone ASC, c.ordre ASC, c.libelle ASC
            ';
        }

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        string $label,
        int $posteId,
        string $zone,
        int $order,
        bool $active,
        int $caserneId,
        ?int $vehicleId = null,
        ?int $zoneId = null,
        string $inputType = 'statut',
        ?float $expectedValue = null,
        ?string $unit = null,
        ?float $minThreshold = null,
        ?float $maxThreshold = null
    ): bool {
        $connection = Database::getConnection();
        $hasInputSchema = $this->hasInputSchema();

        if ($this->hasHierarchicalSchema() && $vehicleId !== null && $zoneId !== null) {
            if ($hasInputSchema) {
                $sql = '
                    INSERT INTO controles (caserne_id, libelle, type_saisie, valeur_attendue, unite, seuil_min, seuil_max, poste_id, vehicule_id, zone_id, zone, ordre, actif)
                    VALUES (:caserne_id, :libelle, :type_saisie, :valeur_attendue, :unite, :seuil_min, :seuil_max, :poste_id, :vehicule_id, :zone_id, :zone, :ordre, :actif)
                ';
            } else {
                $sql = '
                    INSERT INTO controles (caserne_id, libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif)
                    VALUES (:caserne_id, :libelle, :poste_id, :vehicule_id, :zone_id, :zone, :ordre, :actif)
                ';
            }

            $statement = $connection->prepare($sql);

            $params = [
                'caserne_id' => $caserneId,
                'libelle' => $label,
                'poste_id' => $posteId,
                'vehicule_id' => $vehicleId,
                'zone_id' => $zoneId,
                'zone' => $zone,
                'ordre' => $order,
                'actif' => $active ? 1 : 0,
            ];

            if ($hasInputSchema) {
                $params['type_saisie'] = $inputType;
                $params['valeur_attendue'] = $expectedValue;
                $params['unite'] = $unit;
                $params['seuil_min'] = $minThreshold;
                $params['seuil_max'] = $maxThreshold;
            }

            return $statement->execute($params);
        }

        if ($hasInputSchema) {
            $sql = '
                INSERT INTO controles (caserne_id, libelle, type_saisie, valeur_attendue, unite, seuil_min, seuil_max, poste_id, zone, ordre, actif)
                VALUES (:caserne_id, :libelle, :type_saisie, :valeur_attendue, :unite, :seuil_min, :seuil_max, :poste_id, :zone, :ordre, :actif)
            ';
        } else {
            $sql = '
                INSERT INTO controles (caserne_id, libelle, poste_id, zone, ordre, actif)
                VALUES (:caserne_id, :libelle, :poste_id, :zone, :ordre, :actif)
            ';
        }

        $statement = $connection->prepare($sql);

        $params = [
            'caserne_id' => $caserneId,
            'libelle' => $label,
            'poste_id' => $posteId,
            'zone' => $zone,
            'ordre' => $order,
            'actif' => $active ? 1 : 0,
        ];

        if ($hasInputSchema) {
            $params['type_saisie'] = $inputType;
            $params['valeur_attendue'] = $expectedValue;
            $params['unite'] = $unit;
            $params['seuil_min'] = $minThreshold;
            $params['seuil_max'] = $maxThreshold;
        }

        return $statement->execute($params);
    }

    public function update(
        int $id,
        string $label,
        int $posteId,
        string $zone,
        int $order,
        bool $active,
        int $caserneId,
        ?int $vehicleId = null,
        ?int $zoneId = null,
        string $inputType = 'statut',
        ?float $expectedValue = null,
        ?string $unit = null,
        ?float $minThreshold = null,
        ?float $maxThreshold = null
    ): bool {
        $connection = Database::getConnection();
        $hasInputSchema = $this->hasInputSchema();

        if ($this->hasHierarchicalSchema() && $vehicleId !== null && $zoneId !== null) {
            if ($hasInputSchema) {
                $sql = '
                    UPDATE controles
                    SET libelle = :libelle,
                        type_saisie = :type_saisie,
                        valeur_attendue = :valeur_attendue,
                        unite = :unite,
                        seuil_min = :seuil_min,
                        seuil_max = :seuil_max,
                        poste_id = :poste_id,
                        vehicule_id = :vehicule_id,
                        zone_id = :zone_id,
                        zone = :zone,
                        ordre = :ordre,
                        actif = :actif
                    WHERE id = :id
                      AND caserne_id = :caserne_id
                ';
            } else {
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
                      AND caserne_id = :caserne_id
                ';
            }

            $statement = $connection->prepare($sql);

            $params = [
                'id' => $id,
                'caserne_id' => $caserneId,
                'libelle' => $label,
                'poste_id' => $posteId,
                'vehicule_id' => $vehicleId,
                'zone_id' => $zoneId,
                'zone' => $zone,
                'ordre' => $order,
                'actif' => $active ? 1 : 0,
            ];

            if ($hasInputSchema) {
                $params['type_saisie'] = $inputType;
                $params['valeur_attendue'] = $expectedValue;
                $params['unite'] = $unit;
                $params['seuil_min'] = $minThreshold;
                $params['seuil_max'] = $maxThreshold;
            }

            return $statement->execute($params);
        }

        if ($hasInputSchema) {
            $sql = '
                UPDATE controles
                SET libelle = :libelle,
                    type_saisie = :type_saisie,
                    valeur_attendue = :valeur_attendue,
                    unite = :unite,
                    seuil_min = :seuil_min,
                    seuil_max = :seuil_max,
                    poste_id = :poste_id,
                    zone = :zone,
                    ordre = :ordre,
                    actif = :actif
                WHERE id = :id
                  AND caserne_id = :caserne_id
            ';
        } else {
            $sql = '
                UPDATE controles
                SET libelle = :libelle,
                    poste_id = :poste_id,
                    zone = :zone,
                    ordre = :ordre,
                    actif = :actif
                WHERE id = :id
                  AND caserne_id = :caserne_id
            ';
        }

        $statement = $connection->prepare($sql);

        $params = [
            'id' => $id,
            'caserne_id' => $caserneId,
            'libelle' => $label,
            'poste_id' => $posteId,
            'zone' => $zone,
            'ordre' => $order,
            'actif' => $active ? 1 : 0,
        ];

        if ($hasInputSchema) {
            $params['type_saisie'] = $inputType;
            $params['valeur_attendue'] = $expectedValue;
            $params['unite'] = $unit;
            $params['seuil_min'] = $minThreshold;
            $params['seuil_max'] = $maxThreshold;
        }

        return $statement->execute($params);
    }

    public function delete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM controles WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
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

    public function hasInputSchema(): bool
    {
        if ($this->hasInputSchema !== null) {
            return $this->hasInputSchema;
        }

        $connection = Database::getConnection();

        try {
            $typeColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'type_saisie'");
            $expectedColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'valeur_attendue'");
            $unitColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'unite'");
            $minColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'seuil_min'");
            $maxColumn = $connection->query("SHOW COLUMNS FROM controles LIKE 'seuil_max'");

            $this->hasInputSchema =
                $typeColumn !== false && $typeColumn->fetchColumn() !== false &&
                $expectedColumn !== false && $expectedColumn->fetchColumn() !== false &&
                $unitColumn !== false && $unitColumn->fetchColumn() !== false &&
                $minColumn !== false && $minColumn->fetchColumn() !== false &&
                $maxColumn !== false && $maxColumn->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->hasInputSchema = false;
        }

        return $this->hasInputSchema;
    }
}
