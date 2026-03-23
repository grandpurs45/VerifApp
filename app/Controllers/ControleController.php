<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;

final class ControleController
{
    public function list(int $vehicleId, int $posteId): void
    {
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();

        $vehicle = $vehicleRepository->findById($vehicleId);
        $poste = null;
        $controles = [];

        if ($vehicle !== null) {
            $poste = $posteRepository->findByIdForVehicle($posteId, $vehicleId);

            if ($poste !== null) {
                $controles = $controleRepository->findByVehicleAndPosteId($vehicleId, $posteId);
            }
        }

        require dirname(__DIR__, 2) . '/public/views/controles.php';
    }
}
