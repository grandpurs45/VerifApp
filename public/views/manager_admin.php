<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-5 md:p-6 shadow">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <a href="/index.php?controller=manager&action=dashboard" class="text-xs text-slate-300 hover:text-white"><- Retour dashboard</a>
                    <h1 class="text-3xl font-extrabold mt-2">Administration</h1>
                    <p class="text-slate-200 mt-1">Menu central de configuration et gouvernance des acces.</p>
                </div>
                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

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
                <p class="text-sm text-slate-600 mt-1">Standardiser les postes selon les types d'engins.</p>
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
    </main>
</body>
</html>
