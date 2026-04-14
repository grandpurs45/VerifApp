<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;
use App\Repositories\TypeVehiculeRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\ZoneRepository;
use PDOException;
use RuntimeException;
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
        $postesByType = [];
        foreach ($postes as $poste) {
            $typeId = (int) ($poste['type_vehicule_id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            if (!isset($postesByType[$typeId])) {
                $postesByType[$typeId] = 0;
            }
            $postesByType[$typeId]++;
        }
        $typesVehicules = $typeVehiculeRepository->findAll($caserneId);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_types.php';
    }

    public function typeDetail(int $typeVehiculeId): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $posteRepository = new PosteRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();

        $typeVehicule = $typeVehiculeRepository->findById($typeVehiculeId, $caserneId);
        if ($typeVehicule === null) {
            $this->redirect('/index.php?controller=manager_assets&action=types&error=invalid_type');
        }

        $postes = $posteRepository->findByTypeIdDetailed($typeVehiculeId, $caserneId);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_type_detail.php';
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
        foreach ($vehicles as &$vehicle) {
            $vehicle['indicatif'] = $this->extractIndicatifFromVehicleName(
                (string) ($vehicle['nom'] ?? ''),
                (string) ($vehicle['type_vehicule'] ?? '')
            );
        }
        unset($vehicle);
        $postes = $posteRepository->findAllDetailed($caserneId);
        $controles = $controleRepository->findAllDetailed($caserneId);
        $typesVehicules = $typeVehiculeRepository->findAll($caserneId);
        $zones = $zoneRepository->findAllDetailed($caserneId);
        $zonesByVehicle = [];
        foreach ($zones as $zone) {
            $vehicleId = (int) ($zone['vehicule_id'] ?? 0);
            if ($vehicleId <= 0) {
                continue;
            }
            if (!isset($zonesByVehicle[$vehicleId])) {
                $zonesByVehicle[$vehicleId] = 0;
            }
            $zonesByVehicle[$vehicleId]++;
        }

        $controlesByVehicle = [];
        foreach ($controles as $controle) {
            $vehicleId = (int) ($controle['vehicule_id'] ?? 0);
            if ($vehicleId <= 0) {
                continue;
            }
            if (!isset($controlesByVehicle[$vehicleId])) {
                $controlesByVehicle[$vehicleId] = 0;
            }
            $controlesByVehicle[$vehicleId]++;
        }

        foreach ($vehicles as &$vehicle) {
            $vehicleId = (int) ($vehicle['id'] ?? 0);
            $vehicle['zones_count'] = (int) ($zonesByVehicle[$vehicleId] ?? 0);
            $vehicle['controles_count'] = (int) ($controlesByVehicle[$vehicleId] ?? 0);
        }
        unset($vehicle);

        $settingRepository = new AppSettingRepository();
        $hasSettings = $settingRepository->isAvailable();
        foreach ($vehicles as &$vehicle) {
            $vehicleId = (int) ($vehicle['id'] ?? 0);
            $hasQr = false;
            if ($hasSettings && $vehicleId > 0) {
                $token = $settingRepository->get($this->vehicleQrSettingKey($caserneId, $vehicleId));
                $hasQr = $token !== null && trim($token) !== '';
            }
            $vehicle['has_vehicle_qr'] = $hasQr;
        }
        unset($vehicle);

        $zonesAvailable = $zoneRepository->isAvailable();
        $hierarchyAvailable = $controleRepository->hasHierarchicalSchema();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_vehicles.php';
    }

    public function vehicleDetail(int $vehicleId): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $vehicleRepository = new VehicleRepository();
        $zoneRepository = new ZoneRepository();

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        if ($vehicle === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }
        $vehicle['indicatif'] = $this->extractIndicatifFromVehicleName(
            (string) ($vehicle['nom'] ?? ''),
            (string) ($vehicle['type_vehicule'] ?? '')
        );

        $zones = $zoneRepository->isAvailable() ? $zoneRepository->findByVehicleId($vehicleId, $caserneId) : [];
        $controleRepository = new ControleRepository();
        $controles = $controleRepository->findByVehicleIdDetailed($vehicleId, $caserneId);

        $appUrl = $this->resolvePublicBaseUrl();
        $vehicleToken = trim($this->getVehicleQrToken($caserneId, $vehicleId));
        $fieldVehicleGuestUrl = '';
        if ($vehicleToken !== '') {
            $fieldVehiclePath = '/index.php?controller=field&action=access'
                . '&token=' . rawurlencode($vehicleToken)
                . '&caserne_id=' . $caserneId
                . '&vehicle_id=' . $vehicleId;
            $fieldVehicleGuestUrl = $appUrl !== '' ? $appUrl . $fieldVehiclePath : $fieldVehiclePath;
        }

        $vehicleZonesCount = count($zones);
        $vehicleControlesCount = count($controles);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_vehicle_summary.php';
    }

    public function vehicleZones(int $vehicleId): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $vehicleRepository = new VehicleRepository();
        $zoneRepository = new ZoneRepository();

        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        if ($vehicle === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $zonesAvailable = $zoneRepository->isAvailable();
        $zones = $zonesAvailable ? $zoneRepository->findByVehicleId($vehicleId, $caserneId) : [];
        $posteRepository = new PosteRepository();
        $controleRepository = new ControleRepository();
        $postes = $posteRepository->findByTypeIdDetailed((int) ($vehicle['type_vehicule_id'] ?? 0), $caserneId);
        $controles = $controleRepository->findByVehicleIdDetailed($vehicleId, $caserneId);
        $hierarchyAvailable = $controleRepository->hasHierarchicalSchema();
        $inputSchemaAvailable = $controleRepository->hasInputSchema();
        $nextOrder = 1;
        foreach ($controles as $controle) {
            $currentOrder = (int) ($controle['ordre'] ?? 0);
            if ($currentOrder >= $nextOrder) {
                $nextOrder = $currentOrder + 1;
            }
        }
        $managerUser = $_SESSION['manager_user'] ?? null;
        $flash = [
            'success' => isset($_GET['success']) ? (string) $_GET['success'] : '',
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : '',
        ];

        require dirname(__DIR__, 2) . '/public/views/manager_vehicle_detail.php';
    }

    public function vehicleQrSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $vehicleId = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
        $action = strtolower(trim((string) ($_POST['qr_action'] ?? '')));

        if ($vehicleId <= 0 || !in_array($action, ['generate', 'delete'], true)) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();
        $vehicle = $vehicleRepository->findById($vehicleId, $caserneId);
        if ($vehicle === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicle_detail&id=' . $vehicleId . '&error=vehicle_qr_store_failed');
        }

        $settingKey = $this->vehicleQrSettingKey($caserneId, $vehicleId);
        if ($action === 'generate') {
            $token = bin2hex(random_bytes(32));
            $saved = $settingRepository->set($settingKey, $token);
            $this->redirect('/index.php?controller=manager_assets&action=vehicle_detail&id=' . $vehicleId . '&' . ($saved ? 'success=vehicle_qr_saved' : 'error=vehicle_qr_store_failed'));
        }

        $deleted = $settingRepository->delete($settingKey);
        $this->redirect('/index.php?controller=manager_assets&action=vehicle_detail&id=' . $vehicleId . '&' . ($deleted ? 'success=vehicle_qr_deleted' : 'error=vehicle_qr_store_failed'));
    }

    public function typeSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = $this->normalizeTypeName((string) ($_POST['nom'] ?? ''));

        if ($name === '' || !$this->isValidTypeName($name)) {
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
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;
        $indicatif = $this->normalizeVehicleIndicatif((string) ($_POST['indicatif'] ?? ''));
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($indicatif === '' || $typeVehiculeId <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_save_failed');
        }

        $name = $this->composeVehicleName($typeVehiculeRepository, $typeVehiculeId, $indicatif, $caserneId);
        if ($name === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        if ($vehicleRepository->existsByTypeAndName($typeVehiculeId, $name, $caserneId, $id > 0 ? $id : null)) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_duplicate');
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
        $deleteMode = trim((string) ($_POST['delete_mode'] ?? 'safe'));
        $forceDelete = $deleteMode === 'force';

        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $vehicleRepository = new VehicleRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_delete_failed');
        }

        $vehicle = $vehicleRepository->findById($id, $caserneId);
        if ($vehicle === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        if ((int) ($vehicle['actif'] ?? 1) === 1) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_delete_requires_inactive');
        }

        try {
            if ($forceDelete) {
                $vehicleRepository->forceDelete($id, $caserneId);
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_deleted_force');
            }

            $vehicleRepository->delete($id, $caserneId);
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_deleted');
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_in_use');
            }
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=' . ($forceDelete ? 'vehicle_force_delete_failed' : 'vehicle_delete_failed'));
        }
    }

    public function vehicleDuplicate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $sourceVehicleId = isset($_POST['source_vehicle_id']) ? (int) $_POST['source_vehicle_id'] : 0;
        $duplicateScope = trim((string) ($_POST['duplicate_scope'] ?? 'vehicle_only'));
        $indicatif = $this->normalizeVehicleIndicatif((string) ($_POST['indicatif'] ?? ''));

        $allowedScopes = ['vehicle_only', 'with_zones', 'with_zones_controls'];
        if (!in_array($duplicateScope, $allowedScopes, true)) {
            $duplicateScope = 'vehicle_only';
        }

        if ($sourceVehicleId <= 0 || $indicatif === '') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_save_failed');
        }

        $vehicleRepository = new VehicleRepository();
        $typeVehiculeRepository = new TypeVehiculeRepository();
        $zoneRepository = new ZoneRepository();
        $controleRepository = new ControleRepository();

        $sourceVehicle = $vehicleRepository->findById($sourceVehicleId, $caserneId);
        if ($sourceVehicle === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        $typeVehiculeId = (int) ($sourceVehicle['type_vehicule_id'] ?? 0);
        $newVehicleName = $this->composeVehicleName($typeVehiculeRepository, $typeVehiculeId, $indicatif, $caserneId);
        if ($typeVehiculeId <= 0 || $newVehicleName === null) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=invalid_vehicle');
        }

        if ($vehicleRepository->existsByTypeAndName($typeVehiculeId, $newVehicleName, $caserneId)) {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_duplicate');
        }

        $connection = Database::getConnection();
        $newVehicleId = 0;

        try {
            $connection->beginTransaction();

            $createdId = $vehicleRepository->createAndReturnId(
                $newVehicleName,
                $typeVehiculeId,
                (int) ($sourceVehicle['actif'] ?? 1) === 1,
                $caserneId
            );

            if ($createdId === null) {
                throw new RuntimeException('vehicle_create_failed');
            }

            $newVehicleId = $createdId;
            $zoneMap = [];

            if ($duplicateScope !== 'vehicle_only') {
                $zoneMap = $this->duplicateZones($sourceVehicleId, $newVehicleId, $zoneRepository, $caserneId);
            }

            if ($duplicateScope === 'with_zones_controls') {
                $this->duplicateControles(
                    $sourceVehicleId,
                    $newVehicleId,
                    $zoneMap,
                    $controleRepository,
                    $caserneId
                );
            }

            $connection->commit();
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->redirect('/index.php?controller=manager_assets&action=vehicles&error=vehicle_save_failed');
        }

        $this->redirect('/index.php?controller=manager_assets&action=vehicles&success=vehicle_duplicated');
    }

    public function zoneSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $returnVehicleId = isset($_POST['return_vehicle_id']) ? (int) $_POST['return_vehicle_id'] : 0;
        $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($vehicleId <= 0 || $name === '') {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'invalid_zone'));
        }

        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'zone_save_failed'));
        }
        if (!$zoneRepository->isAvailable()) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'zones_table_missing'));
        }

        if ($parentId > 0 && !$zoneRepository->belongsToVehicle($parentId, $vehicleId, $caserneId)) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'invalid_zone'));
        }

        try {
            $zoneRepository->create($vehicleId, $name, $parentId > 0 ? $parentId : null, $caserneId);
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, '', 'zone_created'));
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'zone_duplicate'));
            }
            $this->redirect($this->vehicleRedirectPath($returnVehicleId > 0 ? $returnVehicleId : $vehicleId, 'zone_save_failed'));
        }
    }

    public function zoneDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $returnVehicleId = isset($_POST['return_vehicle_id']) ? (int) $_POST['return_vehicle_id'] : 0;

        if ($id <= 0) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'invalid_zone'));
        }

        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'zone_delete_failed'));
        }
        if (!$zoneRepository->isAvailable()) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'zones_table_missing'));
        }

        try {
            $zoneRepository->delete($id, $caserneId);
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, '', 'zone_deleted'));
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'zone_in_use'));
            }
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'zone_delete_failed'));
        }
    }

    public function posteSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $returnTypeId = isset($_POST['return_type_id']) ? (int) $_POST['return_type_id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $code = $this->normalizePosteCode((string) ($_POST['code'] ?? ''));
        $typeVehiculeId = isset($_POST['type_vehicule_id']) ? (int) $_POST['type_vehicule_id'] : 0;

        if ($name === '' || $code === '' || $typeVehiculeId <= 0 || !$this->isValidPosteCode($code)) {
            $this->redirect($this->posteRedirectPath($returnTypeId, 'invalid_poste'));
        }

        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->posteRedirectPath($returnTypeId, 'poste_save_failed'));
        }

        try {
            if ($id > 0) {
                $posteRepository->update($id, $name, $code, $typeVehiculeId, $caserneId);
                $this->redirect($this->posteRedirectPath($returnTypeId, '', 'poste_updated'));
            }

            $posteRepository->create($name, $code, $typeVehiculeId, $caserneId);
            $this->redirect($this->posteRedirectPath($returnTypeId, '', 'poste_created'));
        } catch (Throwable $throwable) {
            $this->redirect($this->posteRedirectPath($returnTypeId, 'poste_save_failed'));
        }
    }

    public function posteDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=types');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $returnTypeId = isset($_POST['return_type_id']) ? (int) $_POST['return_type_id'] : 0;

        if ($id <= 0) {
            $this->redirect($this->posteRedirectPath($returnTypeId, 'invalid_poste'));
        }

        $posteRepository = new PosteRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->posteRedirectPath($returnTypeId, 'poste_delete_failed'));
        }

        try {
            $posteRepository->delete($id, $caserneId);
            $this->redirect($this->posteRedirectPath($returnTypeId, '', 'poste_deleted'));
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect($this->posteRedirectPath($returnTypeId, 'poste_in_use'));
            }
            $this->redirect($this->posteRedirectPath($returnTypeId, 'poste_delete_failed'));
        }
    }

    public function controleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $returnVehicleId = isset($_POST['return_vehicle_id']) ? (int) $_POST['return_vehicle_id'] : 0;
        $label = trim((string) ($_POST['libelle'] ?? ''));
        $posteId = isset($_POST['poste_id']) ? (int) $_POST['poste_id'] : 0;
        $vehicleId = isset($_POST['vehicule_id']) ? (int) $_POST['vehicule_id'] : 0;
        $zoneId = isset($_POST['zone_id']) ? (int) $_POST['zone_id'] : 0;
        $zoneName = trim((string) ($_POST['zone_nom'] ?? ''));
        $order = isset($_POST['ordre']) ? (int) $_POST['ordre'] : 0;
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';
        $inputType = strtolower(trim((string) ($_POST['type_saisie'] ?? 'statut')));
        $unitRaw = trim((string) ($_POST['unite'] ?? ''));
        $expectedValueRaw = trim((string) ($_POST['valeur_attendue'] ?? ''));
        $minThresholdRaw = trim((string) ($_POST['seuil_min'] ?? ''));
        $maxThresholdRaw = trim((string) ($_POST['seuil_max'] ?? ''));

        $allowedInputTypes = ['statut', 'quantite', 'mesure'];
        if (!in_array($inputType, $allowedInputTypes, true)) {
            $inputType = 'statut';
        }

        $expectedValue = $this->parseIntegerOrNull($expectedValueRaw);
        $minThreshold = $this->parseIntegerOrNull($minThresholdRaw);
        $maxThreshold = $this->parseIntegerOrNull($maxThresholdRaw);
        $unit = $unitRaw === '' ? null : $unitRaw;
        $targetVehicleId = $returnVehicleId > 0 ? $returnVehicleId : $vehicleId;

        if ($label === '' || $posteId <= 0 || $order < 0) {
            $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle'));
        }

        if (($minThresholdRaw !== '' && $minThreshold === null)
            || ($maxThresholdRaw !== '' && $maxThreshold === null)
            || ($expectedValueRaw !== '' && $expectedValue === null)
        ) {
            $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle'));
        }

        if ($inputType === 'statut') {
            $expectedValue = null;
            $minThreshold = null;
            $maxThreshold = null;
            $unit = null;
        } elseif ($inputType === 'quantite') {
            if ($expectedValue === null || $expectedValue <= 0) {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle'));
            }
            $minThreshold = null;
            $maxThreshold = null;
            $unit = null;
        } elseif ($inputType === 'mesure') {
            $expectedValue = null;
            if ($unit === null || ($minThreshold === null && $maxThreshold === null)) {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle'));
            }
        }

        $controleRepository = new ControleRepository();
        $zoneRepository = new ZoneRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'controle_save_failed'));
        }

        if ($controleRepository->hasHierarchicalSchema()) {
            if ($vehicleId <= 0 || $zoneId <= 0) {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle_link'));
            }

            if (!$this->isPosteCompatibleWithVehicle($posteId, $vehicleId, $caserneId)) {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle_link'));
            }

            if (!$zoneRepository->belongsToVehicle($zoneId, $vehicleId, $caserneId)) {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle_link'));
            }
        } else {
            if ($zoneName === '') {
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'invalid_controle'));
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
                $this->redirect($this->vehicleRedirectPath($targetVehicleId, '', 'controle_updated'));
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
            $this->redirect($this->vehicleRedirectPath($targetVehicleId, '', 'controle_created'));
        } catch (Throwable $throwable) {
            $this->redirect($this->vehicleRedirectPath($targetVehicleId, 'controle_save_failed'));
        }
    }

    public function controleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_assets&action=vehicles');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $returnVehicleId = isset($_POST['return_vehicle_id']) ? (int) $_POST['return_vehicle_id'] : 0;

        if ($id <= 0) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'invalid_controle'));
        }

        $controleRepository = new ControleRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'controle_delete_failed'));
        }

        try {
            $controleRepository->delete($id, $caserneId);
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, '', 'controle_deleted'));
        } catch (Throwable $throwable) {
            if ($this->isConstraintViolation($throwable)) {
                $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'controle_in_use'));
            }
            $this->redirect($this->vehicleRedirectPath($returnVehicleId, 'controle_delete_failed'));
        }
    }

    /**
     * @return array<int, int> oldZoneId => newZoneId
     */
    private function duplicateZones(int $sourceVehicleId, int $newVehicleId, ZoneRepository $zoneRepository, int $caserneId): array
    {
        if (!$zoneRepository->isAvailable()) {
            return [];
        }

        $sourceZones = $zoneRepository->findByVehicleId($sourceVehicleId, $caserneId);
        if ($sourceZones === []) {
            return [];
        }

        usort($sourceZones, static function (array $a, array $b): int {
            $levelA = (int) ($a['niveau'] ?? 1);
            $levelB = (int) ($b['niveau'] ?? 1);
            if ($levelA !== $levelB) {
                return $levelA <=> $levelB;
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $zoneMap = [];
        $connection = Database::getConnection();

        foreach ($sourceZones as $sourceZone) {
            $oldZoneId = (int) ($sourceZone['id'] ?? 0);
            if ($oldZoneId <= 0) {
                continue;
            }

            $oldParentId = (int) ($sourceZone['parent_id'] ?? 0);
            $newParentId = $oldParentId > 0 ? ($zoneMap[$oldParentId] ?? null) : null;
            $zoneName = trim((string) ($sourceZone['nom'] ?? ''));
            if ($zoneName === '') {
                continue;
            }

            $created = $zoneRepository->create($newVehicleId, $zoneName, $newParentId, $caserneId);
            if (!$created) {
                throw new RuntimeException('zone_copy_failed');
            }

            $newZoneId = (int) $connection->lastInsertId();
            if ($newZoneId <= 0) {
                throw new RuntimeException('zone_copy_failed');
            }

            $zoneMap[$oldZoneId] = $newZoneId;
        }

        return $zoneMap;
    }

    /**
     * @param array<int, int> $zoneMap oldZoneId => newZoneId
     */
    private function duplicateControles(
        int $sourceVehicleId,
        int $newVehicleId,
        array $zoneMap,
        ControleRepository $controleRepository,
        int $caserneId
    ): void {
        $sourceControles = $controleRepository->findByVehicleIdDetailed($sourceVehicleId, $caserneId);
        if ($sourceControles === []) {
            return;
        }

        foreach ($sourceControles as $controle) {
            $zoneId = (int) ($controle['zone_id'] ?? 0);
            $newZoneId = $zoneId > 0 ? ($zoneMap[$zoneId] ?? 0) : 0;
            if ($controleRepository->hasHierarchicalSchema() && $newZoneId <= 0) {
                continue;
            }

            $created = $controleRepository->create(
                (string) ($controle['libelle'] ?? ''),
                (int) ($controle['poste_id'] ?? 0),
                (string) ($controle['zone'] ?? ''),
                (int) ($controle['ordre'] ?? 0),
                ((int) ($controle['actif'] ?? 1)) === 1,
                $caserneId,
                $controleRepository->hasHierarchicalSchema() ? $newVehicleId : null,
                $controleRepository->hasHierarchicalSchema() ? $newZoneId : null,
                (string) ($controle['type_saisie'] ?? 'statut'),
                isset($controle['valeur_attendue']) ? (float) $controle['valeur_attendue'] : null,
                isset($controle['unite']) ? (string) $controle['unite'] : null,
                isset($controle['seuil_min']) ? (float) $controle['seuil_min'] : null,
                isset($controle['seuil_max']) ? (float) $controle['seuil_max'] : null,
            );

            if (!$created) {
                throw new RuntimeException('controle_copy_failed');
            }
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

    private function posteRedirectPath(int $returnTypeId, string $error = '', string $success = ''): string
    {
        if ($returnTypeId > 0) {
            $base = '/index.php?controller=manager_assets&action=type_detail&type_id=' . $returnTypeId;
        } else {
            $base = '/index.php?controller=manager_assets&action=types';
        }

        if ($error !== '') {
            return $base . '&error=' . rawurlencode($error);
        }

        if ($success !== '') {
            return $base . '&success=' . rawurlencode($success);
        }

        return $base;
    }

    private function vehicleRedirectPath(int $vehicleId = 0, string $error = '', string $success = ''): string
    {
        if ($vehicleId > 0) {
            $base = '/index.php?controller=manager_assets&action=vehicle_zones&id=' . $vehicleId;
        } else {
            $base = '/index.php?controller=manager_assets&action=vehicles';
        }

        if ($error !== '') {
            return $base . '&error=' . rawurlencode($error);
        }

        if ($success !== '') {
            return $base . '&success=' . rawurlencode($success);
        }

        return $base;
    }

    private function normalizeVehicleIndicatif(string $raw): string
    {
        $value = strtoupper(trim($raw));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function composeVehicleName(
        TypeVehiculeRepository $typeVehiculeRepository,
        int $typeVehiculeId,
        string $indicatif,
        int $caserneId
    ): ?string {
        if ($typeVehiculeId <= 0 || $indicatif === '') {
            return null;
        }

        $typeVehicule = $typeVehiculeRepository->findById($typeVehiculeId, $caserneId);
        if ($typeVehicule === null) {
            return null;
        }

        $typeName = trim((string) ($typeVehicule['nom'] ?? ''));
        if ($typeName === '') {
            return null;
        }

        $normalizedIndicatif = $this->normalizeVehicleIndicatif($indicatif);
        $prefixUpper = strtoupper($typeName);
        if (str_starts_with($normalizedIndicatif, $prefixUpper . ' ')) {
            $normalizedIndicatif = trim(substr($normalizedIndicatif, strlen($prefixUpper)));
        } elseif ($normalizedIndicatif === $prefixUpper) {
            $normalizedIndicatif = '';
        }

        if ($normalizedIndicatif === '') {
            return null;
        }

        return $typeName . ' ' . $normalizedIndicatif;
    }

    private function extractIndicatifFromVehicleName(string $vehicleName, string $typeName): string
    {
        $vehicleName = trim($vehicleName);
        $typeName = trim($typeName);

        if ($vehicleName === '' || $typeName === '') {
            return $vehicleName;
        }

        $prefixLength = strlen($typeName);
        if (strncasecmp($vehicleName, $typeName, $prefixLength) !== 0) {
            return $vehicleName;
        }

        $rest = trim(substr($vehicleName, $prefixLength));
        return $rest === '' ? $vehicleName : $rest;
    }

    private function normalizePosteCode(string $raw): string
    {
        $value = strtoupper(trim($raw));
        $value = preg_replace('/\s+/', '', $value) ?? '';

        return substr($value, 0, 10);
    }

    private function isValidPosteCode(string $code): bool
    {
        if ($code === '' || strlen($code) > 10) {
            return false;
        }

        return (bool) preg_match('/^[A-Z0-9_-]{1,10}$/', $code);
    }

    private function normalizeTypeName(string $raw): string
    {
        $value = strtoupper(trim($raw));
        $value = preg_replace('/\s+/', '', $value) ?? '';

        return substr($value, 0, 10);
    }

    private function isValidTypeName(string $name): bool
    {
        if ($name === '' || strlen($name) > 10) {
            return false;
        }

        return (bool) preg_match('/^[A-Z0-9_-]{1,10}$/', $name);
    }

    private function resolvePublicBaseUrl(): string
    {
        $requestHost = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        if ($requestHost !== '') {
            $isHttps =
                (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
            $scheme = $isHttps ? 'https' : 'http';

            return $scheme . '://' . $requestHost;
        }

        return rtrim((string) (Env::get('APP_URL', '') ?? ''), '/');
    }

    private function getSettingValue(string $settingKey, string $envKey, string $default): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            $value = $repository->get($settingKey);
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    }

    private function getScopedSettingValue(string $settingKey, string $envKey, ?int $caserneId, string $default): string
    {
        if ($caserneId !== null && $caserneId > 0) {
            $scoped = $this->getSettingValue($settingKey . '_caserne_' . $caserneId, $envKey, '');
            if ($scoped !== '') {
                return $scoped;
            }
        }

        return $this->getSettingValue($settingKey, $envKey, $default);
    }

    private function getVehicleQrToken(int $caserneId, int $vehicleId): string
    {
        $repository = new AppSettingRepository();
        if (!$repository->isAvailable()) {
            return '';
        }

        $value = $repository->get($this->vehicleQrSettingKey($caserneId, $vehicleId));
        if ($value === null || trim($value) === '') {
            return '';
        }

        return trim($value);
    }

    private function vehicleQrSettingKey(int $caserneId, int $vehicleId): string
    {
        return 'field_vehicle_qr_token_caserne_' . $caserneId . '_vehicle_' . $vehicleId;
    }
}
