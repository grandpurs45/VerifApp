<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard gestionnaire - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-5xl mx-auto p-4 md:p-8">
        <header class="mb-6 flex items-start justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold">Dashboard gestionnaire</h1>
                <p class="text-slate-600 mt-2">
                    Connecte en tant que <?= htmlspecialchars((string) ($managerUser['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                    v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="/index.php?controller=manager_auth&action=logout" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-medium">
                    Deconnexion
                </a>
            </div>
        </header>

        <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] === '1'): ?>
            <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                Mot de passe modifie avec succes.
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Verifs aujourd'hui</p>
                <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Conformes</p>
                <p class="text-2xl font-bold mt-1 text-emerald-700"><?= (int) ($stats['conformes_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Non conformes</p>
                <p class="text-2xl font-bold mt-1 text-red-700"><?= (int) ($stats['non_conformes_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Total verifs</p>
                <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_all'] ?? 0) ?></p>
            </article>
        </section>

        <section class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Anomalies ouvertes</p>
                <p class="text-2xl font-bold mt-1 text-red-700"><?= (int) ($anomalyStats['ouverte'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">En cours</p>
                <p class="text-2xl font-bold mt-1 text-amber-700"><?= (int) ($anomalyStats['en_cours'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Resolues</p>
                <p class="text-2xl font-bold mt-1 text-emerald-700"><?= (int) ($anomalyStats['resolue'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Cloturees</p>
                <p class="text-2xl font-bold mt-1"><?= (int) ($anomalyStats['cloturee'] ?? 0) ?></p>
            </article>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <a href="/index.php?controller=verifications&action=history" class="bg-white rounded-2xl shadow p-4 block hover:bg-slate-50">
                <p class="font-semibold">Historique verifications</p>
                <p class="text-sm text-slate-600 mt-1">Consulter et filtrer les sessions.</p>
            </a>
            <a href="/index.php?controller=anomalies&action=index" class="bg-white rounded-2xl shadow p-4 block hover:bg-slate-50">
                <p class="font-semibold">Suivi anomalies</p>
                <p class="text-sm text-slate-600 mt-1">Traiter les anomalies ouvertes.</p>
            </a>
            <a href="/index.php?controller=manager_assets&action=types" class="bg-white rounded-2xl shadow p-4 block hover:bg-slate-50">
                <p class="font-semibold">Types d engins</p>
                <p class="text-sm text-slate-600 mt-1">Configurer les postes standards par type.</p>
            </a>
            <a href="/index.php?controller=manager_assets&action=vehicles" class="bg-white rounded-2xl shadow p-4 block hover:bg-slate-50">
                <p class="font-semibold">Vehicules</p>
                <p class="text-sm text-slate-600 mt-1">Gerer les engins reels et leurs zones.</p>
            </a>
        </section>
    </main>
</body>
</html>
