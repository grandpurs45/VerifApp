<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail véhicule - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-3xl mx-auto p-4 md:p-8">
        <header class="mb-6">
            <a href="/index.php" class="text-sm text-slate-500 hover:text-slate-700">
                ← Retour à la liste des véhicules
            </a>
            <h1 class="text-3xl font-bold mt-2">Détail du véhicule</h1>
        </header>

        <?php if ($vehicle === null): ?>
            <section class="bg-white rounded-2xl shadow p-6">
                <p class="text-slate-600">Véhicule introuvable.</p>
            </section>
        <?php else: ?>
            <section class="bg-white rounded-2xl shadow p-6 space-y-3">
                <div>
                    <p class="text-sm text-slate-500">Nom</p>
                    <p class="text-xl font-semibold">
                        <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Type</p>
                    <p>
                        <?= htmlspecialchars($vehicle['type_vehicule'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>