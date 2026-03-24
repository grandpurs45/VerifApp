<?php

declare(strict_types=1);

use App\Controllers\AnomalyController;
use App\Controllers\AuthController;
use App\Controllers\ControleController;
use App\Controllers\FieldAuthController;
use App\Controllers\FieldController;
use App\Controllers\HomeController;
use App\Controllers\ManagerAssetController;
use App\Controllers\ManagerController;
use App\Controllers\PosteController;
use App\Controllers\VehicleController;
use App\Controllers\VerificationController;
use App\Core\Autoloader;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();
Env::load(dirname(__DIR__) . '/.env');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$controllerName = isset($_GET['controller']) ? (string) $_GET['controller'] : null;
$action = isset($_GET['action']) ? (string) $_GET['action'] : null;

$isManagerAuthenticated = isset($_SESSION['manager_user']) && is_array($_SESSION['manager_user']);
$isFieldAuthenticated = isset($_SESSION['field_user']) && is_array($_SESSION['field_user']);
$fieldToken = (string) (Env::get('FIELD_QR_TOKEN', '') ?? '');
$hasFieldAccess = $fieldToken === '' || (isset($_SESSION['field_access']) && $_SESSION['field_access'] === true);

$managerRoutes = [
    'manager/dashboard',
    'verifications/history',
    'verifications/show',
    'verifications/export',
    'anomalies/index',
    'anomalies/update',
    'manager_assets/index',
    'manager_assets/vehicle_save',
    'manager_assets/vehicle_delete',
    'manager_assets/zone_save',
    'manager_assets/zone_delete',
    'manager_assets/poste_save',
    'manager_assets/poste_delete',
    'manager_assets/controle_save',
    'manager_assets/controle_delete',
];

$fieldRoutes = [
    'home/index',
    'postes/list',
    'controles/list',
    'verifications/store',
    'verifications/saved',
];

$routeKey = ($controllerName ?? '') . '/' . ($action ?? '');

if (in_array($routeKey, $managerRoutes, true) && !$isManagerAuthenticated) {
    header('Location: /index.php?controller=manager_auth&action=login_form');
    exit;
}

if (in_array($routeKey, $fieldRoutes, true) && !$hasFieldAccess) {
    header('Location: /index.php?controller=field&action=denied');
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

    if ($controllerName === 'manager_auth' && $action === 'logout') {
        $controller = new AuthController();
        $controller->logout();
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

    if ($controllerName === 'manager_assets' && $action === 'index') {
        $controller = new ManagerAssetController();
        $controller->index();
        return;
    }

    if ($controllerName === 'manager_assets' && $action === 'vehicle_save') {
        $controller = new ManagerAssetController();
        $controller->vehicleSave();
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
