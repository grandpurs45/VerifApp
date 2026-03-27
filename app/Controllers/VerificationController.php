<?php

declare(strict_types=1);

namespace App\Controllers;

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

        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();
        $verificationRepository = new VerificationRepository();

        $vehicle = $vehicleRepository->findById($vehicleId);
        $poste = $posteRepository->findByIdForVehicle($posteId, $vehicleId);

        if ($vehicle === null || $poste === null) {
            $this->redirect('/index.php?controller=home&action=index');
        }

        if ($agent === '') {
            $this->redirect(
                '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=agent_required'
            );
        }

        $controles = $controleRepository->findByVehicleAndPosteId($vehicleId, $posteId);
        $resultats = is_array($_POST['resultats'] ?? null) ? $_POST['resultats'] : [];
        $values = is_array($_POST['valeurs'] ?? null) ? $_POST['valeurs'] : [];
        $commentaires = is_array($_POST['commentaires'] ?? null) ? $_POST['commentaires'] : [];
        $allowedStatuses = ['ok', 'nok', 'na'];
        $lines = [];

        foreach ($controles as $controle) {
            $controleId = (int) $controle['id'];
            $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));
            if (!in_array($inputType, ['statut', 'quantite', 'mesure'], true)) {
                $inputType = 'statut';
            }

            $result = '';
            $valueInput = null;

            if ($inputType === 'statut') {
                $rawResult = $resultats[(string) $controleId] ?? null;
                $result = is_string($rawResult) ? strtolower(trim($rawResult)) : '';

                if (!in_array($result, $allowedStatuses, true)) {
                    $this->redirect(
                        '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=incomplete'
                    );
                }
            } else {
                $rawValue = $values[(string) $controleId] ?? null;
                $valueString = is_string($rawValue) ? trim($rawValue) : '';

                if ($valueString === '' || !is_numeric($valueString)) {
                    $this->redirect(
                        '/index.php?controller=controles&action=list&vehicle_id=' . $vehicleId . '&poste_id=' . $posteId . '&error=incomplete'
                    );
                }

                $valueInput = (float) $valueString;
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

        $filters = [
            'vehicule_id' => isset($_GET['vehicule_id']) ? (string) $_GET['vehicule_id'] : '',
            'poste_id' => isset($_GET['poste_id']) ? (string) $_GET['poste_id'] : '',
            'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
            'statut_global' => isset($_GET['statut_global']) ? (string) $_GET['statut_global'] : '',
            'with_anomalies' => isset($_GET['with_anomalies']) ? (string) $_GET['with_anomalies'] : '',
        ];

        $history = $verificationRepository->findHistory($filters);
        $vehicles = $vehicleRepository->findAllActive();
        $postes = $posteRepository->findAll();

        require dirname(__DIR__, 2) . '/public/views/history.php';
    }

    public function show(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();

        $verification = $verificationRepository->findById($verificationId);
        $lines = $verification === null ? [] : $verificationRepository->findLinesByVerificationId($verificationId);
        if ($verification !== null) {
            $lines = $this->applyZonePaths((int) $verification['vehicule_id'], $lines);
        }

        require dirname(__DIR__, 2) . '/public/views/verification_show.php';
    }

    public function export(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();

        $verification = $verificationRepository->findById($verificationId);
        $lines = $verification === null ? [] : $verificationRepository->findLinesByVerificationId($verificationId);
        if ($verification !== null) {
            $lines = $this->applyZonePaths((int) $verification['vehicule_id'], $lines);
        }

        require dirname(__DIR__, 2) . '/public/views/verification_export.php';
    }

    public function saved(int $verificationId): void
    {
        $verificationRepository = new VerificationRepository();
        $verification = $verificationRepository->findById($verificationId);

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

        foreach ($zoneRepository->findByVehicleId($vehicleId) as $zone) {
            $zoneMap[(int) $zone['id']] = (string) ($zone['chemin'] ?? $zone['nom']);
        }

        foreach ($lines as &$line) {
            $zoneId = isset($line['zone_id']) ? (int) $line['zone_id'] : 0;
            if ($zoneId > 0 && isset($zoneMap[$zoneId])) {
                $line['zone'] = $zoneMap[$zoneId];
            }
        }
        unset($line);

        return $lines;
    }
}
