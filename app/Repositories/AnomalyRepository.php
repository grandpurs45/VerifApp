<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

final class AnomalyRepository
{
    private ?bool $tableExists = null;
    private ?bool $assigneeColumnExists = null;

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
            if (($filters['statut'] ?? '') === 'actives') {
                $where[] = "a.statut IN ('ouverte', 'en_cours')";
            } else {
                $where[] = 'a.statut = :statut';
                $params['statut'] = $filters['statut'];
            }
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

        if (($filters['assigne_a'] ?? '') !== '') {
            if (($filters['assigne_a'] ?? '') === 'none') {
                $where[] = 'a.assigne_a IS NULL';
            } elseif (ctype_digit((string) $filters['assigne_a'])) {
                $where[] = 'a.assigne_a = :assigne_a';
                $params['assigne_a'] = (int) $filters['assigne_a'];
            }
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $assigneeSelect = $this->hasAssigneeColumn()
            ? 'a.assigne_a, COALESCE(ua.nom, ua.email) AS assigne_nom'
            : 'NULL AS assigne_a, NULL AS assigne_nom';
        $assigneeJoin = $this->hasAssigneeColumn() ? 'LEFT JOIN utilisateurs ua ON ua.id = a.assigne_a' : '';

        $sql = '
            SELECT
                a.id,
                a.verification_ligne_id,
                a.statut,
                a.priorite,
                ' . $assigneeSelect . ',
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
            ' . $assigneeJoin . '
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

    public function updateStatus(int $anomalyId, string $status, string $priority, ?string $comment, ?int $assigneeId): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $connection = Database::getConnection();
        $isResolvedStatus = in_array($status, ['resolue', 'cloturee'], true);

        if ($this->hasAssigneeColumn()) {
            $sql = '
                UPDATE anomalies
                SET
                    statut = :statut,
                    priorite = :priorite,
                    assigne_a = :assigne_a,
                    commentaire = :commentaire,
                    date_resolution = CASE
                        WHEN :is_resolved = 1 THEN COALESCE(date_resolution, NOW())
                        ELSE NULL
                    END
                WHERE id = :id
            ';
        } else {
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
        }

        $statement = $connection->prepare($sql);

        $params = [
            'statut' => $status,
            'priorite' => $priority,
            'commentaire' => $comment,
            'is_resolved' => $isResolvedStatus ? 1 : 0,
            'id' => $anomalyId,
        ];

        if ($this->hasAssigneeColumn()) {
            $params['assigne_a'] = $assigneeId;
        }

        return $statement->execute($params);
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
            if ($status === 'cloturee') {
                $stats['resolue'] += (int) ($row['total'] ?? 0);
            } elseif (array_key_exists($status, $stats)) {
                $stats[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $stats;
    }

    public function getAssignmentStats(?int $userId): array
    {
        if (!$this->hasTable() || !$this->hasAssigneeColumn()) {
            return [
                'non_assignees' => 0,
                'mes_anomalies' => 0,
            ];
        }

        $connection = Database::getConnection();
        $sql = '
            SELECT
                SUM(CASE WHEN a.assigne_a IS NULL AND a.statut IN (\'ouverte\', \'en_cours\') THEN 1 ELSE 0 END) AS non_assignees,
                SUM(CASE WHEN :user_id > 0 AND a.assigne_a = :user_id AND a.statut IN (\'ouverte\', \'en_cours\') THEN 1 ELSE 0 END) AS mes_anomalies
            FROM anomalies a
        ';
        $statement = $connection->prepare($sql);
        $statement->execute(['user_id' => $userId ?? 0]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return [
                'non_assignees' => 0,
                'mes_anomalies' => 0,
            ];
        }

        return [
            'non_assignees' => (int) ($row['non_assignees'] ?? 0),
            'mes_anomalies' => (int) ($row['mes_anomalies'] ?? 0),
        ];
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

    private function hasAssigneeColumn(): bool
    {
        if ($this->assigneeColumnExists !== null) {
            return $this->assigneeColumnExists;
        }

        if (!$this->hasTable()) {
            $this->assigneeColumnExists = false;
            return false;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM anomalies LIKE 'assigne_a'");
            $this->assigneeColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->assigneeColumnExists = false;
        }

        return $this->assigneeColumnExists;
    }
}
