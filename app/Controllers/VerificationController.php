<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\SliderMeasure;
use App\Core\VerificationCoverage;
use App\Repositories\AppSettingRepository;
use App\Repositories\ControleRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\VerificationRepository;
use App\Repositories\ZoneRepository;
use App\Repositories\QrAccessLogRepository;

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
        $guardDurationHours = isset($_POST['garde_duree_heures']) ? (int) $_POST['garde_duree_heures'] : 12;
        if (!in_array($guardDurationHours, [12, 24], true)) {
            $guardDurationHours = 12;
        }
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

        if ($vehicle === null || (int) ($vehicle['actif'] ?? 0) !== 1 || (int) ($vehicle['verification_active'] ?? 0) !== 1 || $poste === null) {
            $this->redirect('/index.php?controller=home&action=index');
        }

        if ($agent === '') {
            $this->redirect(
                '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=agent_required' . $contextSuffix
            );
        }

        $submissionKey = is_string($_POST['_submission_key'] ?? null) ? $_POST['_submission_key'] : '';
        if (preg_match('/^[a-f0-9]{64}$/D', $submissionKey) !== 1) {
            $this->redirect('/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=reload_required' . $contextSuffix);
        }

        $controles = $controleRepository->findByVehicleAndPosteId($vehicleId, $posteId, $caserneId);
        $resultats = is_array($_POST['resultats'] ?? null) ? $_POST['resultats'] : [];
        $values = is_array($_POST['valeurs'] ?? null) ? $_POST['valeurs'] : [];
        $commentaires = is_array($_POST['commentaires'] ?? null) ? $_POST['commentaires'] : [];
        $allowedStatuses = ['ok', 'nok'];
        $lines = [];
        $anomalyDetails = [];

        foreach ($controles as $controle) {
            $controleId = (int) $controle['id'];
            $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));
            if (!in_array($inputType, ['statut', 'quantite', 'mesure', 'curseur'], true)) {
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

                if ($inputType === 'curseur') {
                    $slider = SliderMeasure::configuration(
                        $controle['curseur_min'] ?? '', $controle['curseur_max'] ?? '', $controle['curseur_pas'] ?? ''
                    );
                    if ($slider === null || !SliderMeasure::accepts($slider, (int) $valueString)) {
                        $this->redirect('/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId
                            . '&poste_id=' . $posteId . '&error=incomplete' . $contextSuffix);
                    }
                }

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
            if ($result === 'nok') {
                $unit = trim((string) ($controle['unite'] ?? ''));
                $anomalyDetails[] = [
                    'controle_id' => $controleId,
                    'label' => trim((string) ($controle['libelle'] ?? 'Controle non conforme')),
                    'zone' => trim((string) ($controle['zone'] ?? '')),
                    'comment' => $comment,
                    'value' => $valueInput !== null
                        ? rtrim(rtrim(number_format($valueInput, 2, '.', ''), '0'), '.') . ($unit !== '' ? ' ' . $unit : '')
                        : '',
                ];
            }
        }

        try {
            $verificationId = $verificationRepository->createWithLines(
                $caserneId,
                $vehicleId,
                $posteId,
                $utilisateurId,
                $agent,
                $guardDurationHours,
                $globalComment === '' ? null : $globalComment,
                $lines,
                $submissionKey
            );
        } catch (\DomainException) {
            $this->redirect('/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=submission_conflict' . $contextSuffix);
        }
        if ($verificationRepository->wasSubmissionReplayed()) {
            $this->redirect('/index.php?controller=verifications&action=saved&id=' . $verificationId);
        }
        (new QrAccessLogRepository())->attachIdentity('verifications', $agent, $utilisateurId);

        $anomalyReports = $verificationRepository->getLastAnomalyReports();
        if ($anomalyDetails !== []) {
            $vehicleName = trim((string) ($vehicle['nom'] ?? 'Engin'));
            $posteName = trim((string) ($poste['nom'] ?? 'Poste'));
            $notificationRepository = new NotificationRepository();
            $detailsByControlId = [];
            foreach ($anomalyDetails as $anomalyDetail) {
                $detailsByControlId[(int) ($anomalyDetail['controle_id'] ?? 0)] = $anomalyDetail;
            }

            $newAnomalyDetails = [];
            if ($anomalyReports === []) {
                $newAnomalyDetails = $anomalyDetails;
            } else {
                foreach ($anomalyReports as $report) {
                    if (empty($report['is_new'])) {
                        continue;
                    }
                    $controlId = (int) ($report['controle_id'] ?? 0);
                    if (isset($detailsByControlId[$controlId])) {
                        $newAnomalyDetails[] = $detailsByControlId[$controlId];
                    }
                }
            }

            if ($newAnomalyDetails !== []) {
                $newAnomalyCount = count($newAnomalyDetails);
                $firstAnomalyLabel = trim((string) ($newAnomalyDetails[0]['label'] ?? 'Anomalie'));
                $notificationTitle = $newAnomalyCount === 1
                    ? 'Anomalie - ' . $vehicleName . ' - ' . $firstAnomalyLabel
                    : $newAnomalyCount . ' anomalies - ' . $vehicleName;
                $notificationRepository->createForCaserneEvent(
                    $caserneId,
                    'anomaly.created',
                    $notificationTitle,
                    'Nouvelle non-conformite detectee pendant une verification.',
                    '/index.php?controller=anomalies&action=index&statut=actives',
                    $utilisateurId,
                    $agent,
                    [
                        'verification_id' => $verificationId,
                        'vehicle_id' => $vehicleId,
                        'vehicle' => $vehicleName,
                        'poste' => $posteName,
                        'notifier' => $agent,
                        'anomaly_count' => $newAnomalyCount,
                        'anomalies' => $newAnomalyDetails,
                        'global_comment' => $globalComment,
                    ]
                );
            }

            $reminderInterval = (int) $this->getScopedSettingValue(
                'anomaly_reminder_occurrence_interval',
                'ANOMALY_REMINDER_OCCURRENCE_INTERVAL',
                $caserneId,
                '3'
            );
            $reminderInterval = max(2, min(100, $reminderInterval));
            foreach ($anomalyReports as $report) {
                $occurrenceCount = (int) ($report['occurrence_count'] ?? 0);
                if (!$this->shouldSendAnomalyReminder($report, $reminderInterval)) {
                    continue;
                }
                $controlId = (int) ($report['controle_id'] ?? 0);
                $detail = $detailsByControlId[$controlId] ?? null;
                if (!is_array($detail)) {
                    continue;
                }
                $anomalyId = (int) ($report['anomaly_id'] ?? 0);
                $firstReportTimestamp = strtotime((string) ($report['first_report_at'] ?? ''));
                $firstReportDate = $firstReportTimestamp !== false ? date('d/m/Y', $firstReportTimestamp) : 'date inconnue';
                $anomalyLabel = trim((string) ($detail['label'] ?? 'anomalie'));
                $reminderMessage = 'L anomalie numero ' . $anomalyId
                    . ' concernant ' . $anomalyLabel . ' du ' . $vehicleName
                    . ' a ete remontee ' . $occurrenceCount . ' fois depuis le ' . $firstReportDate . '.';
                $notificationRepository->createForCaserneEvent(
                    $caserneId,
                    'anomaly.created',
                    'Rappel anomalie #' . $anomalyId . ' - ' . $vehicleName,
                    $reminderMessage,
                    '/index.php?controller=anomalies&action=index&statut=actives',
                    $utilisateurId,
                    $agent,
                    [
                        'notification_kind' => 'reminder',
                        'anomaly_id' => $anomalyId,
                        'occurrence_count' => $occurrenceCount,
                        'first_report_date' => $firstReportDate,
                        'verification_id' => $verificationId,
                        'vehicle_id' => $vehicleId,
                        'vehicle' => $vehicleName,
                        'poste' => $posteName,
                        'notifier' => $agent,
                        'anomaly_count' => 1,
                        'anomalies' => [$detail],
                        'global_comment' => $globalComment,
                    ]
                );
            }
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

        $vehicles = $vehicleRepository->findAllVerificationEnabled($caserneId);
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
        $expectedPostes = $this->expectedPostesForVehicles($coverageVehicles, $caserneId);
        $expectedPostesPerDay = array_sum(array_map(static fn(array $poste): int => ($poste['verification_frequency'] ?? 'daily') === 'twice_daily' ? 2 : 1, $expectedPostes));
        $verificationEveningHour = (int) $this->getScopedSettingValue(
            'verification_evening_hour',
            'VERIFICATION_EVENING_HOUR',
            $caserneId,
            '18'
        );
        if ($verificationEveningHour < 0 || $verificationEveningHour > 23) {
            $verificationEveningHour = 18;
        }

        $statsRows = $verificationRepository->findMonthlyDailyPosteStats(
            $year,
            $month,
            $caserneId,
            $selectedVehicleId > 0 ? $selectedVehicleId : null,
            $verificationEveningHour
        );

        $coverage = VerificationCoverage::month($year, $month, $expectedPostes, $statsRows, $today);
        $dailyCoverage = $coverage['dailyCoverage'];
        $totals = $coverage['totals'];
        $daysInMonth = $coverage['daysInMonth'];
        $eligibleDays = $coverage['eligibleDays'];
        $conformityRate = $coverage['rate'];
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

    public function yearly(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) $this->redirect('/index.php?controller=manager&action=dashboard');
        $today = new \DateTimeImmutable('today');
        $selectedYear = filter_var($_GET['year'] ?? '', FILTER_VALIDATE_INT);
        if ($selectedYear === false || $selectedYear < 2000 || $selectedYear > 2100) $selectedYear = (int)$today->format('Y');
        $selectedVehicleId = max(0,(int)($_GET['vehicule_id'] ?? 0));
        $vehicles = (new VehicleRepository())->findAllVerificationEnabled($caserneId);
        $vehiclesById = [];
        foreach ($vehicles as $vehicle) $vehiclesById[(int)$vehicle['id']] = $vehicle;
        if ($selectedVehicleId > 0 && !isset($vehiclesById[$selectedVehicleId])) $selectedVehicleId = 0;
        $coverageVehicles = $selectedVehicleId > 0 ? [$vehiclesById[$selectedVehicleId]] : $vehicles;
        $expectedPostes = $this->expectedPostesForVehicles($coverageVehicles,$caserneId);
        $repository = new VerificationRepository();
        $availableYears = $repository->findAvailableCoverageYears($caserneId,$selectedVehicleId ?: null);
        $comparisonYear = filter_var($_GET['compare_year'] ?? '', FILTER_VALIDATE_INT);
        if ($comparisonYear === false || $comparisonYear === $selectedYear || !in_array($comparisonYear,$availableYears,true)) $comparisonYear = 0;
        $yearOptions = array_values(array_unique(array_merge($availableYears,[$selectedYear,(int)$today->format('Y')])));
        rsort($yearOptions);
        $eveningHour = (int)$this->getScopedSettingValue('verification_evening_hour','VERIFICATION_EVENING_HOUR',$caserneId,'18');
        if ($eveningHour < 0 || $eveningHour > 23) $eveningHour = 18;
        $yearSummaries = [];
        foreach (array_filter([$selectedYear,$comparisonYear]) as $year) {
            $rows = $repository->findAnnualDailyPosteStats($year,$caserneId,$selectedVehicleId ?: null,$eveningHour);
            $yearSummaries[$year] = VerificationCoverage::year($year,$expectedPostes,$rows,$today);
        }
        $primarySummary = $yearSummaries[$selectedYear];
        $monthLabels = ['Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aout','Sep','Oct','Nov','Dec'];
        $monthNames = ['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Decembre'];
        $chartDatasets = [];
        foreach ($yearSummaries as $year => $summary) {
            $links = [];
            foreach ($summary['months'] as $month => $data) {
                $links[] = '/index.php?' . http_build_query(['controller'=>'verifications','action'=>'monthly','month'=>sprintf('%04d-%02d',$year,$month),'vehicule_id'=>$selectedVehicleId]);
            }
            $chartDatasets[] = ['label'=>(string)$year,'data'=>array_column($summary['months'],'rate'),'months'=>array_values($summary['months']),'links'=>$links];
        }
        $chartData = ['labels'=>$monthLabels,'datasets'=>$chartDatasets];
        $monthlyUrl = '/index.php?' . http_build_query(['controller'=>'verifications','action'=>'monthly','month'=>sprintf('%04d-%02d',$selectedYear,$selectedYear === (int)$today->format('Y') ? (int)$today->format('m') : 1),'vehicule_id'=>$selectedVehicleId]);
        require dirname(__DIR__,2) . '/public/views/verifications_yearly.php';
    }

    private function expectedPostesForVehicles(array $coverageVehicles, ?int $caserneId): array
    {
        return $caserneId === null ? [] : (new \App\Core\VerificationCoverageProvider())->expectedPostesForVehicles($coverageVehicles,$caserneId);
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

        if (in_array($inputType, ['mesure', 'curseur'], true)) {
            $min = $controle['seuil_min'] !== null ? (float) $controle['seuil_min'] : null;
            $max = $controle['seuil_max'] !== null ? (float) $controle['seuil_max'] : null;

            if ($min !== null && ($value < $min || (!empty($controle['seuil_min_exclusif']) && $value === $min))) {
                return 'nok';
            }

            if ($max !== null && $value > $max) {
                return 'nok';
            }

            return 'ok';
        }

        return 'ok';
    }

    private function shouldSendAnomalyReminder(array $report, int $interval): bool
    {
        $interval = max(2, min(100, $interval));
        $occurrenceCount = (int) ($report['occurrence_count'] ?? 0);

        return empty($report['is_new'])
            && $occurrenceCount >= $interval
            && $occurrenceCount % $interval === 0;
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
