<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerifApp</title>
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
        <header class="mb-5 flex items-center justify-end">
            <a href="/index.php?controller=manager_auth&action=login_form"
               class="inline-flex rounded-xl border border-slate-500 bg-slate-800/70 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-700">
                Espace gestionnaire
            </a>
        </header>
        <section class="relative overflow-hidden rounded-3xl border border-slate-700/80 bg-slate-900/70 p-7 shadow-2xl md:p-10">
            <div class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full bg-amber-300/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="relative">
                <div class="mb-6 flex items-center justify-between gap-3">
                    <p class="brand-font text-xs uppercase tracking-[0.24em] text-amber-300">VerifApp Open Source</p>
                    <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs font-bold text-slate-200">
                        v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <h1 class="brand-font text-4xl font-bold leading-tight text-white md:text-5xl">
                    Verification materiel & pharmacie
                    <span class="block text-amber-300">pour casernes multi-sites</span>
                </h1>
                <p class="mt-4 max-w-3xl text-base leading-relaxed text-slate-200 md:text-lg">
                    VerifApp est une application web mobile-first pour la verification quotidienne des engins,
                    le suivi des anomalies, la gestion des stocks pharmacie et les inventaires terrain via QR code.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="https://github.com/grandpurs45/VerifApp"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-900 hover:bg-amber-300">
                        Voir le projet sur GitHub
                    </a>
                    <a href="https://github.com/grandpurs45/VerifApp#readme"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center rounded-xl border border-slate-500 bg-slate-800 px-5 py-3 text-sm font-bold text-slate-100 hover:bg-slate-700">
                        Documentation
                    </a>
                    <a href="/index.php?controller=home&action=features"
                       class="inline-flex items-center rounded-xl border border-cyan-500 bg-cyan-950/60 px-5 py-3 text-sm font-bold text-cyan-200 hover:bg-cyan-900/70">
                        Fonctionnalites avancees
                    </a>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-700 bg-slate-900/60 p-5">
                <p class="text-xs uppercase tracking-wider text-cyan-300">Terrain</p>
                <h2 class="mt-2 text-xl font-bold text-white">Verification mobile</h2>
                <p class="mt-2 text-sm text-slate-300">Checklist tactile, brouillons locaux, saisie rapide et generation automatique d anomalies.</p>
            </article>
            <article class="rounded-2xl border border-slate-700 bg-slate-900/60 p-5">
                <p class="text-xs uppercase tracking-wider text-emerald-300">Pharmacie</p>
                <h2 class="mt-2 text-xl font-bold text-white">Stock + sorties</h2>
                <p class="mt-2 text-sm text-slate-300">Sorties via QR, acquittement, inventaires, receptions commandes et statistiques de consommation.</p>
            </article>
            <article class="rounded-2xl border border-slate-700 bg-slate-900/60 p-5">
                <p class="text-xs uppercase tracking-wider text-violet-300">Gouvernance</p>
                <h2 class="mt-2 text-xl font-bold text-white">Multi-casernes</h2>
                <p class="mt-2 text-sm text-slate-300">Scopes par caserne, roles, notifications, tableaux de bord dynamiques et parametres locaux.</p>
            </article>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Ce que couvre VerifApp aujourd hui</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-amber-300">Verification engins</h3>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Arborescence zones / sous-zones par vehicule</li>
                        <li>Types de reponse: presence, fonctionnel, valeur</li>
                        <li>Historique detaille et export des verifications</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-amber-300">Module pharmacie</h3>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Gestion de stock et alertes de seuil</li>
                        <li>Sorties structurees avec motifs metier</li>
                        <li>Inventaires terrain + application des ecarts</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Securite d acces</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-white">Acces terrain QR</p>
                    <p class="mt-1 text-sm text-slate-300">Les modules terrain restent derives de QR officiels par caserne.</p>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-white">Auth gestionnaire</p>
                    <p class="mt-1 text-sm text-slate-300">Comptes nominatifs, permissions par role et session protegee.</p>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-white">Socle durci</p>
                    <p class="mt-1 text-sm text-slate-300">Politique mot de passe forte et limitation des tentatives de connexion.</p>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Roadmap v1 (cap)</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-white">Socle produit</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Hardening auth et roles multi-casernes</li>
                        <li>Notifications in-app + email configurables</li>
                        <li>Historique, exports et indicateurs de pilotage</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-white">Usage terrain</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>UX smartphone gros doigts / forte lisibilite</li>
                        <li>Saisie rapide avec brouillons et reprise</li>
                        <li>QR codes operationnels par caserne et engin</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
