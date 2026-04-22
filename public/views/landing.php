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
<body class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#273752_0%,_#0f172a_38%,_#020617_100%)] text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-5 py-8 md:py-12">
        <header class="mb-6 rounded-2xl border border-slate-700/80 bg-slate-950/55 px-5 py-4 backdrop-blur">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="brand-font text-sm uppercase tracking-[0.24em] text-amber-300">VerifApp OSS</span>
                    <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs font-bold text-slate-200">
                        v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <nav class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                    <a href="#fonctionnalites" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Fonctionnalites</a>
                    <a href="#captures" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Captures</a>
                    <a href="#architecture" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Architecture</a>
                    <a href="#roadmap" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Roadmap</a>
                    <a href="/index.php?controller=manager_auth&action=login_form"
                       class="rounded-lg bg-amber-400 px-3 py-2 text-slate-900 hover:bg-amber-300">Espace gestionnaire</a>
                </nav>
            </div>
        </header>

        <section class="relative overflow-hidden rounded-3xl border border-slate-700/80 bg-slate-950/55 p-7 shadow-2xl md:p-10">
            <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-amber-300/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.3fr_1fr] lg:items-center">
                <div>
                    <h1 class="brand-font text-4xl font-bold leading-tight text-white md:text-6xl">
                        Verification materiel
                        <span class="block text-cyan-300">et pharmacie pour casernes</span>
                    </h1>
                    <p class="mt-4 max-w-3xl text-base leading-relaxed text-slate-200 md:text-lg">
                        VerifApp est une application open source, mobile-first et multi-casernes pour piloter
                        les verifications engins, anomalies, sorties pharmacie, inventaires et notifications.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="https://github.com/grandpurs45/VerifApp"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-900 hover:bg-amber-300">
                            Voir le projet GitHub
                        </a>
                        <a href="/index.php?controller=home&action=features"
                           class="inline-flex items-center rounded-xl border border-cyan-500 bg-cyan-950/60 px-5 py-3 text-sm font-bold text-cyan-200 hover:bg-cyan-900/70">
                            Fonctionnalites avancees
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/70 p-4">
                    <div class="rounded-xl border border-slate-700 bg-slate-950/80 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Indicateurs live</p>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-emerald-500/40 bg-emerald-950/40 p-3">
                                <p class="text-xs text-emerald-200">Verifs jour</p>
                                <p class="text-2xl font-extrabold text-emerald-300">24</p>
                            </div>
                            <div class="rounded-lg border border-rose-500/40 bg-rose-950/40 p-3">
                                <p class="text-xs text-rose-200">Anomalies</p>
                                <p class="text-2xl font-extrabold text-rose-300">3</p>
                            </div>
                            <div class="rounded-lg border border-sky-500/40 bg-sky-950/40 p-3">
                                <p class="text-xs text-sky-200">Articles stock</p>
                                <p class="text-2xl font-extrabold text-sky-300">142</p>
                            </div>
                            <div class="rounded-lg border border-amber-500/40 bg-amber-950/40 p-3">
                                <p class="text-xs text-amber-200">Sorties 7j</p>
                                <p class="text-2xl font-extrabold text-amber-300">18</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="fonctionnalites" class="mt-7 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Fonctionnalites principales</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-cyan-300">Terrain</p>
                    <h3 class="mt-2 text-lg font-bold text-white">Verification mobile</h3>
                    <p class="mt-2 text-sm text-slate-300">Saisie tactile, progression visible, zones incompletes surlignees, QR caserne ou QR engin.</p>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-rose-300">Operationnel</p>
                    <h3 class="mt-2 text-lg font-bold text-white">Anomalies tracees</h3>
                    <p class="mt-2 text-sm text-slate-300">Creation auto sur non-conformite, priorites, assignation, suivi et notifications ciblables.</p>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-emerald-300">Pharmacie</p>
                    <h3 class="mt-2 text-lg font-bold text-white">Stock et inventaires</h3>
                    <p class="mt-2 text-sm text-slate-300">Sorties QR, motifs metier, inventaire mobile, acquittement et statistiques de consommation.</p>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/60 p-4">
                    <p class="text-xs uppercase tracking-wider text-violet-300">Gouvernance</p>
                    <h3 class="mt-2 text-lg font-bold text-white">Multi-casernes</h3>
                    <p class="mt-2 text-sm text-slate-300">Roles par caserne, etancheite des donnees, parametres locaux, audit securite et exports.</p>
                </article>
            </div>
        </section>

        <section id="captures" class="mt-7 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Captures produit</h2>
            <p class="mt-2 text-sm text-slate-300">Vue representative des ecrans utilises en caserne et au backoffice.</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <article class="rounded-2xl border border-slate-700 bg-slate-950/70 p-4">
                    <p class="text-xs uppercase tracking-wider text-cyan-300">Mobile terrain</p>
                    <div class="mt-3 rounded-xl border border-slate-700 bg-slate-900 p-3">
                        <div class="mb-3 h-3 w-24 rounded bg-slate-700"></div>
                        <div class="space-y-2">
                            <div class="h-10 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-10 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-10 rounded border border-slate-700 bg-slate-800"></div>
                        </div>
                        <div class="mt-3 h-10 rounded bg-amber-400/90"></div>
                    </div>
                </article>
                <article class="rounded-2xl border border-slate-700 bg-slate-950/70 p-4">
                    <p class="text-xs uppercase tracking-wider text-emerald-300">Backoffice parc</p>
                    <div class="mt-3 rounded-xl border border-slate-700 bg-slate-900 p-3">
                        <div class="mb-2 h-3 w-20 rounded bg-slate-700"></div>
                        <div class="grid gap-2">
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                        </div>
                    </div>
                </article>
                <article class="rounded-2xl border border-slate-700 bg-slate-950/70 p-4">
                    <p class="text-xs uppercase tracking-wider text-violet-300">Pharmacie</p>
                    <div class="mt-3 rounded-xl border border-slate-700 bg-slate-900 p-3">
                        <div class="mb-2 grid grid-cols-3 gap-2">
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                            <div class="h-8 rounded border border-slate-700 bg-slate-800"></div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section id="architecture" class="mt-7 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Architecture technique</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <article class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-bold text-white">Socle applicatif</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>PHP 8.2+ en MVC leger</li>
                        <li>MySQL / MariaDB</li>
                        <li>Routing simple: <code>index.php?controller=X&action=Y</code></li>
                        <li>Deployment local (XAMPP) ou Docker</li>
                    </ul>
                </article>
                <article class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                    <p class="text-sm font-bold text-white">Securite et ops</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-300">
                        <li>Roles et permissions par caserne</li>
                        <li>Audit securite des connexions</li>
                        <li>Backup/restore CLI conf + data</li>
                        <li>Healthcheck enrichi: db/smtp/version/timezone</li>
                    </ul>
                </article>
            </div>
        </section>

        <section id="roadmap" class="mt-7 rounded-3xl border border-slate-700 bg-slate-900/60 p-6">
            <h2 class="brand-font text-2xl font-bold text-white">Roadmap vers v1</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-emerald-500/40 bg-emerald-950/30 p-4">
                    <p class="text-xs uppercase tracking-wider text-emerald-300">Securite</p>
                    <p class="mt-2 text-sm text-slate-200">Etancheite multi-casernes, audit connexion, politique MDP et controle session.</p>
                </div>
                <div class="rounded-xl border border-cyan-500/40 bg-cyan-950/30 p-4">
                    <p class="text-xs uppercase tracking-wider text-cyan-300">Terrain</p>
                    <p class="mt-2 text-sm text-slate-200">UX mobile rapide, QR stabilises et workflows verif/pharmacie/inventaire valides.</p>
                </div>
                <div class="rounded-xl border border-amber-500/40 bg-amber-950/30 p-4">
                    <p class="text-xs uppercase tracking-wider text-amber-300">Operations</p>
                    <p class="mt-2 text-sm text-slate-200">Runbooks, onboarding caserne, migration/rollback documentes et testes.</p>
                </div>
            </div>
        </section>

        <footer class="mt-8 rounded-2xl border border-slate-700/80 bg-slate-950/55 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="brand-font text-sm font-bold text-slate-100">VerifApp</p>
                    <p class="text-sm text-slate-400">Open source pour verification engins, anomalies et pharmacie en caserne.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm font-semibold">
                    <a href="https://github.com/grandpurs45/VerifApp" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">GitHub</a>
                    <a href="https://github.com/grandpurs45/VerifApp#readme" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">README</a>
                    <a href="/index.php?controller=home&action=features" class="rounded-lg border border-slate-600 px-3 py-2 text-slate-200 hover:bg-slate-800">Fonctionnalites avancees</a>
                </div>
            </div>
        </footer>
    </main>
</body>
</html>
