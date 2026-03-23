<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class AnomalyRepository
{
    private ?bool $tableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function findAll(array $filters): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $connection = Database::getConnection();
        $where = [];
        $params = [];

        if (($filters['statut'] ?? '') !== '') {
            $where[] = 'a.statut = :statut';
            $params['statut'] = $filters['statut'];
        }

        if (($filters['priorite'] ?? '') !== '') {
            $where[] = 'a.priorite = :priorite';
            $params['priorite'] = $filters['priorite'];
        }

        if (($filters['vehicule_id'] ?? '') !== '') {
            $where[] = 'v.vehicule_id = :vehicule_id';
            $params['vehicule_id'] = (int) $filters['vehicule_id'];
        }

        if (($filters['poste_id'] ?? '') !== '') {
            $where[] = 'v.poste_id = :poste_id';
            $params['poste_id'] = (int) $filters['poste_id'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(a.date_creation) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(a.date_creation) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = '
            SELECT
                a.id,
                a.verification_ligne_id,
                a.statut,
                a.priorite,
                a.commentaire,
                a.date_creation,
                a.date_resolution,
                v.id AS verification_id,
                v.date_heure AS verification_date,
                v.agent AS verification_agent,
                veh.nom AS vehicule_nom,
                p.nom AS poste_nom,
                c.zone AS controle_zone,
                c.libelle AS controle_libelle
            FROM anomalies a
            INNER JOIN verification_lignes vl ON vl.id = a.verification_ligne_id
            INNER JOIN verifications v ON v.id = vl.verification_id
            INNER JOIN vehicules veh ON veh.id = v.vehicule_id
            INNER JOIN postes p ON p.id = v.poste_id
            INNER JOIN controles c ON c.id = vl.controle_id
            ' . $whereSql . '
            ORDER BY
                CASE a.statut
                    WHEN \'ouverte\' THEN 1
                    WHEN \'en_cours\' THEN 2
                    WHEN \'resolue\' THEN 3
                    WHEN \'cloturee\' THEN 4
                    ELSE 5
                END ASC,
                a.date_creation DESC,
                a.id DESC
            LIMIT 300
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $anomalyId, string $status, string $priority, ?string $comment): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $isResolvedStatus = in_array($status, ['resolue', 'cloturee'], true);

        $sql = '
            UPDATE anomalies
            SET
                statut = :statut,
                priorite = :priorite,
                commentaire = :commentaire,
                date_resolution = CASE
                    WHEN :is_resolved = 1 THEN COALESCE(date_resolution, NOW())
                    ELSE NULL
                END
            WHERE id = :id
        ';

        $statement = $connection->prepare($sql);

        return $statement->execute([
            'statut' => $status,
            'priorite' => $priority,
            'commentaire' => $comment,
            'is_resolved' => $isResolvedStatus ? 1 : 0,
            'id' => $anomalyId,
        ]);
    }

    public function getStatusStats(): array
    {
        if (!$this->hasTable()) {
            return [
                'ouverte' => 0,
                'en_cours' => 0,
                'resolue' => 0,
                'cloturee' => 0,
            ];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                statut,
                COUNT(*) AS total
            FROM anomalies
            GROUP BY statut
        ';

        $statement = $connection->query($sql);

        $stats = [
            'ouverte' => 0,
            'en_cours' => 0,
            'resolue' => 0,
            'cloturee' => 0,
        ];

        if ($statement === false) {
            return $stats;
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $status = (string) ($row['statut'] ?? '');
            if (array_key_exists($status, $stats)) {
                $stats[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $stats;
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'anomalies'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }
}
