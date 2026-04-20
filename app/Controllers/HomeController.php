<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VehicleRepository;

final class HomeController
{
    public function index(): void
    {
        require dirname(__DIR__, 2) . '/public/views/landing.php';
    }

    public function features(): void
    {
        require dirname(__DIR__, 2) . '/public/views/landing_features.php';
    }

    public function terrain(): void
    {
        $vehicleRepository = new VehicleRepository();
        $caserneId = $this->resolveActiveCaserneId();
        $vehicles = $vehicleRepository->findAllActive($caserneId);

        require dirname(__DIR__, 2) . '/public/views/home.php';
    }

    private function resolveActiveCaserneId(): ?int
    {
        $fieldCaserneId = isset($_SESSION['field_caserne_id']) ? (int) $_SESSION['field_caserne_id'] : 0;
        if ($fieldCaserneId > 0) {
            return $fieldCaserneId;
        }

        $managerCaserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;
        if ($managerCaserneId > 0) {
            return $managerCaserneId;
        }

        return null;
    }
}
