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
                <span class="block text-cyan-300">pensee pour casernes et terrain</span>
            </h1>
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-slate-200 md:text-lg">
                Vue detaillee des modules metier, capacites de securite, exploitation et gouvernance multi-casernes.
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
            <h2 class="brand-font text-2xl font-bold text-white">Points forts techniques</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-cyan-300">Securite et robustesse</h3>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Rate-limit login et verrou temporaire</li>
                        <li>Audit securite connexions + exports CSV</li>
                        <li>Mode debug global controlable en BO</li>
                        <li>Gestion d erreur standardisee avec code incident</li>
                    </ul>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-emerald-300">Exploitation</h3>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Healthcheck complet: DB, SMTP, timezone, version</li>
                        <li>Backup/restore CLI complet (data + conf)</li>
                        <li>Guide rollback, runbook incident, onboarding caserne</li>
                        <li>Compatibilite Docker et installation locale XAMPP</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Roadmap court terme</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-xs uppercase tracking-wider text-violet-300">v1.0</p>
                    <p class="mt-2 text-sm text-slate-200">Stabilite prod, securite, docs complete et deployment reproductible.</p>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-xs uppercase tracking-wider text-violet-300">v1.1</p>
                    <p class="mt-2 text-sm text-slate-200">Status page BO, audit usage avance, ameliorations UX terrain.</p>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-xs uppercase tracking-wider text-violet-300">v1.2+</p>
                    <p class="mt-2 text-sm text-slate-200">Comparaison referentiel SDIS, modules et reporting operationnel etendu.</p>
                </div>
            </div>
        </section>

        <footer class="mt-6 rounded-2xl border border-slate-700/80 bg-slate-950/55 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-400">VerifApp - projet open source pour casernes (verifications, anomalies, pharmacie).</p>
                <div class="flex flex-wrap gap-2 text-sm font-semibold">
                    <a href="https://github.com/grandpurs45/VerifApp" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">GitHub</a>
                    <a href="https://github.com/grandpurs45/VerifApp#readme" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">README</a>
                    <a href="/index.php?controller=manager_auth&action=login_form" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Espace gestionnaire</a>
                </div>
            </div>
        </footer>
    </main>
</body>
</html>
