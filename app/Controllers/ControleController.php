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
        $caserneId = $this->resolveActiveCaserneId();

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        $poste = null;
        $controles = [];

        if ($vehicle !== null) {
            $poste = $posteRepository->findByIdForVehicle($posteId, $vehicleId, $caserneId);

            if ($poste !== null) {
                $controles = $controleRepository->findByVehicleAndPosteId($vehicleId, $posteId, $caserneId);

                $zoneRepository = new ZoneRepository();
                $zoneMap = [];
                foreach ($zoneRepository->findByVehicleId($vehicleId, $caserneId) as $zone) {
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
