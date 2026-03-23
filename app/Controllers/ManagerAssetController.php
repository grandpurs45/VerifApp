<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;
use App\Repositories\TypeVehiculeRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\ZoneRepository;
use Throwable;

final class ManagerAssetController
{
    public function index(): void
    {
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();
        $zoneRepository = new ZoneRepository();

        $vehicles = $vehicleRepository->findAllDetailed();
        $postes = $posteRepository->findAllDetailed();
        $controles = $controleRepository->findAllDetailed();
        $typesVehicules = $typeVehiculeRepository->findAll();
        $zones = $zoneRepository->findAllDetailed();
        $zonesAvailable = $zoneRepository->isAvailable();
        $hierarchyAvailable = $controleRepository->hasHierarchicalSchema();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_assets.php';
    }

    public function vehicleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($name === '' || $typeVehiculeId <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();

        try {
            if ($id > 0) {
                $vehicleRepository->update($id, $name, $typeVehiculeId, $active);
                $this->redirect('/index.php?controller=manager_assets&action=index&success=vehicle_updated');
            }

            $vehicleRepository->create($name, $typeVehiculeId, $active);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=vehicle_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=vehicle_save_failed');
        }
    }

    public function vehicleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();

        try {
            $vehicleRepository->delete($id);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=vehicle_deleted');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=vehicle_delete_failed');
        }
    }

    public function zoneSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($vehicleId <= 0 || $name === '') {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_zone');
        }

        $zoneRepository = new ZoneRepository();
        if (!$zoneRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=zones_table_missing');
        }

        try {
            $zoneRepository->create($vehicleId, $name);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=zone_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=zone_save_failed');
        }
    }

    public function zoneDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_zone');
        }

        $zoneRepository = new ZoneRepository();
        if (!$zoneRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=zones_table_missing');
        }

        try {
            $zoneRepository->delete($id);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=zone_deleted');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=zone_delete_failed');
        }
    }

    public function posteSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;

        if ($name === '' || $code === '' || $typeVehiculeId <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_poste');
        }

        $posteRepository = new PosteRepository();

        try {
            if ($id > 0) {
                $posteRepository->update($id, $name, $code, $typeVehiculeId);
                $this->redirect('/index.php?controller=manager_assets&action=index&success=poste_updated');
            }

            $posteRepository->create($name, $code, $typeVehiculeId);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=poste_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=poste_save_failed');
        }
    }

    public function posteDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_poste');
        }

        $posteRepository = new PosteRepository();

        try {
            $posteRepository->delete($id);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=poste_deleted');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=poste_delete_failed');
        }
    }

    public function controleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $label = trim((string) ($_POST['libelle'] ?? ''));
        $posteId = isset($_POST['poste_id']) ? (int) $_POST['poste_id'] : 0;
        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $zoneId = isset($_POST['zone_id']) ? (int) $_POST['zone_id'] : 0;
        $zoneName = trim((string) ($_POST['zone_nom'] ?? ''));
        $order = isset($_POST['ordre']) ? (int) $_POST['ordre'] : 0;
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($label === '' || $posteId <= 0 || $order < 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle');
        }

        $controleRepository = new ControleRepository();
        $zoneRepository = new ZoneRepository();

        if ($controleRepository->hasHierarchicalSchema()) {
            if ($vehicleId <= 0 || $zoneId <= 0) {
                $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle_link');
            }

            if (!$this->isPosteCompatibleWithVehicle($posteId, $vehicleId)) {
                $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle_link');
            }

            if (!$zoneRepository->belongsToVehicle($zoneId, $vehicleId)) {
                $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle_link');
            }
        } else {
            if ($zoneName === '') {
                $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle');
            }
        }

        try {
            if ($id > 0) {
                $controleRepository->update(
                    $id,
                    $label,
                    $posteId,
                    $controleRepository->hasHierarchicalSchema() ? '' : $zoneName,
                    $order,
                    $active,
                    $controleRepository->hasHierarchicalSchema() ? $vehicleId : null,
                    $controleRepository->hasHierarchicalSchema() ? $zoneId : null
                );
                $this->redirect('/index.php?controller=manager_assets&action=index&success=controle_updated');
            }

            $controleRepository->create(
                $label,
                $posteId,
                $controleRepository->hasHierarchicalSchema() ? '' : $zoneName,
                $order,
                $active,
                $controleRepository->hasHierarchicalSchema() ? $vehicleId : null,
                $controleRepository->hasHierarchicalSchema() ? $zoneId : null
            );
            $this->redirect('/index.php?controller=manager_assets&action=index&success=controle_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=controle_save_failed');
        }
    }

    public function controleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=invalid_controle');
        }

        $controleRepository = new ControleRepository();

        try {
            $controleRepository->delete($id);
            $this->redirect('/index.php?controller=manager_assets&action=index&success=controle_deleted');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=index&error=controle_delete_failed');
        }
    }

    private function isPosteCompatibleWithVehicle(int $posteId, int $vehicleId): bool
    {
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();

        $vehicle = $vehicleRepository->findById($vehicleId);
        $poste = $posteRepository->findById($posteId);

        if ($vehicle === null || $poste === null) {
            return false;
        }

        return (int) $vehicle['type_vehicule_id'] === (int) $poste['type_vehicule_id'];
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
