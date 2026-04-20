<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fonctionnalites avancees - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
        .brand-font { font-family: "Space Grotesk", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-slate-100">
    <main class="mx-auto w-full max-w-6xl px-5 py-8 md:py-12">
        <header class="mb-5 flex items-center justify-between gap-3">
            <a href="/index.php?controller=home&action=index" class="inline-flex rounded-xl border border-slate-500 bg-slate-800/70 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-700">
                <- Retour accueil
            </a>
            <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs font-bold text-slate-200">
                v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </header>

        <section class="rounded-3xl border border-slate-700/80 bg-slate-900/70 p-7 shadow-2xl md:p-10">
            <p class="brand-font text-xs uppercase tracking-[0.24em] text-amber-300">VerifApp - Fonctionnalites avancees</p>
            <h1 class="brand-font mt-3 text-4xl font-bold leading-tight text-white md:text-5xl">
                Une plateforme operationnelle
                <span class="block text-cyan-300">pensée casernes et terrain</span>
            </h1>
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-slate-200 md:text-lg">
                VerifApp couvre la verification engins, les anomalies, la pharmacie, les inventaires et les notifications
                sur un socle multi-casernes avec permissions fines.
            </p>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2">
            <article class="rounded-2xl border border-slate-700 bg-slate-900/60 p-5">
                <h2 class="text-xl font-bold text-white">Verification terrain</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-300">
                    <li>Checklists mobiles par vehicule, poste et zone</li>
                    <li>Reponses presence / fonctionnel / valeur</li>
                    <li>Brouillons locaux avec reprise sur creneau</li>
                    <li>QR global caserne + QR direct engin</li>
                </ul>
            </article>
            <article class="rounded-2xl border border-slate-700 bg-slate-900/60 p-5">
                <h2 class="text-xl font-bold text-white">Backoffice operatif</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-300">
                    <li>Gestion types, engins, zones arborescentes et materiels</li>
                    <li>Historique, suivi mensuel et exports</li>
                    <li>Anomalies avec suivi de traitement</li>
                    <li>Dashboard par module selon droits utilisateur</li>
                </ul>
            </article>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Captures et exemples d ecrans</h2>
            <p class="mt-2 text-sm text-slate-300">Apercu des grands modules pour une lecture rapide du projet.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-emerald-300">Capture 1</p>
                    <h3 class="mt-1 text-sm font-bold text-white">Dashboard multi-modules</h3>
                    <div class="mt-3 h-36 rounded-lg border border-slate-600 bg-slate-900/80 p-3 text-xs text-slate-300">
                        Vue indicateurs: anomalies, verifications, pharmacie, notifications.
                    </div>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-cyan-300">Capture 2</p>
                    <h3 class="mt-1 text-sm font-bold text-white">Checklist terrain mobile</h3>
                    <div class="mt-3 h-36 rounded-lg border border-slate-600 bg-slate-900/80 p-3 text-xs text-slate-300">
                        Progression, zones, boutons gros doigts, validation rapide.
                    </div>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-amber-300">Capture 3</p>
                    <h3 class="mt-1 text-sm font-bold text-white">Pharmacie: stock & sorties</h3>
                    <div class="mt-3 h-36 rounded-lg border border-slate-600 bg-slate-900/80 p-3 text-xs text-slate-300">
                        Stocks, alertes, sorties QR, inventaires et statistiques 12 mois.
                    </div>
                </article>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Liens projet</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="https://github.com/grandpurs45/VerifApp" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-xl bg-amber-400 px-4 py-2 text-sm font-bold text-slate-900 hover:bg-amber-300">
                    GitHub
                </a>
                <a href="https://github.com/grandpurs45/VerifApp#readme" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-xl border border-slate-500 bg-slate-800 px-4 py-2 text-sm font-bold text-slate-100 hover:bg-slate-700">
                    README
                </a>
                <a href="/index.php?controller=manager_auth&action=login_form" class="inline-flex rounded-xl border border-slate-500 bg-slate-800 px-4 py-2 text-sm font-bold text-slate-100 hover:bg-slate-700">
                    Espace gestionnaire
                </a>
            </div>
        </section>
    </main>
</body>
</html>
