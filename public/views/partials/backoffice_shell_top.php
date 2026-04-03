<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
$managerUser = $_SESSION['manager_user'] ?? null;
$managerRole = is_array($managerUser) ? (string) ($managerUser['role'] ?? '') : '';
$managerUserId = is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : 0;
$managerCaserneId = is_array($managerUser) && isset($managerUser['caserne_id']) ? (int) $managerUser['caserne_id'] : 0;
$managerCaserneNom = is_array($managerUser) ? (string) ($managerUser['caserne_nom'] ?? '') : '';
$caserneOptions = [];
if ($managerUserId > 0) {
    $caserneRepository = new \App\Repositories\CaserneRepository();
    $caserneOptions = $caserneRepository->findByUserId($managerUserId);
}
$currentRoute = (string) ($_GET['controller'] ?? '') . '/' . (string) ($_GET['action'] ?? '');

$allModules = [
    [
        'label' => 'Dashboard',
        'route' => '/index.php?controller=manager&action=dashboard',
        'route_key' => 'manager/dashboard',
        'permission' => 'dashboard.view',
    ],
    [
        'label' => 'Anomalies',
        'route' => '/index.php?controller=anomalies&action=index',
        'route_key' => 'anomalies/index',
        'permission' => 'anomalies.manage',
    ],
    [
        'label' => 'Historique',
        'route' => '/index.php?controller=verifications&action=history',
        'route_key' => 'verifications/history',
        'permission' => 'verifications.history',
    ],
    [
        'label' => 'Parc & materiel',
        'route' => '/index.php?controller=manager_assets&action=vehicles',
        'route_key' => 'manager_assets/vehicles',
        'permission' => 'assets.manage',
    ],
    [
        'label' => 'Pharmacie',
        'route' => '/index.php?controller=manager_pharmacy&action=index',
        'route_key' => 'manager_pharmacy/index',
        'permission' => 'pharmacy.manage',
    ],
    [
        'label' => 'Mon compte',
        'route' => '/index.php?controller=manager&action=account',
        'route_key' => 'manager/account',
        'permission' => 'dashboard.view',
    ],
    [
        'label' => 'Administration',
        'route' => '/index.php?controller=manager_admin&action=menu',
        'route_key' => 'manager_admin/menu',
        'permission' => 'users.manage',
    ],
];

$visibleModules = [];
foreach ($allModules as $module) {
    if (\App\Core\ManagerAccess::hasPermission($managerRole, (string) $module['permission'])) {
        $visibleModules[] = $module;
    }
}

$mobileModules = array_slice($visibleModules, 0, 5);
$pageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '' ? $pageTitle : 'Backoffice - VerifApp';
$pageHeading = isset($pageHeading) && is_string($pageHeading) ? $pageHeading : '';
$pageSubtitle = isset($pageSubtitle) && is_string($pageSubtitle) ? $pageSubtitle : '';
$pageBackUrl = isset($pageBackUrl) && is_string($pageBackUrl) ? $pageBackUrl : '';
$pageBackLabel = isset($pageBackLabel) && is_string($pageBackLabel) && $pageBackLabel !== '' ? $pageBackLabel : 'Retour';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <div class="mx-auto max-w-[1400px] px-3 py-3 md:px-4 md:py-4">
        <div class="grid grid-cols-1 lg:grid-cols-[250px_minmax(0,1fr)] gap-4">
            <aside class="hidden lg:flex lg:flex-col rounded-3xl bg-gradient-to-b from-slate-900 to-slate-800 text-white p-4 shadow sticky top-4 h-[calc(100vh-2rem)]">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-300">VerifApp</p>
                <p class="text-xl font-extrabold mt-2">Backoffice</p>
                <p class="text-xs text-slate-300 mt-1">
                    <?= htmlspecialchars((string) ($managerUser['nom'] ?? 'Gestionnaire'), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if ($managerCaserneNom !== ''): ?>
                    <p class="text-xs text-amber-200 mt-1 font-semibold">
                        <?= htmlspecialchars($managerCaserneNom, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                <?php if (count($caserneOptions) > 1): ?>
                    <form method="post" action="/index.php?controller=manager_auth&action=switch_caserne" class="mt-3">
                        <select name="caserne_id" onchange="this.form.submit()" class="w-full rounded-lg border border-white/20 bg-white/10 px-2 py-2 text-xs text-white">
                            <?php foreach ($caserneOptions as $caserne): ?>
                                <option
                                    value="<?= (int) ($caserne['id'] ?? 0) ?>"
                                    <?= $managerCaserneId === (int) ($caserne['id'] ?? 0) ? 'selected' : '' ?>
                                    style="color:#0f172a;background:#ffffff;"
                                >
                                    <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <nav class="mt-5 space-y-2">
                    <?php foreach ($visibleModules as $module): ?>
                        <?php
                        $active = $currentRoute === $module['route_key'];
                        if (!$active && $module['route_key'] === 'verifications/history' && str_starts_with($currentRoute, 'verifications/')) {
                            $active = true;
                        }
                        ?>
                        <a
                            href="<?= htmlspecialchars($module['route'], ENT_QUOTES, 'UTF-8') ?>"
                            class="block rounded-xl px-3 py-2 text-sm font-semibold <?= $active ? 'bg-white text-slate-900' : 'text-slate-100 hover:bg-white/10' ?>"
                        >
                            <?= htmlspecialchars($module['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="mt-auto pt-4">
                    <div class="space-y-2">
                        <span class="inline-flex rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="/index.php?controller=manager&action=account" class="rounded-xl border border-white/30 px-2 py-2 text-center text-xs font-semibold text-white whitespace-nowrap">
                                Compte
                            </a>
                            <a href="/index.php?controller=manager_auth&action=logout" class="rounded-xl bg-white text-slate-900 px-2 py-2 text-center text-xs font-semibold whitespace-nowrap">
                                Deconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 pb-24 lg:pb-0">
                <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-4 md:p-5 shadow">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <?php if ($pageBackUrl !== ''): ?>
                                <a href="<?= htmlspecialchars($pageBackUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-xs text-slate-300 hover:text-white">
                                    <- <?= htmlspecialchars($pageBackLabel, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($pageHeading !== ''): ?>
                                <h1 class="text-2xl md:text-3xl font-extrabold mt-1"><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
                            <?php endif; ?>
                            <?php if ($pageSubtitle !== ''): ?>
                                <p class="text-slate-200 mt-1 text-sm md:text-base"><?= htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 lg:hidden">
                            <span class="inline-flex rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
                            <a href="/index.php?controller=manager&action=account" class="rounded-xl border border-white/30 px-3 py-2 text-xs font-semibold text-white whitespace-nowrap">
                                Compte
                            </a>
                            <a href="/index.php?controller=manager_auth&action=logout" class="rounded-xl bg-white text-slate-900 px-3 py-2 text-xs font-semibold whitespace-nowrap">
                                Deconnexion
                            </a>
                        </div>
                    </div>
                </header>

                <section class="mt-4 space-y-4">
