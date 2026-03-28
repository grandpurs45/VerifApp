<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AnomalyRepository;
use App\Repositories\PosteRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;

final class AnomalyController
{
    public function index(): void
    {
        $anomalyRepository = new AnomalyRepository();
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $userRepository = new UserRepository();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerUserId = is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : 0;

        $filters = [
            'statut' => array_key_exists('statut', $_GET) ? (string) $_GET['statut'] : 'actives',
            'priorite' => isset($_GET['priorite']) ? (string) $_GET['priorite'] : '',
            'vehicule_id' => isset($_GET['vehicule_id']) ? (string) $_GET['vehicule_id'] : '',
            'poste_id' => isset($_GET['poste_id']) ? (string) $_GET['poste_id'] : '',
            'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
            'assigne_a' => isset($_GET['assigne_a']) ? (string) $_GET['assigne_a'] : '',
        ];

        $anomalies = $anomalyRepository->findAll($filters);
        $anomaliesAvailable = $anomalyRepository->isAvailable();
        $vehicles = $vehicleRepository->findAllActive();
        $postes = $posteRepository->findAll();
        $assignableUsers = $userRepository->findAllActiveByRoles(['admin', 'responsable_materiel']);
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
        $assigneeRaw = (string) ($_POST['assigne_a'] ?? '');
        $assignToMe = isset($_POST['assign_to_me']) && (string) $_POST['assign_to_me'] === '1';
        $managerUser = $_SESSION['manager_user'] ?? null;
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

        if ($status === 'cloturee') {
            $status = 'resolue';
        }

        $assigneeId = null;
        if ($assignToMe && is_array($managerUser) && isset($managerUser['id'])) {
            $assigneeId = (int) $managerUser['id'];
        } elseif ($assigneeRaw !== '' && ctype_digit($assigneeRaw)) {
            $assigneeId = (int) $assigneeRaw;
        }

        $anomalyRepository = new AnomalyRepository();
        $anomalyRepository->updateStatus(
            $anomalyId,
            $status,
            $priority,
            $comment === '' ? null : $comment,
            $assigneeId
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
