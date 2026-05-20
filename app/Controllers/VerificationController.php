<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\ControleRepository;
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
        $eveningStartHour = (int) $this->getScopedSettingValue('verification_evening_hour', 'VERIFICATION_EVENING_HOUR', $caserneId, '18');
        if ($eveningStartHour < 0 || $eveningStartHour > 23) {
            $eveningStartHour = 18;
        }

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
        $statsRows = $verificationRepository->findMonthlyDaySlotStats(
            $year,
            $month,
            $eveningStartHour,
            $caserneId,
            $selectedVehicleId > 0 ? $selectedVehicleId : null
        );

        $daysInMonth = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $slotsByDay = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $slotsByDay[$date] = [
                'matin' => ['total' => 0, 'conformes' => 0, 'non_conformes' => 0],
                'soir' => ['total' => 0, 'conformes' => 0, 'non_conformes' => 0],
            ];
        }

        $totals = [
            'total_verifs' => 0,
            'conformes' => 0,
            'non_conformes' => 0,
            'slots_couverts' => 0,
            'jours_couverts' => 0,
        ];

        foreach ($statsRows as $row) {
            $date = (string) ($row['jour'] ?? '');
            $slot = (string) ($row['creneau'] ?? '');
            if (!isset($slotsByDay[$date], $slotsByDay[$date][$slot])) {
                continue;
            }

            $total = (int) ($row['total_verifs'] ?? 0);
            $conformes = (int) ($row['conformes'] ?? 0);
            $nonConformes = (int) ($row['non_conformes'] ?? 0);

            $slotsByDay[$date][$slot]['total'] = $total;
            $slotsByDay[$date][$slot]['conformes'] = $conformes;
            $slotsByDay[$date][$slot]['non_conformes'] = $nonConformes;

            $totals['total_verifs'] += $total;
            $totals['conformes'] += $conformes;
            $totals['non_conformes'] += $nonConformes;
            if ($total > 0) {
                $totals['slots_couverts']++;
            }
        }

        foreach ($slotsByDay as $slots) {
            if (($slots['matin']['total'] ?? 0) > 0 && ($slots['soir']['total'] ?? 0) > 0) {
                $totals['jours_couverts']++;
            }
        }

        $totalSlots = $daysInMonth * 2;
        $coverageRate = $totalSlots > 0 ? (int) round(($totals['slots_couverts'] / $totalSlots) * 100) : 0;
        $conformityRate = $totals['total_verifs'] > 0 ? (int) round(($totals['conformes'] / $totals['total_verifs']) * 100) : 0;
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
