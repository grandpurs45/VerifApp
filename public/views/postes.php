<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postes - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-3xl mx-auto p-4 md:p-8">
        <header class="mb-6">
            <a href="/index.php?controller=home&action=index" class="text-sm text-slate-500 hover:text-slate-700">
                <- Retour a la liste des vehicules
            </a>

            <h1 class="text-3xl font-bold mt-2">Choisir un poste</h1>

            <?php if ($vehicle !== null): ?>
                <p class="text-slate-600 mt-2">
                    Vehicule : <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
        </header>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <?php if ($vehicle === null): ?>
                <p class="text-slate-500">Vehicule introuvable.</p>
            <?php elseif ($postes === []): ?>
                <p class="text-slate-500">Aucun poste disponible pour ce vehicule.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($postes as $poste): ?>
                        <li class="border border-slate-200 rounded-xl p-4 hover:bg-slate-50 transition">
                            <a href="/index.php?controller=controles&action=list&vehicle_id=<?= (int) $vehicle['id'] ?>&poste_id=<?= (int) $poste['id'] ?>" class="block">
                                <div class="font-semibold text-lg">
                                    <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-sm text-slate-500 mt-1">
                                    Code : <?= htmlspecialchars($poste['code'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
