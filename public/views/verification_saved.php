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
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <section class="bg-white rounded-2xl shadow p-6 text-center">
            <?php if ($verification === null): ?>
                <h1 class="text-2xl font-bold">Verification enregistree</h1>
                <p class="text-slate-600 mt-2">La session a ete creee.</p>
            <?php else: ?>
                <h1 class="text-2xl font-bold">Verification #<?= (int) $verification['id'] ?> enregistree</h1>
                <p class="text-slate-600 mt-2">
                    <?= htmlspecialchars($verification['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($verification['poste_nom'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="text-slate-500 text-sm mt-1">
                    Statut : <?= htmlspecialchars((string) $verification['statut_global'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <a href="/index.php?controller=home&action=index" class="inline-flex mt-5 rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
                Nouvelle verification
            </a>
        </section>
    </main>
</body>
</html>
