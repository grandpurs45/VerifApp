<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;

final class PosteController
{
    public function list(int $vehicleId): void
    {
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();

        $vehicle = $vehicleRepository->findById($vehicleId);
        $postes = [];

        if ($vehicle !== null) {
            $postes = $posteRepository->findByVehicleId($vehicleId);
        }

        require dirname(__DIR__, 2) . '/public/views/postes.php';
    }
}
