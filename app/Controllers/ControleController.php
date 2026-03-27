<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\ZoneRepository;

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

                $zoneRepository = new ZoneRepository();
                $zoneMap = [];
                foreach ($zoneRepository->findByVehicleId($vehicleId) as $zone) {
                    $zoneMap[(int) $zone['id']] = (string) ($zone['chemin'] ?? $zone['nom']);
                }

                foreach ($controles as &$controle) {
                    $zoneId = isset($controle['zone_id']) ? (int) $controle['zone_id'] : 0;
                    if ($zoneId > 0 && isset($zoneMap[$zoneId])) {
                        $controle['zone'] = $zoneMap[$zoneId];
                    }
                }
                unset($controle);
            }
        }

        require dirname(__DIR__, 2) . '/public/views/controles.php';
    }
}
