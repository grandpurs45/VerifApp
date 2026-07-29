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
    private ?bool $valeurSaisieColumnExists = null;
    private ?bool $zoneOrderColumnExists = null;
    private ?bool $guardDurationColumnExists = null;
    private ?bool $anomalyOccurrencesTableExists = null;

    public function createWithLines(
        int $caserneId,
        int $vehicleId,
        int $posteId,
        ?int $utilisateurId,
        string $agent,
        int $guardDurationHours,
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
        $guardDurationHours = $guardDurationHours === 24 ? 24 : 12;

        try {
            $connection->beginTransaction();

            if ($this->hasUtilisateurColumn()) {
                $columns = 'caserne_id, vehicule_id, poste_id, utilisateur_id, agent';
                $values = ':caserne_id, :vehicule_id, :poste_id, :utilisateur_id, :agent';
                $params = [
                    'caserne_id' => $caserneId,
                    'vehicule_id' => $vehicleId,
                    'poste_id' => $posteId,
                    'utilisateur_id' => $utilisateurId,
                    'agent' => $agent,
                    'statut_global' => $status,
                    'commentaire_global' => $globalComment,
                ];
            } else {
                $columns = 'caserne_id, vehicule_id, poste_id, agent';
                $values = ':caserne_id, :vehicule_id, :poste_id, :agent';
                $params = [
                    'caserne_id' => $caserneId,
                    'vehicule_id' => $vehicleId,
                    'poste_id' => $posteId,
                    'agent' => $agent,
                    'statut_global' => $status,
                    'commentaire_global' => $globalComment,
                ];
            }

            if ($this->hasGuardDurationColumn()) {
                $columns .= ', garde_duree_heures';
                $values .= ', :garde_duree_heures';
                $params['garde_duree_heures'] = $guardDurationHours;
            }

            $verificationStatement = $connection->prepare(
                '
                INSERT INTO verifications (' . $columns . ', date_heure, statut_global, commentaire_global)
                VALUES (' . $values . ', NOW(), :statut_global, :commentaire_global)
                '
            );
            $verificationStatement->execute($params);

            $verificationId = (int) $connection->lastInsertId();

            if ($this->hasValeurSaisieColumn()) {
                $lineStatement = $connection->prepare(
                    '
                    INSERT INTO verification_lignes (verification_id, controle_id, resultat, valeur_saisie, commentaire, photo)
                    VALUES (:verification_id, :controle_id, :resultat, :valeur_saisie, :commentaire, NULL)
                    '
                );
            } else {
                $lineStatement = $connection->prepare(
                    '
                    INSERT INTO verification_lignes (verification_id, controle_id, resultat, commentaire, photo)
                    VALUES (:verification_id, :controle_id, :resultat, :commentaire, NULL)
                    '
                );
            }

            $anomalyStatement = null;
            $activeAnomalyStatement = null;
            $occurrenceStatement = null;
            $controlLockStatement = null;
            if ($this->hasAnomaliesTable()) {
                $anomalyStatement = $connection->prepare(
                    '
                    INSERT INTO anomalies (verification_ligne_id, statut, priorite, commentaire, date_creation, date_resolution)
                    VALUES (:verification_ligne_id, :statut, :priorite, :commentaire, NOW(), NULL)
                    '
                );
                if ($this->hasAnomalyOccurrencesTable()) {
                    $controlLockStatement = $connection->prepare(
                        'SELECT id FROM controles WHERE id = :controle_id FOR UPDATE'
                    );
                    $activeAnomalyStatement = $connection->prepare('
                        SELECT a.id
                        FROM anomalies a
                        INNER JOIN anomaly_occurrences ao ON ao.anomalie_id = a.id
                        INNER JOIN verification_lignes previous_line ON previous_line.id = ao.verification_ligne_id
                        INNER JOIN verifications previous_verification ON previous_verification.id = previous_line.verification_id
                        WHERE previous_verification.caserne_id = :caserne_id
                          AND previous_verification.vehicule_id = :vehicule_id
                          AND previous_verification.poste_id = :poste_id
                          AND previous_line.controle_id = :controle_id
                          AND a.statut IN (\'ouverte\', \'en_cours\')
                        ORDER BY a.date_creation DESC, a.id DESC
                        LIMIT 1
                        FOR UPDATE
                    ');
                    $occurrenceStatement = $connection->prepare('
                        INSERT INTO anomaly_occurrences (anomalie_id, verification_ligne_id, date_remontee)
                        VALUES (:anomalie_id, :verification_ligne_id, NOW())
                    ');
                }
            }

            foreach ($lines as $line) {
                $lineParams = [
                    'verification_id' => $verificationId,
                    'controle_id' => (int) $line['controle_id'],
                    'resultat' => $line['resultat'],
                    'commentaire' => $line['commentaire'],
                ];

                if ($this->hasValeurSaisieColumn()) {
                    $lineParams['valeur_saisie'] = $line['valeur_saisie'] ?? null;
                }

                $lineStatement->execute($lineParams);

                $verificationLineId = (int) $connection->lastInsertId();

                if ($line['resultat'] === 'nok' && $anomalyStatement !== null) {
                    $anomalyId = 0;
                    if ($activeAnomalyStatement !== null) {
                        $controlLockStatement?->execute(['controle_id' => (int) $line['controle_id']]);
                        $activeAnomalyStatement->execute([
                            'caserne_id' => $caserneId,
                            'vehicule_id' => $vehicleId,
                            'poste_id' => $posteId,
                            'controle_id' => (int) $line['controle_id'],
                        ]);
                        $anomalyId = (int) ($activeAnomalyStatement->fetchColumn() ?: 0);
                    }
                    if ($anomalyId <= 0) {
                        $anomalyStatement->execute([
                            'verification_ligne_id' => $verificationLineId,
                            'statut' => 'ouverte',
                            'priorite' => 'moyenne',
                            'commentaire' => $line['commentaire'],
                        ]);
                        $anomalyId = (int) $connection->lastInsertId();
                    }
                    if ($occurrenceStatement !== null && $anomalyId > 0) {
                        $occurrenceStatement->execute([
                            'anomalie_id' => $anomalyId,
                            'verification_ligne_id' => $verificationLineId,
                        ]);
                    }
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

    public function findHistory(array $filters, ?int $caserneId = null): array
    {
        $connection = Database::getConnection();
        $hasAnomalies = $this->hasAnomaliesTable();
        $withUser = $this->hasUtilisateurColumn() && $this->hasUsersTable();

        $where = [];
        $params = [];

        if ($caserneId !== null) {
            $where[] = 'v.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
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
        $anomalyJoin = $hasAnomalies
            ? ($this->hasAnomalyOccurrencesTable()
                ? 'LEFT JOIN anomaly_occurrences ao ON ao.verification_ligne_id = vl.id LEFT JOIN anomalies a ON a.id = ao.anomalie_id'
                : 'LEFT JOIN anomalies a ON a.verification_ligne_id = vl.id')
            : '';
        $zoneOrderJoin = $this->hasZoneOrderColumn() ? 'LEFT JOIN zones zsort ON zsort.id = c.zone_id' : '';
        $zoneOrderSql = $this->hasZoneOrderColumn() ? 'COALESCE(zsort.ordre, 0) ASC,' : '';

        $agentSelect = $withUser ? 'COALESCE(u.nom, v.agent) AS agent' : 'v.agent AS agent';
        $userJoin = $withUser ? 'LEFT JOIN utilisateurs u ON u.id = v.utilisateur_id' : '';
        $guardDurationSelect = $this->hasGuardDurationColumn()
            ? 'v.garde_duree_heures'
            : '12 AS garde_duree_heures';
        $guardDurationGroup = $this->hasGuardDurationColumn() ? ', v.garde_duree_heures' : '';

        $sql = '
            SELECT
                v.id,
                v.date_heure,
                ' . $agentSelect . ',
                ' . $guardDurationSelect . ',
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
                ' . ($withUser ? 'COALESCE(u.nom, v.agent)' : 'v.agent') . '
                ' . $guardDurationGroup . ',
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

    public function findById(int $verificationId, ?int $caserneId = null): ?array
    {
        $connection = Database::getConnection();
        $withUser = $this->hasUtilisateurColumn() && $this->hasUsersTable();
        $agentSelect = $withUser ? 'COALESCE(u.nom, v.agent) AS agent' : 'v.agent AS agent';
        $userSelect = $withUser ? ', v.utilisateur_id' : ', NULL AS utilisateur_id';
        $userJoin = $withUser ? 'LEFT JOIN utilisateurs u ON u.id = v.utilisateur_id' : '';
        $guardDurationSelect = $this->hasGuardDurationColumn()
            ? 'v.garde_duree_heures'
            : '12 AS garde_duree_heures';

        $sql = '
            SELECT
                v.id,
                v.caserne_id,
                v.vehicule_id,
                v.poste_id
                ' . $userSelect . ',
                v.date_heure,
                ' . $agentSelect . ',
                ' . $guardDurationSelect . ',
                v.statut_global,
                v.commentaire_global,
                veh.nom AS vehicule_nom,
                p.nom AS poste_nom
            FROM verifications v
            INNER JOIN vehicules veh ON veh.id = v.vehicule_id
            INNER JOIN postes p ON p.id = v.poste_id
            ' . $userJoin . '
            WHERE v.id = :id
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);
        $params = ['id' => $verificationId];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }
        $statement->execute($params);

        $verification = $statement->fetch(PDO::FETCH_ASSOC);

        if ($verification === false) {
            return null;
        }

        return $verification;
    }

    public function deleteById(int $verificationId, ?int $caserneId = null): bool
    {
        if ($verificationId <= 0) {
            return false;
        }

        $connection = Database::getConnection();

        $lookupSql = '
            SELECT id
            FROM verifications
            WHERE id = :id
              ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            LIMIT 1
        ';
        $lookupParams = ['id' => $verificationId];
        if ($caserneId !== null) {
            $lookupParams['caserne_id'] = $caserneId;
        }

        $lookupStatement = $connection->prepare($lookupSql);
        $lookupStatement->execute($lookupParams);
        if ($lookupStatement->fetchColumn() === false) {
            return false;
        }

        try {
            $connection->beginTransaction();

            if ($this->hasAnomaliesTable()) {
                if ($this->hasAnomalyOccurrencesTable()) {
                    $reassignAnomalies = $connection->prepare('
                        UPDATE anomalies a
                        INNER JOIN verification_lignes original_line ON original_line.id = a.verification_ligne_id
                        SET a.verification_ligne_id = (
                            SELECT ao.verification_ligne_id
                            FROM anomaly_occurrences ao
                            INNER JOIN verification_lignes replacement_line ON replacement_line.id = ao.verification_ligne_id
                            WHERE ao.anomalie_id = a.id
                              AND replacement_line.verification_id <> :verification_id
                            ORDER BY ao.date_remontee ASC, ao.id ASC
                            LIMIT 1
                        )
                        WHERE original_line.verification_id = :verification_id
                          AND EXISTS (
                              SELECT 1
                              FROM anomaly_occurrences ao2
                              INNER JOIN verification_lignes replacement_line2 ON replacement_line2.id = ao2.verification_ligne_id
                              WHERE ao2.anomalie_id = a.id
                                AND replacement_line2.verification_id <> :verification_id
                          )
                    ');
                    $reassignAnomalies->execute(['verification_id' => $verificationId]);
                }
                $deleteAnomalies = $connection->prepare(
                    '
                    DELETE a
                    FROM anomalies a
                    INNER JOIN verification_lignes vl ON vl.id = a.verification_ligne_id
                    WHERE vl.verification_id = :verification_id
                    '
                );
                $deleteAnomalies->execute(['verification_id' => $verificationId]);
            }

            $deleteLines = $connection->prepare('DELETE FROM verification_lignes WHERE verification_id = :verification_id');
            $deleteLines->execute(['verification_id' => $verificationId]);

            $deleteVerificationSql = '
                DELETE FROM verifications
                WHERE id = :id
                  ' . ($caserneId !== null ? 'AND caserne_id = :caserne_id' : '') . '
            ';
            $deleteVerificationParams = ['id' => $verificationId];
            if ($caserneId !== null) {
                $deleteVerificationParams['caserne_id'] = $caserneId;
            }

            $deleteVerification = $connection->prepare($deleteVerificationSql);
            $deleteVerification->execute($deleteVerificationParams);
            $deleted = $deleteVerification->rowCount() > 0;

            $connection->commit();

            return $deleted;
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function getDashboardStats(?int $caserneId = null, int $eveningStartHour = 18): array
    {
        $connection = Database::getConnection();

        if ($eveningStartHour < 0 || $eveningStartHour > 23) {
            $eveningStartHour = 18;
        }

        $sql = '
            SELECT
                COUNT(*) AS total_all,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() THEN 1 ELSE 0 END) AS total_today,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() AND statut_global = \'conforme\' THEN 1 ELSE 0 END) AS conformes_today,
                SUM(CASE WHEN DATE(date_heure) = CURDATE() AND statut_global = \'non_conforme\' THEN 1 ELSE 0 END) AS non_conformes_today
            FROM verifications
            ' . ($caserneId !== null ? 'WHERE caserne_id = :caserne_id' : '') . '
        ';

        $statement = $connection->prepare($sql);
        $params = [];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }
        $statement->execute($params);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return [
                'total_all' => 0,
                'total_today' => 0,
                'conformes_today' => 0,
                'non_conformes_today' => 0,
                'month_slots_done' => 0,
                'month_slots_expected' => 0,
                'month_coverage_rate' => 0,
            ];
        }

        $monthStart = new \DateTimeImmutable('first day of this month');
        $today = new \DateTimeImmutable('today');
        $daysElapsed = (int) $monthStart->diff($today)->days;
        $monthSlotsExpected = max(0, $daysElapsed * 2);
        $guardDurationSql = $this->hasGuardDurationColumn() ? 'v.garde_duree_heures' : '12';
        $coverageSql = '
            SELECT COUNT(DISTINCT CONCAT(DATE(v.date_heure), \'|\', slots.creneau))
            FROM verifications v
            INNER JOIN (
                SELECT \'matin\' AS creneau
                UNION ALL
                SELECT \'soir\' AS creneau
            ) slots
                ON ' . $guardDurationSql . ' = 24
                OR slots.creneau = CASE
                    WHEN HOUR(v.date_heure) < :coverage_evening_start_hour THEN \'matin\'
                    ELSE \'soir\'
                END
            WHERE DATE(v.date_heure) >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\')
              AND DATE(v.date_heure) < CURDATE()
              ' . ($caserneId !== null ? 'AND v.caserne_id = :coverage_caserne_id' : '') . '
        ';
        $coverageStatement = $connection->prepare($coverageSql);
        $coverageParams = ['coverage_evening_start_hour' => $eveningStartHour];
        if ($caserneId !== null) {
            $coverageParams['coverage_caserne_id'] = $caserneId;
        }
        $coverageStatement->execute($coverageParams);
        $monthSlotsDone = (int) ($coverageStatement->fetchColumn() ?: 0);
        $monthCoverageRate = $monthSlotsExpected > 0
            ? (int) round(($monthSlotsDone / $monthSlotsExpected) * 100)
            : 0;

        return [
            'total_all' => (int) ($row['total_all'] ?? 0),
            'total_today' => (int) ($row['total_today'] ?? 0),
            'conformes_today' => (int) ($row['conformes_today'] ?? 0),
            'non_conformes_today' => (int) ($row['non_conformes_today'] ?? 0),
            'month_slots_done' => $monthSlotsDone,
            'month_slots_expected' => $monthSlotsExpected,
            'month_coverage_rate' => $monthCoverageRate,
        ];
    }

    public function findMonthlyDaySlotStats(
        int $year,
        int $month,
        int $eveningStartHour = 18,
        ?int $caserneId = null,
        ?int $vehicleId = null
    ): array
    {
        $connection = Database::getConnection();

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-d', strtotime($start . ' +1 month'));

        $where = [
            'v.date_heure >= :start_date',
            'v.date_heure < :end_date',
        ];
        $params = [
            'start_date' => $start . ' 00:00:00',
            'end_date' => $end . ' 00:00:00',
            'evening_start_hour_a' => $eveningStartHour,
        ];

        if ($caserneId !== null) {
            $where[] = 'v.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        if ($vehicleId !== null && $vehicleId > 0) {
            $where[] = 'v.vehicule_id = :vehicule_id';
            $params['vehicule_id'] = $vehicleId;
        }

        $guardDurationSql = $this->hasGuardDurationColumn() ? 'v.garde_duree_heures' : '12';
        $sql = '
            SELECT
                monthly_stats.jour,
                monthly_stats.creneau,
                COUNT(*) AS total_verifs,
                SUM(CASE WHEN monthly_stats.statut_global = \'conforme\' THEN 1 ELSE 0 END) AS conformes,
                SUM(CASE WHEN monthly_stats.statut_global = \'non_conforme\' THEN 1 ELSE 0 END) AS non_conformes
            FROM (
                SELECT
                    DATE(v.date_heure) AS jour,
                    slots.creneau,
                    v.statut_global
                FROM verifications v
                INNER JOIN (
                    SELECT \'matin\' AS creneau
                    UNION ALL
                    SELECT \'soir\' AS creneau
                ) slots
                    ON ' . $guardDurationSql . ' = 24
                    OR slots.creneau = CASE
                        WHEN HOUR(v.date_heure) < :evening_start_hour_a THEN \'matin\'
                        ELSE \'soir\'
                    END
                WHERE ' . implode(' AND ', $where) . '
            ) monthly_stats
            GROUP BY monthly_stats.jour, monthly_stats.creneau
            ORDER BY monthly_stats.jour ASC, monthly_stats.creneau ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findMonthlyDailyPosteStats(
        int $year,
        int $month,
        ?int $caserneId = null,
        ?int $vehicleId = null
    ): array
    {
        $connection = Database::getConnection();

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-d', strtotime($start . ' +1 month'));
        $where = [
            'v.date_heure >= :start_date',
            'v.date_heure < :end_date',
        ];
        $params = [
            'start_date' => $start . ' 00:00:00',
            'end_date' => $end . ' 00:00:00',
        ];

        if ($caserneId !== null) {
            $where[] = 'v.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        if ($vehicleId !== null && $vehicleId > 0) {
            $where[] = 'v.vehicule_id = :vehicule_id';
            $params['vehicule_id'] = $vehicleId;
        }

        $sql = '
            SELECT
                DATE(v.date_heure) AS jour,
                v.vehicule_id,
                v.poste_id,
                COUNT(*) AS total_verifs,
                SUM(CASE WHEN v.statut_global = \'conforme\' THEN 1 ELSE 0 END) AS conformes,
                SUM(CASE WHEN v.statut_global = \'non_conforme\' THEN 1 ELSE 0 END) AS non_conformes
            FROM verifications v
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY DATE(v.date_heure), v.vehicule_id, v.poste_id
            ORDER BY DATE(v.date_heure) ASC, v.vehicule_id ASC, v.poste_id ASC
        ';

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLinesByVerificationId(int $verificationId, ?int $caserneId = null): array
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
        $anomalyJoin = $hasAnomalies
            ? ($this->hasAnomalyOccurrencesTable()
                ? 'LEFT JOIN anomaly_occurrences ao ON ao.verification_ligne_id = vl.id LEFT JOIN anomalies a ON a.id = ao.anomalie_id'
                : 'LEFT JOIN anomalies a ON a.verification_ligne_id = vl.id')
            : '';

        $sql = '
            SELECT
                vl.id,
                vl.controle_id,
                vl.resultat,
                ' . ($this->hasValeurSaisieColumn() ? 'vl.valeur_saisie,' : 'NULL AS valeur_saisie,') . '
                vl.commentaire,
                c.zone_id,
                c.zone,
                c.ordre,
                c.libelle,
                ' . ($this->hasControleInputSchema() ? 'c.type_saisie,' : '\'statut\' AS type_saisie,') . '
                ' . ($this->hasControleInputSchema() ? 'c.valeur_attendue,' : 'NULL AS valeur_attendue,') . '
                ' . ($this->hasControleInputSchema() ? 'c.unite,' : 'NULL AS unite,') . '
                ' . ($this->hasControleInputSchema() ? 'c.seuil_min,' : 'NULL AS seuil_min,') . '
                ' . ($this->hasControleInputSchema() ? 'c.seuil_max,' : 'NULL AS seuil_max,') . '
                ' . $anomalySelect . '
            FROM verification_lignes vl
            INNER JOIN verifications v ON v.id = vl.verification_id
            INNER JOIN controles c ON c.id = vl.controle_id
            ' . $anomalyJoin . '
            ' . $zoneOrderJoin . '
            WHERE vl.verification_id = :verification_id
              ' . ($caserneId !== null ? 'AND v.caserne_id = :caserne_id' : '') . '
            ORDER BY ' . $zoneOrderSql . ' c.zone ASC, c.ordre ASC, c.libelle ASC
        ';

        $statement = $connection->prepare($sql);
        $params = ['verification_id' => $verificationId];
        if ($caserneId !== null) {
            $params['caserne_id'] = $caserneId;
        }
        $statement->execute($params);

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

    private function hasAnomalyOccurrencesTable(): bool
    {
        if ($this->anomalyOccurrencesTableExists !== null) {
            return $this->anomalyOccurrencesTableExists;
        }

        try {
            $statement = Database::getConnection()->query("SHOW TABLES LIKE 'anomaly_occurrences'");
            $this->anomalyOccurrencesTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException) {
            $this->anomalyOccurrencesTableExists = false;
        }

        return $this->anomalyOccurrencesTableExists;
    }

    private function hasZoneOrderColumn(): bool
    {
        if ($this->zoneOrderColumnExists !== null) {
            return $this->zoneOrderColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM zones LIKE 'ordre'");
            $this->zoneOrderColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->zoneOrderColumnExists = false;
        }

        return $this->zoneOrderColumnExists;
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

    private function hasValeurSaisieColumn(): bool
    {
        if ($this->valeurSaisieColumnExists !== null) {
            return $this->valeurSaisieColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM verification_lignes LIKE 'valeur_saisie'");
            $this->valeurSaisieColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->valeurSaisieColumnExists = false;
        }

        return $this->valeurSaisieColumnExists;
    }

    private function hasGuardDurationColumn(): bool
    {
        if ($this->guardDurationColumnExists !== null) {
            return $this->guardDurationColumnExists;
        }

        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM verifications LIKE 'garde_duree_heures'");
            $this->guardDurationColumnExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            $this->guardDurationColumnExists = false;
        }

        return $this->guardDurationColumnExists;
    }

    private function hasControleInputSchema(): bool
    {
        $connection = Database::getConnection();

        try {
            $statement = $connection->query("SHOW COLUMNS FROM controles LIKE 'type_saisie'");
            return $statement !== false && $statement->fetchColumn() !== false;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
