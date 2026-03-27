<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
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
        $assignmentStats = $anomalyRepository->getAssignmentStats(
            is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : null
        );
        $appUrl = rtrim((string) (Env::get('APP_URL', '') ?? ''), '/');
        $fieldToken = trim((string) (Env::get('FIELD_QR_TOKEN', '') ?? ''));
        $pharmacyToken = trim((string) (Env::get('PHARMACY_QR_TOKEN', '') ?? ''));
        $fieldGuestPath = '/index.php?controller=field&action=access' . ($fieldToken !== '' ? '&token=' . rawurlencode($fieldToken) : '');
        $pharmacyGuestPath = '/index.php?controller=pharmacy&action=access' . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '');
        $fieldGuestUrl = $appUrl !== '' ? $appUrl . $fieldGuestPath : $fieldGuestPath;
        $pharmacyGuestUrl = $appUrl !== '' ? $appUrl . $pharmacyGuestPath : $pharmacyGuestPath;

        require dirname(__DIR__, 2) . '/public/views/manager_dashboard.php';
    }

    public function forbidden(): void
    {
        require dirname(__DIR__, 2) . '/public/views/manager_forbidden.php';
    }
}
