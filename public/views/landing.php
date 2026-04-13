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
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <main class="mx-auto w-full max-w-5xl px-5 py-8 md:py-12">
        <section class="rounded-3xl border border-slate-700/80 bg-slate-800/80 p-7 shadow-xl md:p-10">
            <div class="mb-5 flex items-center justify-between gap-3">
                <p class="text-xs uppercase tracking-[0.2em] text-amber-300">VerifApp</p>
                <span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs font-bold text-slate-200">
                    v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <h1 class="text-3xl font-extrabold text-white md:text-4xl">Verification materiel pompiers</h1>
            <p class="mt-3 max-w-2xl text-base text-slate-200">
                Application de verification engins et de gestion pharmacie pour casernes multi-sites.
                Les acces terrain se font uniquement via QR code officiel.
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="https://github.com/grandpurs45/VerifApp"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-900 hover:bg-amber-300">
                    Voir le projet GitHub
                </a>
            </div>
        </section>

        <section class="mt-5 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-700 bg-slate-800/70 p-5">
                <h2 class="text-lg font-bold text-white">Verification terrain</h2>
                <p class="mt-2 text-sm text-slate-300">Checklist mobile, saisie rapide et suivi des anomalies.</p>
            </article>
            <article class="rounded-2xl border border-slate-700 bg-slate-800/70 p-5">
                <h2 class="text-lg font-bold text-white">Pharmacie</h2>
                <p class="mt-2 text-sm text-slate-300">Gestion stock, sorties QR, inventaires et historisation.</p>
            </article>
            <article class="rounded-2xl border border-slate-700 bg-slate-800/70 p-5">
                <h2 class="text-lg font-bold text-white">Multi-casernes</h2>
                <p class="mt-2 text-sm text-slate-300">Parametrage par caserne et droits utilisateurs adaptes.</p>
            </article>
        </section>

        <section class="mt-5 rounded-3xl border border-slate-700 bg-slate-800/70 p-6">
            <h2 class="text-lg font-bold text-white">Acces et securite</h2>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-300">
                <li>Les modules terrain s'ouvrent uniquement via QR code officiel.</li>
                <li>Les liens operationnels ne sont pas exposes publiquement.</li>
                <li>L'administration se fait sur un espace dedie avec authentification.</li>
            </ul>
        </section>
    </main>
</body>
</html>
