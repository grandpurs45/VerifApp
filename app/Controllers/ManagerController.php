<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AnomalyRepository;
use App\Repositories\VerificationRepository;

final class ManagerController
{
    public function dashboard(): void
    {
        $verificationRepository = new VerificationRepository();
        $anomalyRepository = new AnomalyRepository();

        $stats = $verificationRepository->getDashboardStats();
        $anomalyStats = $anomalyRepository->getStatusStats();
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_dashboard.php';
    }
}
