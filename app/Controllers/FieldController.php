<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\VehicleRepository;

final class FieldController
{
    public function access(): void
    {
        $caserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;
        $vehicleId = isset($_GET['vehicle_id']) ? (int) $_GET['vehicle_id'] : 0;
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';
        $vehicleToken = $this->getVehicleToken($caserneId, $vehicleId);
        $configuredToken = $this->getFieldToken($caserneId > 0 ? $caserneId : null);

        if ($vehicleToken !== '') {
            if ($caserneId <= 0) {
                $this->redirect('/index.php?controller=field&action=denied');
            }

            if (hash_equals($vehicleToken, $providedToken)) {
                $_SESSION['field_access'] = true;
                $this->storeFieldCaserneContext($caserneId);
                $this->redirectFieldEntry($caserneId, $vehicleId);
            }

            $this->redirect('/index.php?controller=field&action=denied');
        }

        if ($configuredToken === '') {
            $_SESSION['field_access'] = true;
            $this->storeFieldCaserneContext($caserneId);
            $this->redirectFieldEntry($caserneId, $vehicleId);
        }

        if ($caserneId <= 0) {
            $this->redirect('/index.php?controller=field&action=denied');
        }

        if (hash_equals($configuredToken, $providedToken)) {
            $_SESSION['field_access'] = true;
            $this->storeFieldCaserneContext($caserneId);
            $this->redirectFieldEntry($caserneId, $vehicleId);
        }

        $this->redirect('/index.php?controller=field&action=denied');
    }

    public function denied(): void
    {
        require dirname(__DIR__, 2) . '/public/views/field_denied.php';
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function getFieldToken(?int $caserneId): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            if ($caserneId !== null && $caserneId > 0) {
                $scoped = $repository->get('field_qr_token_caserne_' . $caserneId);
                if ($scoped !== null && trim($scoped) !== '') {
                    return trim($scoped);
                }
            }

            $global = $repository->get('field_qr_token');
            if ($global !== null && trim($global) !== '') {
                return trim($global);
            }
        }

        return trim((string) (Env::get('FIELD_QR_TOKEN', '') ?? ''));
    }

    private function storeFieldCaserneContext(int $caserneId): void
    {
        if ($caserneId > 0) {
            $caserneRepository = new CaserneRepository();
            $caserne = $caserneRepository->findById($caserneId);
            if ($caserne !== null && (int) ($caserne['actif'] ?? 0) === 1) {
                $_SESSION['field_caserne_id'] = (int) $caserne['id'];
                $_SESSION['field_caserne_nom'] = (string) $caserne['nom'];
                return;
            }
        }

        unset($_SESSION['field_caserne_id'], $_SESSION['field_caserne_nom']);
    }

    private function getVehicleToken(int $caserneId, int $vehicleId): string
    {
        if ($caserneId <= 0 || $vehicleId <= 0) {
            return '';
        }

        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            return '';
        }

        $value = $repository->get('field_vehicle_qr_token_caserne_' . $caserneId . '_vehicle_' . $vehicleId);
        if ($value === null || trim($value) === '') {
            return '';
        }

        return trim($value);
    }

    private function redirectFieldEntry(int $caserneId, int $vehicleId): void
    {
        if ($vehicleId > 0 && $caserneId > 0) {
            $vehicleRepository = new VehicleRepository();
            $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
            if ($vehicle !== null && (int) ($vehicle['actif'] ?? 0) === 1) {
                $this->redirect('/index.php?controller=postes&action=list&vehicle_id=' . $vehicleId . '&from_vehicle_qr=1');
            }
        }

        $this->redirect('/index.php?controller=home&action=terrain');
    }
}
