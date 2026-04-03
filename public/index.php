<?php

declare(strict_types=1);

use App\Controllers\AnomalyController;
use App\Controllers\AuthController;
use App\Controllers\ControleController;
use App\Controllers\FieldAuthController;
use App\Controllers\FieldController;
use App\Controllers\HomeController;
use App\Controllers\ManagerAdminController;
use App\Controllers\ManagerAssetController;
use App\Controllers\ManagerController;
use App\Controllers\ManagerPharmacyController;
use App\Controllers\ManagerRoleController;
use App\Controllers\ManagerUserController;
use App\Controllers\PharmacyController;
use App\Controllers\PosteController;
use App\Controllers\VehicleController;
use App\Controllers\VerificationController;
use App\Core\Autoloader;
use App\Core\Env;
use App\Core\ManagerAccess;
use App\Repositories\AppSettingRepository;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$controllerName = isset($_GET['controller']) ? (string) $_GET['controller'] : null;
$action = isset($_GET['action']) ? (string) $_GET['action'] : null;

$appSettings = new AppSettingRepository();
$readSetting = static function (string $settingKey, string $envKey, string $default = '') use ($appSettings): string {
    if ($appSettings->isAvailable()) {
        $value = $appSettings->get($settingKey);
        if ($value !== null && trim($value) !== '') {
            return trim($value);
        }
    }

    return trim((string) (Env::get($envKey, $default) ?? $default));
};
$isFeatureProtected = static function (string $settingPrefix, string $envKey) use ($appSettings): bool {
    if ($appSettings->isAvailable() && $appSettings->hasAnyWithPrefix($settingPrefix)) {
        return true;
    }

    $envValue = trim((string) (Env::get($envKey, '') ?? ''));
    return $envValue !== '';
};

$isManagerAuthenticated = isset($_SESSION['manager_user']) && is_array($_SESSION['manager_user']);
$isFieldAuthenticated = isset($_SESSION['field_user']) && is_array($_SESSION['field_user']);
$fieldProtected = $isFeatureProtected('field_qr_token', 'FIELD_QR_TOKEN');
$hasFieldAccess = !$fieldProtected || (isset($_SESSION['field_access']) && $_SESSION['field_access'] === true);
$pharmacyProtected = $isFeatureProtected('pharmacy_qr_token', 'PHARMACY_QR_TOKEN');
$hasPharmacyAccess = !$pharmacyProtected || (isset($_SESSION['pharmacy_access']) && $_SESSION['pharmacy_access'] === true);

$managerRoutes = [
    'manager/dashboard',
    'manager/account',
    'manager/account_save',
    'manager/forbidden',
    'manager_auth/switch_caserne',
    'manager_admin/menu',
    'manager_admin/settings',
    'manager_admin/verification_timing_save',
    'manager_admin/caserne_save',
    'manager_admin/regenerate_qr_token',
    'verifications/history',
    'verifications/monthly',
    'verifications/show',
    'verifications/export',
    'anomalies/index',
    'anomalies/update',
    'manager_assets/index',
    'manager_assets/types',
    'manager_assets/type_detail',
    'manager_assets/vehicles',
    'manager_assets/vehicle_detail',
    'manager_assets/vehicle_zones',
    'manager_assets/type_save',
    'manager_assets/type_delete',
    'manager_assets/vehicle_save',
    'manager_assets/vehicle_duplicate',
    'manager_assets/vehicle_qr_save',
    'manager_assets/vehicle_delete',
    'manager_assets/zone_save',
    'manager_assets/zone_delete',
    'manager_assets/poste_save',
    'manager_assets/poste_delete',
    'manager_assets/controle_save',
    'manager_assets/controle_delete',
    'manager_pharmacy/index',
    'manager_pharmacy/article_save',
    'manager_roles/index',
    'manager_roles/role_save',
    'manager_roles/role_delete',
    'manager_roles/permissions_save',
    'manager_users/index',
    'manager_users/save',
    'manager_users/delete',
];

$fieldRoutes = [
    'home/index',
    'postes/list',
    'controles/list',
    'verifications/store',
    'verifications/saved',
];

$pharmacyRoutes = [
    'pharmacy/form',
    'pharmacy/save',
];

$routeKey = ($controllerName ?? '') . '/' . ($action ?? '');
$managerSessionExpired = false;

$sessionTimeoutRaw = $readSetting('manager_session_ttl_minutes', 'MANAGER_SESSION_TTL_MINUTES', '120');
$sessionTimeoutMinutes = ctype_digit($sessionTimeoutRaw) ? (int) $sessionTimeoutRaw : 120;
if ($sessionTimeoutMinutes <= 0) {
    $sessionTimeoutMinutes = 120;
}

if ($isManagerAuthenticated) {
    $now = time();
    $lastActivity = isset($_SESSION['manager_last_activity']) ? (int) $_SESSION['manager_last_activity'] : $now;
    if (($now - $lastActivity) > ($sessionTimeoutMinutes * 60)) {
        unset($_SESSION['manager_user'], $_SESSION['manager_password_reset_user'], $_SESSION['manager_last_activity']);
        $isManagerAuthenticated = false;
        $managerSessionExpired = true;
    } else {
        $_SESSION['manager_last_activity'] = $now;
    }
}

if (in_array($routeKey, $managerRoutes, true) && !$isManagerAuthenticated) {
    $redirect = '/index.php?controller=manager_auth&action=login_form';
    if ($managerSessionExpired) {
        $redirect .= '&error=session_expired';
    }
    header('Location: ' . $redirect);
    exit;
}

$managerRoutePermissions = [
    'manager/dashboard' => 'dashboard.view',
    'manager/account' => 'dashboard.view',
    'manager/account_save' => 'dashboard.view',
    'manager_auth/switch_caserne' => 'dashboard.view',
    'manager_admin/menu' => 'users.manage',
    'manager_admin/settings' => 'users.manage',
    'manager_admin/verification_timing_save' => 'users.manage',
    'manager_admin/caserne_save' => 'users.manage',
    'manager_admin/regenerate_qr_token' => 'users.manage',
    'verifications/history' => 'verifications.history',
    'verifications/monthly' => 'verifications.history',
    'verifications/show' => 'verifications.history',
    'verifications/export' => 'verifications.history',
    'anomalies/index' => 'anomalies.manage',
    'anomalies/update' => 'anomalies.manage',
    'manager_assets/index' => 'assets.manage',
    'manager_assets/types' => 'assets.manage',
    'manager_assets/type_detail' => 'assets.manage',
    'manager_assets/vehicles' => 'assets.manage',
    'manager_assets/vehicle_detail' => 'assets.manage',
    'manager_assets/vehicle_zones' => 'assets.manage',
    'manager_assets/type_save' => 'assets.manage',
    'manager_assets/type_delete' => 'assets.manage',
    'manager_assets/vehicle_save' => 'assets.manage',
    'manager_assets/vehicle_duplicate' => 'assets.manage',
    'manager_assets/vehicle_qr_save' => 'assets.manage',
    'manager_assets/vehicle_delete' => 'assets.manage',
    'manager_assets/zone_save' => 'assets.manage',
    'manager_assets/zone_delete' => 'assets.manage',
    'manager_assets/poste_save' => 'assets.manage',
    'manager_assets/poste_delete' => 'assets.manage',
    'manager_assets/controle_save' => 'assets.manage',
    'manager_assets/controle_delete' => 'assets.manage',
    'manager_pharmacy/index' => 'pharmacy.manage',
    'manager_pharmacy/article_save' => 'pharmacy.manage',
    'manager_roles/index' => 'users.manage',
    'manager_roles/role_save' => 'users.manage',
    'manager_roles/role_delete' => 'users.manage',
    'manager_roles/permissions_save' => 'users.manage',
    'manager_users/index' => 'users.manage',
    'manager_users/save' => 'users.manage',
    'manager_users/delete' => 'users.manage',
];

if (isset($managerRoutePermissions[$routeKey]) && $isManagerAuthenticated) {
    $managerRole = (string) ($_SESSION['manager_user']['role'] ?? '');
    $permission = $managerRoutePermissions[$routeKey];
    if (!ManagerAccess::hasPermission($managerRole, $permission)) {
        header('Location: /index.php?controller=manager&action=forbidden');
        exit;
    }
}

if (in_array($routeKey, $fieldRoutes, true) && !$hasFieldAccess) {
    header('Location: /index.php?controller=field&action=denied');
    exit;
}

if (in_array($routeKey, $pharmacyRoutes, true) && !$hasPharmacyAccess) {
    header('Location: /index.php?controller=pharmacy&action=denied');
    exit;
}

if ($controllerName !== null) {
    if ($controllerName === 'manager_auth' && $action === 'login_form') {
        $controller = new AuthController();
        $controller->loginForm();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'login') {
        $controller = new AuthController();
        $controller->login();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'select_caserne_form') {
        $controller = new AuthController();
        $controller->selectCaserneForm();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'select_caserne') {
        $controller = new AuthController();
        $controller->selectCaserne();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'change_password_form') {
        $controller = new AuthController();
        $controller->changePasswordForm();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'change_password') {
        $controller = new AuthController();
        $controller->changePassword();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'logout') {
        $controller = new AuthController();
        $controller->logout();
        return;
    }

    if ($controllerName === 'manager_auth' && $action === 'switch_caserne') {
        $controller = new AuthController();
        $controller->switchCaserne();
        return;
    }

    if ($controllerName === 'field_auth' && $action === 'login_form') {
        if (!$hasFieldAccess) {
            header('Location: /index.php?controller=field&action=denied');
            exit;
        }
        $controller = new FieldAuthController();
        $controller->loginForm();
        return;
    }

    if ($controllerName === 'field_auth' && $action === 'login') {
        if (!$hasFieldAccess) {
            header('Location: /index.php?controller=field&action=denied');
            exit;
        }
        $controller = new FieldAuthController();
        $controller->login();
        return;
    }

    if ($controllerName === 'field_auth' && $action === 'logout') {
        $controller = new FieldAuthController();
        $controller->logout();
        return;
    }

    if ($controllerName === 'manager' && $action === 'dashboard') {
        $controller = new ManagerController();
        $controller->dashboard();
        return;
    }

    if ($controllerName === 'manager' && $action === 'account') {
        $controller = new ManagerController();
        $controller->account();
        return;
    }

    if ($controllerName === 'manager' && $action === 'account_save') {
        $controller = new ManagerController();
        $controller->accountSave();
        return;
    }

    if ($controllerName === 'manager' && $action === 'forbidden') {
        $controller = new ManagerController();
        $controller->forbidden();
        return;
    }

    if ($controllerName === 'manager_admin' && $action === 'menu') {
        $controller = new ManagerAdminController();
        $controller->menu();
        return;
    }

    if ($controllerName === 'manager_admin' && $action === 'settings') {
        $controller = new ManagerAdminController();
        $controller->settings();
        return;
    }

    if ($controllerName === 'manager_admin' && $action === 'regenerate_qr_token') {
        $controller = new ManagerAdminController();
        $controller->regenerateQrToken();
        return;
    }

    if ($controllerName === 'manager_admin' && $action === 'caserne_save') {
        $controller = new ManagerAdminController();
        $controller->caserneSave();
        return;
    }

    if ($controllerName === 'manager_admin' && $action === 'verification_timing_save') {
        $controller = new ManagerAdminController();
        $controller->verificationTimingSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'index') {
        $controller = new ManagerAssetController();
        $controller->index();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'types') {
        $controller = new ManagerAssetController();
        $controller->types();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'type_detail' && isset($_GET['type_id'])) {
        $controller = new ManagerAssetController();
        $controller->typeDetail((int) $_GET['type_id']);
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicles') {
        $controller = new ManagerAssetController();
        $controller->vehicles();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_detail' && isset($_GET['id'])) {
        $controller = new ManagerAssetController();
        $controller->vehicleDetail((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_zones' && isset($_GET['id'])) {
        $controller = new ManagerAssetController();
        $controller->vehicleZones((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'type_save') {
        $controller = new ManagerAssetController();
        $controller->typeSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'type_delete') {
        $controller = new ManagerAssetController();
        $controller->typeDelete();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_save') {
        $controller = new ManagerAssetController();
        $controller->vehicleSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_duplicate') {
        $controller = new ManagerAssetController();
        $controller->vehicleDuplicate();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_qr_save') {
        $controller = new ManagerAssetController();
        $controller->vehicleQrSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_delete') {
        $controller = new ManagerAssetController();
        $controller->vehicleDelete();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'zone_save') {
        $controller = new ManagerAssetController();
        $controller->zoneSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'zone_delete') {
        $controller = new ManagerAssetController();
        $controller->zoneDelete();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'poste_save') {
        $controller = new ManagerAssetController();
        $controller->posteSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'poste_delete') {
        $controller = new ManagerAssetController();
        $controller->posteDelete();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'controle_save') {
        $controller = new ManagerAssetController();
        $controller->controleSave();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'controle_delete') {
        $controller = new ManagerAssetController();
        $controller->controleDelete();
        return;
    }

    if ($controllerName === 'manager_pharmacy' && $action === 'index') {
        $controller = new ManagerPharmacyController();
        $controller->index();
        return;
    }

    if ($controllerName === 'manager_pharmacy' && $action === 'article_save') {
        $controller = new ManagerPharmacyController();
        $controller->articleSave();
        return;
    }

    if ($controllerName === 'manager_roles' && $action === 'index') {
        $controller = new ManagerRoleController();
        $controller->index();
        return;
    }

    if ($controllerName === 'manager_roles' && $action === 'role_save') {
        $controller = new ManagerRoleController();
        $controller->roleSave();
        return;
    }

    if ($controllerName === 'manager_roles' && $action === 'role_delete') {
        $controller = new ManagerRoleController();
        $controller->roleDelete();
        return;
    }

    if ($controllerName === 'manager_roles' && $action === 'permissions_save') {
        $controller = new ManagerRoleController();
        $controller->permissionsSave();
        return;
    }

    if ($controllerName === 'manager_users' && $action === 'index') {
        $controller = new ManagerUserController();
        $controller->index();
        return;
    }

    if ($controllerName === 'manager_users' && $action === 'save') {
        $controller = new ManagerUserController();
        $controller->save();
        return;
    }

    if ($controllerName === 'manager_users' && $action === 'delete') {
        $controller = new ManagerUserController();
        $controller->delete();
        return;
    }

    if ($controllerName === 'field' && $action === 'access') {
        $controller = new FieldController();
        $controller->access();
        return;
    }

    if ($controllerName === 'field' && $action === 'denied') {
        $controller = new FieldController();
        $controller->denied();
        return;
    }

    if ($controllerName === 'pharmacy' && $action === 'access') {
        $controller = new PharmacyController();
        $controller->access();
        return;
    }

    if ($controllerName === 'pharmacy' && $action === 'form') {
        $controller = new PharmacyController();
        $controller->form();
        return;
    }

    if ($controllerName === 'pharmacy' && $action === 'save') {
        $controller = new PharmacyController();
        $controller->save();
        return;
    }

    if ($controllerName === 'pharmacy' && $action === 'denied') {
        $controller = new PharmacyController();
        $controller->denied();
        return;
    }

    if ($controllerName === 'home' && ($action === null || $action === 'index')) {
        $controller = new HomeController();
        $controller->index();
        return;
    }

    if ($controllerName === 'vehicles' && $action === 'show' && isset($_GET['id'])) {
        $controller = new VehicleController();
        $controller->show((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'postes' && $action === 'list' && isset($_GET['vehicle_id'])) {
        $controller = new PosteController();
        $controller->list((int) $_GET['vehicle_id']);
        return;
    }

    if ($controllerName === 'controles' && $action === 'list' && isset($_GET['vehicle_id'], $_GET['poste_id'])) {
        $controller = new ControleController();
        $controller->list((int) $_GET['vehicle_id'], (int) $_GET['poste_id']);
        return;
    }

    if ($controllerName === 'verifications' && $action === 'store') {
        $controller = new VerificationController();
        $controller->store();
        return;
    }

    if ($controllerName === 'verifications' && $action === 'saved' && isset($_GET['id'])) {
        $controller = new VerificationController();
        $controller->saved((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'verifications' && $action === 'history') {
        $controller = new VerificationController();
        $controller->history();
        return;
    }

    if ($controllerName === 'verifications' && $action === 'monthly') {
        $controller = new VerificationController();
        $controller->monthly();
        return;
    }

    if ($controllerName === 'verifications' && $action === 'show' && isset($_GET['id'])) {
        $controller = new VerificationController();
        $controller->show((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'verifications' && $action === 'export' && isset($_GET['id'])) {
        $controller = new VerificationController();
        $controller->export((int) $_GET['id']);
        return;
    }

    if ($controllerName === 'anomalies' && $action === 'index') {
        $controller = new AnomalyController();
        $controller->index();
        return;
    }

    if ($controllerName === 'anomalies' && $action === 'update') {
        $controller = new AnomalyController();
        $controller->update();
        return;
    }
}

$page = isset($_GET['page']) ? (string) $_GET['page'] : 'home';

if (!$hasFieldAccess && in_array($page, ['home', 'postes', 'controles'], true)) {
    header('Location: /index.php?controller=field&action=denied');
    exit;
}

if ($page === 'vehicle' && isset($_GET['id'])) {
    $controller = new VehicleController();
    $controller->show((int) $_GET['id']);
    return;
}

if ($page === 'postes' && isset($_GET['vehicle_id'])) {
    $controller = new PosteController();
    $controller->list((int) $_GET['vehicle_id']);
    return;
}

if ($page === 'controles' && isset($_GET['vehicle_id'], $_GET['poste_id'])) {
    $controller = new ControleController();
    $controller->list((int) $_GET['vehicle_id'], (int) $_GET['poste_id']);
    return;
}

$controller = new HomeController();
$controller->index();
