<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification enregistree - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-6">
        <section class="w-full rounded-3xl border border-slate-700 bg-slate-800/85 p-6 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20 text-3xl text-emerald-300">
                ✓
            </div>

            <?php if ($verification === null): ?>
                <h1 class="text-2xl font-extrabold text-white">Verification enregistree</h1>
                <p class="mt-2 text-slate-300">La session a ete creee.</p>
            <?php else: ?>
                <h1 class="text-2xl font-extrabold text-white">Verification #<?= (int) $verification['id'] ?> enregistree</h1>
                <p class="mt-2 text-slate-300">
                    <?= htmlspecialchars($verification['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($verification['poste_nom'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="mt-1 text-sm font-semibold text-amber-200">
                    Statut : <?= htmlspecialchars((string) $verification['statut_global'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <a href="/index.php?controller=home&action=index" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-amber-300 px-5 py-4 text-base font-extrabold text-slate-900 shadow-lg active:scale-[0.99]">
                Nouvelle verification
            </a>
        </section>
    </main>
</body>
</html>
