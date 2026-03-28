<?php

declare(strict_types=1);

$pageTitle = 'Administration - VerifApp';
$pageHeading = 'Administration';
$pageSubtitle = 'Menu central de configuration et gouvernance des acces.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="/index.php?controller=manager_roles&action=index" class="rounded-2xl bg-white shadow p-5 hover:bg-slate-50">
        <p class="text-lg font-bold">Roles et acces</p>
        <p class="text-sm text-slate-600 mt-1">Creer des roles et choisir les fonctionnalites autorisees.</p>
    </a>
    <a href="/index.php?controller=manager_users&action=index" class="rounded-2xl bg-white shadow p-5 hover:bg-slate-50">
        <p class="text-lg font-bold">Utilisateurs</p>
        <p class="text-sm text-slate-600 mt-1">Creer et gerer les comptes, roles et activations.</p>
    </a>
    <a href="/index.php?controller=manager_assets&action=types" class="rounded-2xl bg-white shadow p-5 hover:bg-slate-50">
        <p class="text-lg font-bold">Types et postes</p>
        <p class="text-sm text-slate-600 mt-1">Standardiser les postes selon les types d engins.</p>
    </a>
    <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-2xl bg-white shadow p-5 hover:bg-slate-50">
        <p class="text-lg font-bold">Vehicules, zones, materiel</p>
        <p class="text-sm text-slate-600 mt-1">Configurer le parc et la checklist operationnelle.</p>
    </a>
    <a href="/index.php?controller=manager_pharmacy&action=index" class="rounded-2xl bg-white shadow p-5 hover:bg-slate-50">
        <p class="text-lg font-bold">Module pharmacie</p>
        <p class="text-sm text-slate-600 mt-1">Gerer stocks et sorties medicaux.</p>
    </a>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
