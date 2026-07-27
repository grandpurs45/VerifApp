<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\ControleRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\VerificationRepository;
use App\Repositories\ZoneRepository;

final class VerificationController
{
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php');
        }

        $fieldUser = $_SESSION['field_user'] ?? null;

        $vehicleId = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
        $posteId = isset($_POST['poste_id']) ? (int) $_POST['poste_id'] : 0;
        $agent = '';
        $utilisateurId = null;

        if (is_array($fieldUser) && isset($fieldUser['id'], $fieldUser['nom'])) {
            $agent = trim((string) $fieldUser['nom']);
            $utilisateurId = (int) $fieldUser['id'];
        } else {
            $agent = trim((string) ($_POST['agent'] ?? ''));
        }
        $globalComment = trim((string) ($_POST['commentaire_global'] ?? ''));
        $fromVehicleQr = isset($_POST['from_vehicle_qr']) && (string) $_POST['from_vehicle_qr'] === '1';
        $contextSuffix = $fromVehicleQr ? '&from_vehicle_qr=1' : '';

        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();
        $verificationRepository = new VerificationRepository();
        $caserneId = $this->resolveActiveCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=home&action=index');
        }

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        $poste = $posteRepository->findByIdForVehicle($posteId, $vehicleId, $caserneId);

        if ($vehicle === null || $poste === null) {
            $this->redirect('/index.php?controller=home&action=index');
        }

        if ($agent === '') {
            $this->redirect(
                '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=agent_required' . $contextSuffix
            );
        }

        $controles = $controleRepository->findByVehicleAndPosteId($vehicleId, $posteId, $caserneId);
        $resultats = is_array($_POST['resultats'] ?? null) ? $_POST['resultats'] : [];
        $values = is_array($_POST['valeurs'] ?? null) ? $_POST['valeurs'] : [];
        $commentaires = is_array($_POST['commentaires'] ?? null) ? $_POST['commentaires'] : [];
        $allowedStatuses = ['ok', 'nok'];
        $lines = [];

        foreach ($controles as $controle) {
            $controleId = (int) $controle['id'];
            $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));
            if (!in_array($inputType, ['statut', 'quantite', 'mesure'], true)) {
                $inputType = 'statut';
            }

            $result = '';
            $valueInput = null;

            if ($inputType === 'statut' || $inputType === 'quantite') {
                $rawResult = $resultats[(string) $controleId] ?? null;
                $result = is_string($rawResult) ? strtolower(trim($rawResult)) : '';

                if (!in_array($result, $allowedStatuses, true)) {
                    $this->redirect(
                        '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=incomplete' . $contextSuffix
                    );
                }
            } else {
                $rawValue = $values[(string) $controleId] ?? null;
                $valueString = is_string($rawValue) ? trim($rawValue) : '';

                if ($valueString === '' || filter_var($valueString, FILTER_VALIDATE_INT) === false) {
                    $this->redirect(
                        '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=incomplete' . $contextSuffix
                    );
                }

                $valueInput = (float) ((int) $valueString);

                $result = $this->computeResultForNumericControl($controle, $valueInput);
            }

            $rawComment = $commentaires[(string) $controleId] ?? null;
            $comment = is_string($rawComment) ? trim($rawComment) : '';

            $lines[] = [
                'controle_id' => $controleId,
                'resultat' => $result,
                'commentaire' => $comment === '' ? null : $comment,
                'valeur_saisie' => $valueInput,
            ];
        }

        $verificationId = $verificationRepository->createWithLines(
            $caserneId,
            $vehicleId,
            $posteId,
            $utilisateurId,
            $agent,
            $globalComment === '' ? null : $globalComment,
            $lines
        );

        $anomalyCount = 0;
        foreach ($lines as $line) {
            if (($line['resultat'] ?? '') === 'nok') {
                $anomalyCount++;
            }
        }
        if ($anomalyCount > 0) {
            $vehicleName = trim((string) ($vehicle['nom'] ?? 'Engin'));
            $posteName = trim((string) ($poste['nom'] ?? 'Poste'));
            $notificationRepository = new NotificationRepository();
            $notificationRepository->createForCaserneEvent(
                $caserneId,
                'anomaly.created',
                $anomalyCount === 1 ? 'Nouvelle anomalie detectee' : $anomalyCount . ' nouvelles anomalies detectees',
                $vehicleName . ' / ' . $posteName . ' - verification realisee par ' . $agent,
                '/index.php?controller=anomalies&action=index&statut=actives',
                $utilisateurId,
                $agent,
                [
                    'verification_id' => $verificationId,
                    'vehicle' => $vehicleName,
                    'poste' => $posteName,
                    'anomaly_count' => $anomalyCount,
                ]
            );
        }

        $this->redirect('/index.php?controller=verifications&action=saved&id=' . $verificationId);
    }

    public function history(): void
    {
        $verificationRepository = new VerificationRepository();
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();

        $filters = [
            'vehicule_id' => isset($_GET['vehicule_id']) ? (string) $_GET['vehicule_id'] : '',
            'poste_id' => isset($_GET['poste_id']) ? (string) $_GET['poste_id'] : '',
            'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
            'statut_global' => isset($_GET['statut_global']) ? (string) $_GET['statut_global'] : '',
            'with_anomalies' => isset($_GET['with_anomalies']) ? (string) $_GET['with_anomalies'] : '',
        ];

        $history = $verificationRepository->findHistory($filters, $caserneId);
        $vehicles = $vehicleRepository->findAllActive($caserneId);
        $postes = $posteRepository->findAll($caserneId);
        $canDeleteVerifications = $this->isPlatformAdmin();

        require dirname(__DIR__, 2) . '/public/views/history.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=verifications&action=history&error=delete_invalid');
        }

        if (!$this->isPlatformAdmin()) {
            $this->redirect('/index.php?controller=verifications&action=history&error=delete_forbidden');
        }

        $verificationId = isset($_POST['verification_id']) ? (int) $_POST['verification_id'] : 0;
        if ($verificationId <= 0) {
            $this->redirect('/index.php?controller=verifications&action=history&error=delete_invalid');
        }

        $verificationRepository = new VerificationRepository();
        $deleted = $verificationRepository->deleteById($verificationId, $this->resolveManagerCaserneId());

        $this->redirect(
            '/index.php?controller=verifications&action=history&' . ($deleted ? 'success=deleted' : 'error=delete_failed')
        );
    }

    public function monthly(): void
    {
        $verificationRepository = new VerificationRepository();
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();

        $monthInput = isset($_GET['month']) ? trim((string) $_GET['month']) : '';
        $selectedVehicleId = isset($_GET['vehicule_id']) ? (int) $_GET['vehicule_id'] : 0;
        $today = new \DateTimeImmutable('today');

        if (preg_match('/^\d{4}-\d{2}$/', $monthInput) === 1) {
            [$yearString, $monthString] = explode('-', $monthInput, 2);
            $year = (int) $yearString;
            $month = (int) $monthString;
            if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                $year = (int) $today->format('Y');
                $month = (int) $today->format('m');
            }
        } else {
            $year = (int) $today->format('Y');
            $month = (int) $today->format('m');
        }

        $vehicles = $vehicleRepository->findAllActive($caserneId);
        $vehiclesById = [];
        foreach ($vehicles as $vehicle) {
            $vehiclesById[(int) ($vehicle['id'] ?? 0)] = $vehicle;
        }
        if ($selectedVehicleId > 0 && !isset($vehiclesById[$selectedVehicleId])) {
            $selectedVehicleId = 0;
        }

        $coverageVehicles = $selectedVehicleId > 0
            ? [$vehiclesById[$selectedVehicleId]]
            : $vehicles;
        $expectedPostes = [];
        foreach ($coverageVehicles as $vehicle) {
            $vehicleId = (int) ($vehicle['id'] ?? 0);
            if ($vehicleId <= 0) {
                continue;
            }
            foreach ($posteRepository->findByVehicleId($vehicleId, $caserneId) as $poste) {
                $posteId = (int) ($poste['id'] ?? 0);
                if ($posteId <= 0) {
                    continue;
                }
                $expectedPostes[$vehicleId . '|' . $posteId] = [
                    'vehicule_id' => $vehicleId,
                    'vehicule_nom' => (string) ($vehicle['nom'] ?? ''),
                    'poste_id' => $posteId,
                    'poste_nom' => (string) ($poste['nom'] ?? ''),
                ];
            }
        }
        $expectedPostesPerDay = count($expectedPostes);

        $statsRows = $verificationRepository->findMonthlyDailyPosteStats(
            $year,
            $month,
            $caserneId,
            $selectedVehicleId > 0 ? $selectedVehicleId : null
        );

        $daysInMonth = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $dailyCoverage = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dailyCoverage[$date] = [
                'postes_verifies' => [],
                'postes_verifies_count' => 0,
                'postes_attendus' => $expectedPostesPerDay,
                'pourcentage' => 0,
                'complet' => false,
                'eligible' => false,
                'total_verifs' => 0,
                'conformes' => 0,
                'non_conformes' => 0,
            ];
        }

        $totals = [
            'total_verifs' => 0,
            'conformes' => 0,
            'non_conformes' => 0,
            'postes_couverts' => 0,
            'jours_complets' => 0,
        ];

        foreach ($statsRows as $row) {
            $date = (string) ($row['jour'] ?? '');
            if (!isset($dailyCoverage[$date])) {
                continue;
            }

            $total = (int) ($row['total_verifs'] ?? 0);
            $conformes = (int) ($row['conformes'] ?? 0);
            $nonConformes = (int) ($row['non_conformes'] ?? 0);
            $posteKey = (int) ($row['vehicule_id'] ?? 0) . '|' . (int) ($row['poste_id'] ?? 0);

            $dailyCoverage[$date]['total_verifs'] += $total;
            $dailyCoverage[$date]['conformes'] += $conformes;
            $dailyCoverage[$date]['non_conformes'] += $nonConformes;
            if (isset($expectedPostes[$posteKey])) {
                $dailyCoverage[$date]['postes_verifies'][$posteKey] = true;
            }
            $totals['total_verifs'] += $total;
            $totals['conformes'] += $conformes;
            $totals['non_conformes'] += $nonConformes;
        }

        $monthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $currentMonthStart = $today->modify('first day of this month');
        if ($monthStart > $currentMonthStart) {
            $eligibleDays = 0;
        } elseif ($monthStart->format('Y-m') === $today->format('Y-m')) {
            $eligibleDays = (int) $today->format('j');
        } else {
            $eligibleDays = $daysInMonth;
        }

        foreach ($dailyCoverage as $date => &$dayCoverage) {
            $dayNumber = (int) substr($date, 8, 2);
            $verifiedCount = count($dayCoverage['postes_verifies']);
            $dayCoverage['postes_verifies_count'] = $verifiedCount;
            $dayCoverage['eligible'] = $dayNumber <= $eligibleDays;
            $dayCoverage['pourcentage'] = $expectedPostesPerDay > 0
                ? min(100, (int) round(($verifiedCount / $expectedPostesPerDay) * 100))
                : 0;
            $dayCoverage['complet'] = $dayCoverage['eligible']
                && $expectedPostesPerDay > 0
                && $verifiedCount >= $expectedPostesPerDay;
            unset($dayCoverage['postes_verifies']);

            if ($dayCoverage['eligible']) {
                $totals['postes_couverts'] += $verifiedCount;
                if ($dayCoverage['complet']) {
                    $totals['jours_complets']++;
                }
            }
        }
        unset($dayCoverage);

        $expectedPosteChecks = $expectedPostesPerDay * $eligibleDays;
        $conformityRate = $expectedPosteChecks > 0
            ? min(100, (int) round(($totals['postes_couverts'] / $expectedPosteChecks) * 100))
            : 0;
        $monthValue = sprintf('%04d-%02d', $year, $month);
        $monthNames = [
            1 => 'janvier',
            2 => 'fevrier',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'decembre',
        ];
        $monthLabel = ($monthNames[$month] ?? (string) $month) . ' ' . $year;

        require dirname(__DIR__, 2) . '/public/views/verifications_monthly.php';
    }

    public function show(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();
        $caserneId = $this->resolveManagerCaserneId();

        $verification = $verificationRepository->findById($verificationId, $caserneId);
        $lines = $verification === null ? [] : $verificationRepository->findLinesByVerificationId($verificationId, $caserneId);
        if ($verification !== null) {
            $lines = $this->applyZonePaths((int) $verification['vehicule_id'], $lines);
        }

        require dirname(__DIR__, 2) . '/public/views/verification_show.php';
    }

    public function export(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();
        $caserneId = $this->resolveManagerCaserneId();

        $verification = $verificationRepository->findById($verificationId, $caserneId);
        $lines = $verification === null ? [] : $verificationRepository->findLinesByVerificationId($verificationId, $caserneId);
        if ($verification !== null) {
            $lines = $this->applyZonePaths((int) $verification['vehicule_id'], $lines);
        }

        require dirname(__DIR__, 2) . '/public/views/verification_export.php';
    }

    public function saved(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();
        $verification = $verificationRepository->findById($verificationId, $this->resolveActiveCaserneId());

        require dirname(__DIR__, 2) . '/public/views/verification_saved.php';
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function computeResultForNumericControl(array $controle, float $value): string
    {
        $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));

        if ($inputType === 'quantite') {
            $expected = $controle['valeur_attendue'] !== null ? (float) $controle['valeur_attendue'] : null;
            if ($expected === null) {
                return 'ok';
            }

            return $value >= $expected ? 'ok' : 'nok';
        }

        if ($inputType === 'mesure') {
            $min = $controle['seuil_min'] !== null ? (float) $controle['seuil_min'] : null;
            $max = $controle['seuil_max'] !== null ? (float) $controle['seuil_max'] : null;

            if ($min !== null && $value < $min) {
                return 'nok';
            }

            if ($max !== null && $value > $max) {
                return 'nok';
            }

            return 'ok';
        }

        return 'ok';
    }

    private function applyZonePaths(int $vehicleId, array $lines): array
    {
        if ($vehicleId <= 0 || $lines === []) {
            return $lines;
        }

        $zoneRepository = new ZoneRepository();
        $zoneMap = [];
        $zoneSort = [];

        foreach ($zoneRepository->findByVehicleId($vehicleId, $this->resolveActiveCaserneId()) as $index => $zone) {
            $zoneId = (int) $zone['id'];
            $zoneMap[$zoneId] = (string) ($zone['chemin'] ?? $zone['nom']);
            $zoneSort[$zoneId] = (string) ($zone['tri_arborescence'] ?? sprintf('%06d', $index));
        }

        foreach ($lines as &$line) {
            $zoneId = isset($line['zone_id']) ? (int) $line['zone_id'] : 0;
            if ($zoneId > 0 && isset($zoneMap[$zoneId])) {
                $line['zone'] = $zoneMap[$zoneId];
            }
        }
        unset($line);

        usort($lines, static function (array $a, array $b) use ($zoneSort): int {
            $zoneA = (int) ($a['zone_id'] ?? 0);
            $zoneB = (int) ($b['zone_id'] ?? 0);
            $zoneCompare = strcmp($zoneSort[$zoneA] ?? 'zzzzzz', $zoneSort[$zoneB] ?? 'zzzzzz');
            if ($zoneCompare !== 0) {
                return $zoneCompare;
            }

            $orderA = (int) ($a['ordre'] ?? 0);
            $orderB = (int) ($b['ordre'] ?? 0);
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp((string) ($a['libelle'] ?? ''), (string) ($b['libelle'] ?? ''));
        });

        return $lines;
    }

    private function resolveManagerCaserneId(): ?int
    {
        $managerCaserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;
        return $managerCaserneId > 0 ? $managerCaserneId : null;
    }

    private function isPlatformAdmin(): bool
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        if (!is_array($managerUser)) {
            return false;
        }

        return (int) ($managerUser['is_platform_admin'] ?? 0) === 1
            || strtolower((string) ($managerUser['global_role'] ?? $managerUser['role'] ?? '')) === 'admin';
    }

    private function resolveActiveCaserneId(): ?int
    {
        $fieldCaserneId = isset($_SESSION['field_caserne_id']) ? (int) $_SESSION['field_caserne_id'] : 0;
        if ($fieldCaserneId > 0) {
            return $fieldCaserneId;
        }

        return $this->resolveManagerCaserneId();
    }

    private function getScopedSettingValue(string $settingKey, string $envKey, ?int $caserneId, string $default): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable() && $caserneId !== null && $caserneId > 0) {
            $scoped = $repository->get($settingKey . '_caserne_' . $caserneId);
            if ($scoped !== null && trim($scoped) !== '') {
                return trim($scoped);
            }
        }

        if ($repository->isAvailable()) {
            $global = $repository->get($settingKey);
            if ($global !== null && trim($global) !== '') {
                return trim($global);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    }
}
