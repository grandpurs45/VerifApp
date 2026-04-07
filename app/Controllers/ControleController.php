<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
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
        $fromVehicleQr = isset($_GET['from_vehicle_qr']) && (string) $_GET['from_vehicle_qr'] === '1';

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        $poste = null;
        $controles = [];
        $terrainMobileDensity = $this->getScopedSettingValue('terrain_mobile_density', 'TERRAIN_MOBILE_DENSITY', $caserneId, 'normal');
        if (!in_array($terrainMobileDensity, ['compact', 'normal'], true)) {
            $terrainMobileDensity = 'normal';
        }
        $terrainStickyProgressEnabled = $this->getScopedSettingValue('terrain_sticky_progress_enabled', 'TERRAIN_STICKY_PROGRESS_ENABLED', $caserneId, '1') !== '0';
        $terrainDraftEnabled = $this->getScopedSettingValue('terrain_draft_enabled', 'TERRAIN_DRAFT_ENABLED', $caserneId, '1') !== '0';
        $terrainDraftTtlHours = (int) $this->getScopedSettingValue('terrain_draft_ttl_hours', 'TERRAIN_DRAFT_TTL_HOURS', $caserneId, '12');
        if ($terrainDraftTtlHours < 1 || $terrainDraftTtlHours > 48) {
            $terrainDraftTtlHours = 12;
        }
        $terrainScrollMissingEnabled = $this->getScopedSettingValue('terrain_scroll_missing_enabled', 'TERRAIN_SCROLL_MISSING_ENABLED', $caserneId, '1') !== '0';
        $verificationEveningHour = (int) $this->getScopedSettingValue('verification_evening_hour', 'VERIFICATION_EVENING_HOUR', $caserneId, '18');
        if ($verificationEveningHour < 0 || $verificationEveningHour > 23) {
            $verificationEveningHour = 18;
        }

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

    private function getScopedSettingValue(string $settingKey, string $envKey, ?int $caserneId, string $default): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable() && $caserneId !== null && $caserneId > 0) {
            $scoped = $repository->get($settingKey . '_caserne_' . $caserneId);
            if ($scoped !== null && trim($scoped) !== '') {
                return trim($scoped);
            }
        }

        if ($repository->isAvailable()) {
            $global = $repository->get($settingKey);
            if ($global !== null && trim($global) !== '') {
                return trim($global);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    }
}
