<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VehicleRepository;

final class VehicleController
{
    public function show(int $vehicleId): void
    {
        $vehicleRepository = new VehicleRepository();
        $caserneId = $this->resolveActiveCaserneId();
        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);

        require dirname(__DIR__, 2) . '/public/views/vehicle.php';
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
