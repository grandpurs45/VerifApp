<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;
use Throwable;

final class VerificationRepository
{
    private ?bool $anomaliesTableExists = null;
    private ?bool $usersTableExists = null;
    private ?bool $utilisateurColumnExists = null;

    public function createWithLines(
        int $vehicleId,
        int $posteId,
        ?int $utilisateurId,
        string $agent,
        ?string $globalComment,
        array $lines
    ): int {
        $connection = Database::getConnection();
        $hasNok = false;

        foreach ($lines as $line) {
            if (($line['resultat'] ?? '') === 'nok') {
                $hasNok = true;
                break;
            }
        }

        $status = $hasNok ? 'non_conforme' : 'conforme';

        try {
            $connection->beginTransaction();

            if ($this->hasUtilisateurColumn()) {
                $verificationStatement = $connection->prepare(
                    '
                    INSERT INTO verifications (vehicule_id, poste_id, utilisateur_id, agent, date_heure, statut_global, commentaire_global)
                    VALUES (:vehicule_id, :poste_id, :utilisateur_id, :agent, NOW(), :statut_global, :commentaire_global)
                    '
                );

                $verificationStatement->execute([
                    'vehicule_id' => $vehicleId,
                    'poste_id' => $posteId,
                    'utilisateur_id' => $utilisateurId,
                    'agent' => $agent,
                    'statut_global' => $status,
                    'commentaire_global' => $globalComment,
                ]);
            } else {
                $verificationStatement = $connection->prepare(
                    '
                    INSERT INTO verifications (vehicule_id, poste_id, agent, date_heure, statut_global, commentaire_global)
                    VALUES (:vehicule_id, :poste_id, :agent, NOW(), :statut_global, :commentaire_global)
                    '
                );

                $verificationStatement->execute([
                    'vehicule_id' => $vehicleId,
                    'poste_id' => $posteId,
                    'agent' => $agent,
                    'statut_global' => $status,
                    'commentaire_global' => $globalComment,
                ]);
            }

            $verificationId = (int) $connection->lastInsertId();

            $lineStatement = $connection->prepare(
                '
                INSERT INTO verification_lignes (verification_id, controle_id, resultat, commentaire, photo)
                VALUES (:verification_id, :controle_id, :resultat, :commentaire, NULL)
                '
            );

            $anomalyStatement = null;
            if ($this->hasAnomaliesTable()) {
                $anomalyStatement = $connection->prepare(
                    '
                    INSERT INTO anomalies (verification_ligne_id, statut, priorite, commentaire, date_creation, date_resolution)
                    VALUES (:verification_ligne_id, :statut, :priorite, :commentaire, NOW(), NULL)
                    '
                );
            }

            foreach ($lines as $line) {
                $lineStatement->execute([
                    'verification_id' => $verificationId,
                    'controle_id' => (int) $line['controle_id'],
                    'resultat' => $line['resultat'],
                    'commentaire' => $line['commentaire'],
                ]);

                $verificationLineId = (int) $connection->lastInsertId();

                if ($line['resultat'] === 'nok' && $anomalyStatement !== null) {
                    $anomalyStatement->execute([
                        'verification_ligne_id' => $verificationLineId,
                        'statut' => 'ouverte',
                        'priorite' => 'moyenne',
                        'commentaire' => $line['commentaire'],
                    ]);
                }
            }

            $connection->commit();

            return $verificationId;
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function findHistory(array $filters): array
    {
        $connection = Database::getConnection();
        $hasAnomalies = $this->hasAnomaliesTable();
        $withUser = $this->hasUtilisateurColumn() && $this->hasUsersTable();

        $where = [];
        $params = [];

        if (($filters['vehicule_id'] ?? '') !== '') {
            $where[] = 'v.vehicule_id = :vehicule_id';
            $params['vehicule_id'] = (int) $filters['vehicule_id'];
        }

        if (($filters['poste_id'] ?? '') !== '') {
            $where[] = 'v.poste_id = :poste_id';
            $params['poste_id'] = (int) $filters['poste_id'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(v.date_heure) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(v.date_heure) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (($filters['statut_global'] ?? '') !== '') {
            $where[] = 'v.statut_global = :statut_global';
            $params['statut_global'] = $filters['statut_global'];
        }

        if (($filters['with_anomalies'] ?? '') === '1') {
            if ($hasAnomalies) {
                $where[] = '
                    EXISTS (
                        SELECT 1
                        FROM verification_lignes vl2
                        LEFT JOIN anomalies a2 ON a2.verification_ligne_id = vl2.id
                        WHERE vl2.verification_id = v.id
                          AND (vl2.resultat = \'nok\' OR a2.id IS NOT NULL)
                    )
                ';
            } else {
                $where[] = '
                    EXISTS (
                        SELECT 1
                        FROM verification_lignes vl2
                        WHERE vl2.verification_id = v.id
                          AND vl2.resultat = \'nok\'
                    )
                ';
            }
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $anomalySelect = $hasAnomalies
            ? 'SUM(CASE WHEN a.statut IN (\'ouverte\', \'en_cours\') THEN 1 ELSE 0 END) AS anomalies_ouvertes'
            : '0 AS anomalies_ouvertes';
        $anomalyJoin = $hasAnomalies ? 'LEFT JOIN anomalies a ON a.verification_ligne_id = vl.id' : '';

        $agentSelect = $withUser ? 'COALESCE(u.nom, v.agent) AS agent' : 'v.agent AS agent';
        $userJoin = $withUser ? 'LEFT JOIN utilisateurs u ON u.id = v.utilisateur_id' : '';

        $sql = '
            SELECT
                v.id,
                v.date_heure,
                ' . $agentSelect . ',
                v.statut_global,
                v.commentaire_global,
                veh.nom AS vehicule_nom,
                p.nom AS poste_nom,
                COUNT(vl.id) AS total_controles,
                SUM(CASE WHEN vl.resultat = \'nok\' THEN 1 ELSE 0 END) AS total_nok,
                ' . $anomalySelect . '
            FROM verifications v
            INNER JOIN vehicules veh ON veh.id = v.vehicule_id
            INNER JOIN postes p ON p.id = v.poste_id
            LEFT JOIN verification_lignes vl ON vl.verification_id = v.id
            ' . $userJoin . '
            ' . $anomalyJoin . '
            ' . $whereSql . '
            GROUP BY
                v.id,
                v.date_heure,
                ' . ($withUser ? 'COALESCE(u.nom, v.agent)' : 'v.agent') . ',
                v.statut_global,
                v.commentaire_global,
                veh.nom,
                p.nom
            ORDER BY v.date_heure DESC, v.id DESC
            LIMIT 200
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $verificationId): ?array
    {
        $connection = Database::getConnection();
        $withUser = $this->hasUtilisateurColumn() && $this->hasUsersTable();
        $agentSelect = $withUser ? 'COALESCE(u.nom, v.agent) AS agent' : 'v.agent AS agent';
        $userSelect = $withUser ? ', v.utilisateur_id' : ', NULL AS utilisateur_id';
        $userJoin = $withUser ? 'LEFT JOIN utilisateurs u ON u.id = v.utilisateur_id' : '';

        $sql = '
            SELECT
                v.id,
                v.vehicule_id,
                v.poste_id
                ' . $userSelect . ',
                v.date_heure,
                ' . $agentSelect . ',
                v.statut_global,
                v.commentaire_global,
                veh.nom AS vehicule_nom,
                p.nom AS poste_nom
            FROM verifications v
            INNER JOIN vehicules veh ON veh.id = v.vehicule_id
            INNER JOIN postes p ON p.id = v.poste_id
            ' . $userJoin . '
            WHERE v.id = :id
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['id' => $verificationId]);

        $verification = $statement->fetch(PDO::FETCH_ASSOC);

        if ($verification === false) {
            return null;
        }

        return $verification;
    }

    public function getDashboardStats(): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                COUNT(*) AS total_all,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() THEN 1 ELSE 0 END) AS total_today,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() AND statut_global = \'conforme\' THEN 1 ELSE 0 END) AS conformes_today,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() AND statut_global = \'non_conforme\' THEN 1 ELSE 0 END) AS non_conformes_today
            FROM verifications
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [
                'total_all' => 0,
                'total_today' => 0,
                'conformes_today' => 0,
                'non_conformes_today' => 0,
            ];
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return [
                'total_all' => 0,
                'total_today' => 0,
                'conformes_today' => 0,
                'non_conformes_today' => 0,
            ];
        }

        return [
            'total_all' => (int) ($row['total_all'] ?? 0),
            'total_today' => (int) ($row['total_today'] ?? 0),
            'conformes_today' => (int) ($row['conformes_today'] ?? 0),
            'non_conformes_today' => (int) ($row['non_conformes_today'] ?? 0),
        ];
    }

    public function findLinesByVerificationId(int $verificationId): array
    {
        $connection = Database::getConnection();
        $hasAnomalies = $this->hasAnomaliesTable();

        $anomalySelect = $hasAnomalies
            ? '
                a.id AS anomalie_id,
                a.statut AS anomalie_statut,
                a.priorite AS anomalie_priorite,
                a.commentaire AS anomalie_commentaire,
                a.date_creation AS anomalie_date_creation,
                a.date_resolution AS anomalie_date_resolution
            '
            : '
                NULL AS anomalie_id,
                NULL AS anomalie_statut,
                NULL AS anomalie_priorite,
                NULL AS anomalie_commentaire,
                NULL AS anomalie_date_creation,
                NULL AS anomalie_date_resolution
            ';
        $anomalyJoin = $hasAnomalies ? 'LEFT JOIN anomalies a ON a.verification_ligne_id = vl.id' : '';

        $sql = '
            SELECT
                vl.id,
                vl.controle_id,
                vl.resultat,
                vl.commentaire,
                c.zone,
                c.ordre,
                c.libelle,
                ' . $anomalySelect . '
            FROM verification_lignes vl
            INNER JOIN controles c ON c.id = vl.controle_id
            ' . $anomalyJoin . '
            WHERE vl.verification_id = :verification_id
            ORDER BY c.zone ASC, c.ordre ASC, c.libelle ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute(['verification_id' => $verificationId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasAnomaliesTable(): bool
    {
        if ($this->anomaliesTableExists !== null) {
            return $this->anomaliesTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'anomalies'");
            $this->anomaliesTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->anomaliesTableExists = false;
        }

        return $this->anomaliesTableExists;
    }

    private function hasUsersTable(): bool
    {
        if ($this->usersTableExists !== null) {
            return $this->usersTableExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW TABLES LIKE 'utilisateurs'");
            $this->usersTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->usersTableExists = false;
        }

        return $this->usersTableExists;
    }

    private function hasUtilisateurColumn(): bool
    {
        if ($this->utilisateurColumnExists !== null) {
            return $this->utilisateurColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM verifications LIKE 'utilisateur_id'");
            $this->utilisateurColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->utilisateurColumnExists = false;
        }

        return $this->utilisateurColumnExists;
    }
}
