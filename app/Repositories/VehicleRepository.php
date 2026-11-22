<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Throwable;

final class VehicleRepository
{
    public function findAllActive(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            WHERE v.actif = 1
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllVerificationEnabled(?int $caserneId = null): array
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT v.id, v.nom, v.verification_active, v.verification_frequency, tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            WHERE v.actif = 1
              AND v.verification_active = 1
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC
        ');
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllDetailed(?int $caserneId = null): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.caserne_id,
                v.actif,
                v.verification_active,
                v.verification_frequency,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
            ' . ($caserneId !== null ? 'WHERE v.caserne_id = :caserne_id' : '') . '
            ORDER BY v.nom ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($caserneId !== null ? ['caserne_id' => $caserneId] : []);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                v.id,
                v.nom,
                v.type_vehicule_id,
                v.caserne_id,
                v.actif,
                v.verification_active,
                v.verification_frequency,
                tv.nom AS type_vehicule
            FROM vehicules v
            INNER JOIN type_vehicules tv
                ON tv.id = v.type_vehicule_id
            WHERE v.id = :id
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);

        $params = ['id' => $id];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }

        $statement->execute($params);

        $vehicle = $statement->fetch(PDO::FETCH_ASSOC);

        if ($vehicle === false) {
            return null;
        }

        return $vehicle;
    }

    public function create(
        string $name,
        int $typeVehiculeId,
        bool $active,
        int $caserneId,
        bool $verificationActive = false,
        string $verificationFrequency = 'daily'
    ): bool
    {
        $connection = Database::getConnection();
        $verificationFrequency = $verificationFrequency === 'twice_daily' ? 'twice_daily' : 'daily';

        $sql = '
            INSERT INTO vehicules (caserne_id, nom, type_vehicule_id, actif, verification_active, verification_frequency)
            VALUES (:caserne_id, :nom, :type_vehicule_id, :actif, :verification_active, :verification_frequency)
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'caserne_id' => $caserneId,
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
            'verification_active' => $verificationActive ? 1 : 0,
            'verification_frequency' => $verificationFrequency,
        ]);
    }

    public function createAndReturnId(
        string $name,
        int $typeVehiculeId,
        bool $active,
        int $caserneId,
        bool $verificationActive = false,
        string $verificationFrequency = 'daily'
    ): ?int
    {
        $created = $this->create(
            $name,
            $typeVehiculeId,
            $active,
            $caserneId,
            $verificationActive,
            $verificationFrequency
        );
        if (!$created) {
            return null;
        }

        $connection = Database::getConnection();
        $id = (int) $connection->lastInsertId();

        return $id > 0 ? $id : null;
    }

    public function update(
        int $id,
        string $name,
        int $typeVehiculeId,
        bool $active,
        int $caserneId,
        bool $verificationActive = false,
        string $verificationFrequency = 'daily'
    ): bool
    {
        $connection = Database::getConnection();
        $verificationFrequency = $verificationFrequency === 'twice_daily' ? 'twice_daily' : 'daily';

        $sql = '
            UPDATE vehicules
            SET nom = :nom,
                type_vehicule_id = :type_vehicule_id,
                actif = :actif,
                verification_active = :verification_active,
                verification_frequency = :verification_frequency
            WHERE id = :id
              AND caserne_id = :caserne_id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'caserne_id' => $caserneId,
            'nom' => $name,
            'type_vehicule_id' => $typeVehiculeId,
            'actif' => $active ? 1 : 0,
            'verification_active' => $verificationActive ? 1 : 0,
            'verification_frequency' => $verificationFrequency,
        ]);
    }

    public function updateIdentity(int $id, string $name, int $typeId, int $caserneId): bool
    {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $lock = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $db->prepare('SELECT type_vehicule_id FROM vehicules WHERE id = :id AND caserne_id = :caserne' . $lock);
            $statement->execute(['id' => $id, 'caserne' => $caserneId]);
            $oldType = $statement->fetchColumn();
            $type = $db->prepare('SELECT nom FROM type_vehicules WHERE id = :id AND caserne_id = :caserne');
            $type->execute(['id' => $typeId, 'caserne' => $caserneId]);
            $targetTypeName = $type->fetchColumn();
            if ($oldType === false || $targetTypeName === false || trim($name) === '' || $this->existsByTypeAndName($typeId, $name, $caserneId, $id)) {
                $db->rollBack();
                return false;
            }
            $source = $db->prepare('SELECT DISTINCT p.id, p.nom, p.code, p.type_vehicule_id, tv.nom AS type_nom FROM controles c INNER JOIN postes p ON p.id = c.poste_id INNER JOIN type_vehicules tv ON tv.id = p.type_vehicule_id AND tv.caserne_id = p.caserne_id WHERE c.vehicule_id = :vehicle AND c.caserne_id = :caserne AND p.caserne_id = :poste_caserne');
            $source->execute(['vehicle' => $id, 'caserne' => $caserneId, 'poste_caserne' => $caserneId]);
            $sourcePostes = $source->fetchAll(PDO::FETCH_ASSOC);
            if ($sourcePostes !== []) {
                foreach ($sourcePostes as $poste) {
                    $targetName = $this->posteNameForType($db, $poste, (string) $targetTypeName, $caserneId);
                    if ((int) $poste['type_vehicule_id'] === $typeId && $targetName === $poste['nom']) continue;
                    $target = $db->prepare('SELECT id FROM postes WHERE type_vehicule_id = :type AND caserne_id = :caserne AND nom = :nom ORDER BY id ASC');
                    $target->execute(['type' => $typeId, 'caserne' => $caserneId, 'nom' => $targetName]);
                    $targetId = $target->fetchColumn();
                    if ($targetId === false) {
                        $candidates = $db->prepare('SELECT id, nom FROM postes WHERE type_vehicule_id = :type AND caserne_id = :caserne');
                        $candidates->execute(['type' => $typeId, 'caserne' => $caserneId]);
                        $role = $this->posteRoleName($targetName, (string) $targetTypeName);
                        $matches = array_values(array_filter($candidates->fetchAll(PDO::FETCH_ASSOC), fn (array $candidate): bool => $this->posteRoleName($candidate['nom'], (string) $targetTypeName) === $role));
                        if (count($matches) > 1) throw new \RuntimeException('Plusieurs postes correspondent au meme role dans le type cible.');
                        if (count($matches) === 1) $targetId = $matches[0]['id'];
                    }
                    if ($targetId === false) {
                        $baseCode = 'P' . $typeId . '_' . $poste['id'];
                        $code = $baseCode;
                        $suffix = 0;
                        $codeCheck = $db->prepare('SELECT id FROM postes WHERE caserne_id = :caserne AND code = :code');
                        do {
                            $codeCheck->execute(['caserne' => $caserneId, 'code' => $code]);
                            $exists = $codeCheck->fetchColumn() !== false;
                            if ($exists) $code = $baseCode . '_' . ++$suffix;
                        } while ($exists);
                        $insert = $db->prepare('INSERT INTO postes (caserne_id, nom, code, type_vehicule_id) VALUES (:caserne, :nom, :code, :type)');
                        $insert->execute(['caserne' => $caserneId, 'nom' => $targetName, 'code' => $code, 'type' => $typeId]);
                        $targetId = (int) $db->lastInsertId();
                    }
                    // Keep historical postes unchanged; move only this vehicle's controls.
                    $move = $db->prepare('UPDATE controles SET poste_id = :target WHERE vehicule_id = :vehicle AND caserne_id = :caserne AND poste_id = :source');
                    $move->execute(['target' => $targetId, 'vehicle' => $id, 'caserne' => $caserneId, 'source' => $poste['id']]);
                }
            }
            $update = $db->prepare('UPDATE vehicules SET nom = :nom, type_vehicule_id = :type WHERE id = :id AND caserne_id = :caserne');
            $update->execute(['nom' => $name, 'type' => $typeId, 'id' => $id, 'caserne' => $caserneId]);
            $db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
    }

    private function posteNameForType(PDO $db, array $poste, string $targetType, int $caserneId): string
    {
        $origin = $poste;
        $visited = [];
        // Older automatic copies kept their source label; trace only those exact copies.
        while (!isset($visited[(int) $origin['id']]) && preg_match('/^P' . (int) $origin['type_vehicule_id'] . '_(\d+)(?:_\d+)?$/', (string) $origin['code'], $match)) {
            $visited[(int) $origin['id']] = true;
            $query = $db->prepare('SELECT p.id, p.nom, p.code, p.type_vehicule_id, tv.nom AS type_nom FROM postes p INNER JOIN type_vehicules tv ON tv.id = p.type_vehicule_id AND tv.caserne_id = p.caserne_id WHERE p.id = :id AND p.caserne_id = :caserne');
            $query->execute(['id' => (int) $match[1], 'caserne' => $caserneId]);
            $parent = $query->fetch(PDO::FETCH_ASSOC);
            if (!$parent || $parent['nom'] !== $origin['nom']) break;
            $origin = $parent;
        }
        $pattern = '/\s+' . preg_quote((string) $origin['type_nom'], '/') . '$/iu';
        $label = preg_replace($pattern, ' ' . $targetType, (string) $origin['nom']);
        if ($label === null || mb_strlen($label) > 100) throw new \RuntimeException('Libelle du poste cible invalide.');
        return $label;
    }

    private function posteRoleName(string $name, string $type): string
    {
        return mb_strtolower(trim(preg_replace('/\s+' . preg_quote($type, '/') . '$/iu', '', $name) ?? $name), 'UTF-8');
    }

    public function delete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();
        $statement = $connection->prepare('DELETE FROM vehicules WHERE id = :id AND caserne_id = :caserne_id');

        return $statement->execute(['id' => $id, 'caserne_id' => $caserneId]);
    }

    public function existsByTypeAndName(int $typeVehiculeId, string $name, int $caserneId, ?int $excludeId = null): bool
    {
        $connection = Database::getConnection();
        $sql = '
            SELECT 1
            FROM vehicules
            WHERE type_vehicule_id = :type_vehicule_id
              AND nom = :nom
              AND caserne_id = :caserne_id
        ';

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= ' LIMIT 1';

        $statement = $connection->prepare($sql);
        $params = [
            'type_vehicule_id' => $typeVehiculeId,
            'nom' => $name,
            'caserne_id' => $caserneId,
        ];
        if ($excludeId !== null && $excludeId > 0) {
            $params['exclude_id'] = $excludeId;
        }

        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function forceDelete(int $id, int $caserneId): bool
    {
        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();

            // 1) Supprime les sessions de verification du vehicule
            // (verification_lignes + anomalies sont supprimees en cascade).
            $deleteVerifications = $connection->prepare('
                DELETE FROM verifications
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $deleteVerifications->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);

            // 2) Supprime le materiel/controles du vehicule.
            $deleteControles = $connection->prepare('
                DELETE FROM controles
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $deleteControles->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);

            // 3) Supprime les zones restantes (enfants -> parents).
            // Le FK zones.parent_id est en RESTRICT, donc on supprime les feuilles par vagues.
            while (true) {
                $deleteLeafZones = $connection->prepare('
                    DELETE z
                    FROM zones z
                    LEFT JOIN zones c ON c.parent_id = z.id
                    WHERE z.vehicule_id = :vehicule_id
                      AND z.caserne_id = :caserne_id
                      AND c.id IS NULL
                ');
                $deleteLeafZones->execute([
                    'vehicule_id' => $id,
                    'caserne_id' => $caserneId,
                ]);

                $deletedRows = $deleteLeafZones->rowCount();
                if ($deletedRows === 0) {
                    break;
                }
            }

            $remainingZones = $connection->prepare('
                SELECT COUNT(*) FROM zones
                WHERE vehicule_id = :vehicule_id
                  AND caserne_id = :caserne_id
            ');
            $remainingZones->execute([
                'vehicule_id' => $id,
                'caserne_id' => $caserneId,
            ]);
            if ((int) $remainingZones->fetchColumn() > 0) {
                throw new \RuntimeException('vehicle_force_delete_zones_failed');
            }

            // 4) Supprime le vehicule.
            $deleteVehicle = $connection->prepare('
                DELETE FROM vehicules
                WHERE id = :id
                  AND caserne_id = :caserne_id
            ');
            $ok = $deleteVehicle->execute([
                'id' => $id,
                'caserne_id' => $caserneId,
            ]);

            if (!$ok) {
                throw new \RuntimeException('vehicle_force_delete_failed');
            }

            $connection->commit();
            return true;
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $throwable;
        }
    }
}
