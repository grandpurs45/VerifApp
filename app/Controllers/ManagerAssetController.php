<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;
use App\Repositories\TypeVehiculeRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\ZoneRepository;
use PDOException;
use Throwable;

final class ManagerAssetController
{
    public function index(): void
    {
        $this->redirect('/index.php?controller=manager_assets&action=types');
    }

    public function types(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $posteRepository = new PosteRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();

        $postes = $posteRepository->findAllDetailed($caserneId);
        $typesVehicules = $typeVehiculeRepository->findAll($caserneId);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_types.php';
    }

    public function vehicles(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();
        $zoneRepository = new ZoneRepository();

        $vehicles = $vehicleRepository->findAllDetailed($caserneId);
        $postes = $posteRepository->findAllDetailed($caserneId);
        $controles = $controleRepository->findAllDetailed($caserneId);
        $typesVehicules = $typeVehiculeRepository->findAll($caserneId);
        $zones = $zoneRepository->findAllDetailed($caserneId);
        $zonesAvailable = $zoneRepository->isAvailable();
        $hierarchyAvailable = $controleRepository->hasHierarchicalSchema();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_vehicles.php';
    }

    public function typeSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($name === '') {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=invalid_type');
        }

        $typeRepository = new TypeVehiculeRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=type_save_failed');
        }

        try {
            if ($id > 0) {
                $typeRepository->update($id, $name, $caserneId);
                $this->redirect('/index.php?controller=manager_assets&action=types&success=type_updated');
            }

            $typeRepository->create($name, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=types&success=type_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=type_save_failed');
        }
    }

    public function typeDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=invalid_type');
        }

        $typeRepository = new TypeVehiculeRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=type_delete_failed');
        }

        try {
            $typeRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=types&success=type_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=types&error=type_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=types&error=type_delete_failed');
        }
    }

    public function vehicleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($name === '' || $typeVehiculeId <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_save_failed');
        }

        try {
            if ($id > 0) {
                $vehicleRepository->update($id, $name, $typeVehiculeId, $active, $caserneId);
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_updated');
            }

            $vehicleRepository->create($name, $typeVehiculeId, $active, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_save_failed');
        }
    }

    public function vehicleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_delete_failed');
        }

        try {
            $vehicleRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_delete_failed');
        }
    }

    public function zoneSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($vehicleId <= 0 || $name === '') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_zone');
        }

        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zone_save_failed');
        }
        if (!$zoneRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zones_table_missing');
        }

        if ($parentId > 0 && !$zoneRepository->belongsToVehicle($parentId, $vehicleId, $caserneId)) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_zone');
        }

        try {
            $zoneRepository->create($vehicleId, $name, $parentId > 0 ? $parentId : null, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=zone_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zone_save_failed');
        }
    }

    public function zoneDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_zone');
        }

        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zone_delete_failed');
        }
        if (!$zoneRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zones_table_missing');
        }

        try {
            $zoneRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=zone_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zone_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=zone_delete_failed');
        }
    }

    public function posteSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;

        if ($name === '' || $code === '' || $typeVehiculeId <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=invalid_poste');
        }

        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=poste_save_failed');
        }

        try {
            if ($id > 0) {
                $posteRepository->update($id, $name, $code, $typeVehiculeId, $caserneId);
                $this->redirect('/index.php?controller=manager_assets&action=types&success=poste_updated');
            }

            $posteRepository->create($name, $code, $typeVehiculeId, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=types&success=poste_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=poste_save_failed');
        }
    }

    public function posteDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=invalid_poste');
        }

        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=poste_delete_failed');
        }

        try {
            $posteRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=types&success=poste_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=types&error=poste_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=types&error=poste_delete_failed');
        }
    }

    public function controleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $label = trim((string) ($_POST['libelle'] ?? ''));
        $posteId = isset($_POST['poste_id']) ? (int) $_POST['poste_id'] : 0;
        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $zoneId = isset($_POST['zone_id']) ? (int) $_POST['zone_id'] : 0;
        $zoneName = trim((string) ($_POST['zone_nom'] ?? ''));
        $order = isset($_POST['ordre']) ? (int) $_POST['ordre'] : 0;
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';
        $inputType = strtolower(trim((string) ($_POST['type_saisie'] ?? 'statut')));
        $unitRaw = trim((string) ($_POST['unite'] ?? ''));
        $minThresholdRaw = trim((string) ($_POST['seuil_min'] ?? ''));
        $maxThresholdRaw = trim((string) ($_POST['seuil_max'] ?? ''));

        $allowedInputTypes = ['statut', 'quantite', 'mesure'];
        if (!in_array($inputType, $allowedInputTypes, true)) {
            $inputType = 'statut';
        }

        $expectedValue = null;
        $minThreshold = $this->parseIntegerOrNull($minThresholdRaw);
        $maxThreshold = $this->parseIntegerOrNull($maxThresholdRaw);
        $unit = $unitRaw === '' ? null : $unitRaw;

        if ($label === '' || $posteId <= 0 || $order < 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle');
        }

        if (($minThresholdRaw !== '' && $minThreshold === null)
            || ($maxThresholdRaw !== '' && $maxThreshold === null)
        ) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle');
        }

        if ($inputType === 'statut') {
            $expectedValue = null;
            $minThreshold = null;
            $maxThreshold = null;
            $unit = null;
        } elseif ($inputType === 'quantite') {
            $expectedValue = null;
            $minThreshold = null;
            $maxThreshold = null;
            $unit = null;
        } elseif ($inputType === 'mesure') {
            $expectedValue = null;
            if ($unit === null || ($minThreshold === null && $maxThreshold === null)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle');
            }
        }

        $controleRepository = new ControleRepository();
        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=controle_save_failed');
        }

        if ($controleRepository->hasHierarchicalSchema()) {
            if ($vehicleId <= 0 || $zoneId <= 0) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle_link');
            }

            if (!$this->isPosteCompatibleWithVehicle($posteId, $vehicleId, $caserneId)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle_link');
            }

            if (!$zoneRepository->belongsToVehicle($zoneId, $vehicleId, $caserneId)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle_link');
            }
        } else {
            if ($zoneName === '') {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle');
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
                    $caserneId,
                    $controleRepository->hasHierarchicalSchema() ? $vehicleId : null,
                    $controleRepository->hasHierarchicalSchema() ? $zoneId : null,
                    $inputType,
                    $expectedValue,
                    $unit,
                    $minThreshold,
                    $maxThreshold
                );
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=controle_updated');
            }

            $controleRepository->create(
                $label,
                $posteId,
                $controleRepository->hasHierarchicalSchema() ? '' : $zoneName,
                $order,
                $active,
                $caserneId,
                $controleRepository->hasHierarchicalSchema() ? $vehicleId : null,
                $controleRepository->hasHierarchicalSchema() ? $zoneId : null,
                $inputType,
                $expectedValue,
                $unit,
                $minThreshold,
                $maxThreshold
            );
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=controle_created');
        } catch (Throwable $throwable) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=controle_save_failed');
        }
    }

    public function controleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_controle');
        }

        $controleRepository = new ControleRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=controle_delete_failed');
        }

        try {
            $controleRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=controle_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=controle_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=controle_delete_failed');
        }
    }

    private function isPosteCompatibleWithVehicle(int $posteId, int $vehicleId, int $caserneId): bool
    {
        $vehicleRepository = new VehicleRepository();
        $posteRepository = new PosteRepository();

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        $poste = $posteRepository->findById($posteId, $caserneId);

        if ($vehicle === null || $poste === null) {
            return false;
        }

        return (int) $vehicle['type_vehicule_id'] === (int) $poste['type_vehicule_id'];
    }

    private function parseIntegerOrNull(string $raw): ?float
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (float) ((int) $value);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function isConstraintViolation(Throwable $throwable): bool
    {
        if ($throwable instanceof PDOException && (string) $throwable->getCode() === '23000') {
            return true;
        }

        return str_contains(strtolower($throwable->getMessage()), 'foreign key constraint fails');
    }

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }
}
