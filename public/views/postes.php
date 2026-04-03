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
            <?php if (empty($fromVehicleQr)): ?>
                <a href="/index.php?controller=home&action=index" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200">
                    <- Retour vehicules
                </a>
                <p class="mt-4 text-xs uppercase tracking-[0.18em] text-amber-300">Etape 2</p>
            <?php endif; ?>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Choisir le poste</h1>
            <?php if ($vehicle !== null): ?>
                <div class="mt-3 rounded-2xl border border-slate-600 bg-slate-900/70 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Engin selectionne</p>
                    <p class="mt-1 text-2xl font-extrabold text-white"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($vehicle === null): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Vehicule introuvable.</p>
            </section>
        <?php elseif ($postes === []): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Aucun poste disponible pour ce vehicule.</p>
            </section>
        <?php else: ?>
            <section class="space-y-3">
                <?php foreach ($postes as $poste): ?>
                    <?php $controleLink = '/index.php?controller=controles&action=list&vehicle_id=' . (int) $vehicle['id'] . '&poste_id=' . (int) $poste['id']; ?>
                    <?php if (!empty($fromVehicleQr)) { $controleLink .= '&from_vehicle_qr=1'; } ?>
                    <a
                        href="<?= htmlspecialchars($controleLink, ENT_QUOTES, 'UTF-8') ?>"
                        class="block rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg active:scale-[0.99] active:bg-slate-700"
                    >
                        <p class="text-xl font-bold text-white"><?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-3 inline-flex rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-200">Commencer verification</p>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
