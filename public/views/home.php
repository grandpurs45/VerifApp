<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
$fieldUser = $_SESSION['field_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerifApp - Terrain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <main class="mx-auto w-full max-w-md px-4 pb-8 pt-5">
        <header class="mb-5 rounded-3xl border border-slate-700/80 bg-slate-800/80 p-4 shadow-lg">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-amber-300">VerifApp Terrain</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white">Etape 1 - Choisir l'engin</h1>
                </div>
                <span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs font-bold text-slate-200">
                    v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <?php if (is_array($fieldUser)): ?>
                <p class="text-sm text-slate-300">
                    Connecte : <span class="font-semibold text-white"><?= htmlspecialchars((string) $fieldUser['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <a href="/index.php?controller=field_auth&action=logout" class="mt-2 inline-flex rounded-lg border border-slate-500 px-3 py-2 text-xs font-semibold text-slate-100">
                    Deconnexion verificateur
                </a>
            <?php else: ?>
                <a href="/index.php?controller=field_auth&action=login_form" class="mt-2 inline-flex rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-200">
                    Connexion verificateur (optionnel)
                </a>
            <?php endif; ?>
        </header>

        <?php if ($vehicles === []): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Aucun vehicule actif trouve.</p>
            </section>
        <?php else: ?>
            <section class="space-y-3">
                <?php foreach ($vehicles as $vehicle): ?>
                    <a
                        href="/index.php?controller=postes&action=list&vehicle_id=<?= (int) $vehicle['id'] ?>"
                        class="block rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg active:scale-[0.99] active:bg-slate-700"
                    >
                        <p class="text-xl font-bold text-white"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-sm font-medium text-slate-300">Type : <?= htmlspecialchars($vehicle['type_vehicule'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-3 inline-flex rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-200">Choisir ce vehicule</p>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <footer class="mt-6 text-center">
            <a href="/index.php?controller=manager_auth&action=login_form" class="text-xs font-semibold text-slate-300 underline">
                Acces gestionnaire
            </a>
        </footer>
    </main>
</body>
</html>
