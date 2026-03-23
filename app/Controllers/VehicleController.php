<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VehicleRepository;

final class VehicleController
{
    public function show(int $vehicleId): void
    {
        $vehicleRepository = new VehicleRepository();
        $vehicle = $vehicleRepository->findById($vehicleId);

        require dirname(__DIR__, 2) . '/public/views/vehicle.php';
    }
}