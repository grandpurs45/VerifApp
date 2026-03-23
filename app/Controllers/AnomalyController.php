<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AnomalyRepository;
use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;

final class AnomalyController
{
    public function index(): void
    {
        $anomalyRepository = new AnomalyRepository();
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();

        $filters = [
            'statut' => isset($_GET['statut']) ? (string) $_GET['statut'] : '',
            'priorite' => isset($_GET['priorite']) ? (string) $_GET['priorite'] : '',
            'vehicule_id' => isset($_GET['vehicule_id']) ? (string) $_GET['vehicule_id'] : '',
            'poste_id' => isset($_GET['poste_id']) ? (string) $_GET['poste_id'] : '',
            'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
        ];

        $anomalies = $anomalyRepository->findAll($filters);
        $anomaliesAvailable = $anomalyRepository->isAvailable();
        $vehicles = $vehicleRepository->findAllActive();
        $postes = $posteRepository->findAll();
        $returnQuery = http_build_query(array_merge(
            ['controller' => 'anomalies', 'action' => 'index'],
            array_filter($filters, static fn ($value): bool => $value !== '')
        ));

        require dirname(__DIR__, 2) . '/public/views/anomalies.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=anomalies&action=index');
        }

        $anomalyId = isset($_POST['anomaly_id']) ? (int) $_POST['anomaly_id'] : 0;
        $status = strtolower(trim((string) ($_POST['statut'] ?? '')));
        $priority = strtolower(trim((string) ($_POST['priorite'] ?? '')));
        $comment = trim((string) ($_POST['commentaire'] ?? ''));
        $returnQuery = trim((string) ($_POST['return_query'] ?? ''));

        $allowedStatuses = ['ouverte', 'en_cours', 'resolue', 'cloturee'];
        $allowedPriorities = ['basse', 'moyenne', 'haute', 'critique'];

        if (
            $anomalyId <= 0 ||
            !in_array($status, $allowedStatuses, true) ||
            !in_array($priority, $allowedPriorities, true)
        ) {
            $this->redirect('/index.php?controller=anomalies&action=index&error=invalid');
        }

        $anomalyRepository = new AnomalyRepository();
        $anomalyRepository->updateStatus(
            $anomalyId,
            $status,
            $priority,
            $comment === '' ? null : $comment
        );

        $location = '/index.php?controller=anomalies&action=index&updated=1';
        if ($returnQuery !== '') {
            $location = '/index.php?' . $returnQuery . '&updated=1';
        }

        $this->redirect($location);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
