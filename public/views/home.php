<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerifApp - Terrain</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
    <main class="max-w-3xl mx-auto p-4 md:p-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold">VerifApp Terrain</h1>
            <p class="text-slate-600 mt-2">Selection du vehicule a verifier</p>
        </header>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <?php if ($vehicles === []): ?>
                <p class="text-slate-500">Aucun vehicule actif trouve.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <li class="border border-slate-200 rounded-xl p-4 hover:bg-slate-50 transition">
                            <a href="/index.php?controller=postes&action=list&vehicle_id=<?= (int) $vehicle['id'] ?>" class="block">
                                <div class="font-semibold text-lg">
                                    <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-sm text-slate-500 mt-1">
                                    Type : <?= htmlspecialchars($vehicle['type_vehicule'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <footer class="mt-6 text-center">
            <a href="/index.php?controller=manager_auth&action=login_form" class="text-sm text-slate-500 underline">
                Acces gestionnaire
            </a>
        </footer>
    </main>
</body>
</html>
