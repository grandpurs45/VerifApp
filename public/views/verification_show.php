<?php

declare(strict_types=1);

$linesByZone = [];
foreach ($lines as $line) {
    $zone = $line['zone'];
    $linesByZone[$zone][] = $line;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-4xl mx-auto p-4 md:p-8">
        <header class="mb-6 flex items-start justify-between gap-3">
            <div>
                <a href="/index.php?controller=verifications&action=history" class="text-sm text-slate-500 hover:text-slate-700">
                    <- Retour historique
                </a>
                <h1 class="text-3xl font-bold mt-2">Detail verification</h1>
            </div>
            <div class="flex flex-col gap-2">
                <?php if ($verification !== null): ?>
                    <a href="/index.php?controller=verifications&action=export&id=<?= (int) $verification['id'] ?>" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-medium text-center">
                        Export PDF
                    </a>
                <?php endif; ?>
                <a href="/index.php?controller=anomalies&action=index" class="rounded-lg bg-red-600 text-white px-4 py-2 text-sm font-medium text-center">
                    Voir anomalies
                </a>
            </div>
        </header>

        <?php if ($verification === null): ?>
            <section class="bg-white rounded-2xl shadow p-6">
                <p class="text-slate-500">Verification introuvable.</p>
            </section>
        <?php else: ?>
            <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
                <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
                    Verification enregistree avec succes.
                </section>
            <?php endif; ?>

            <section class="bg-white rounded-2xl shadow p-4 md:p-6 mb-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Vehicule</dt>
                        <dd class="font-semibold"><?= htmlspecialchars($verification['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Poste</dt>
                        <dd class="font-semibold"><?= htmlspecialchars($verification['poste_nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Date</dt>
                        <dd class="font-semibold"><?= htmlspecialchars((string) $verification['date_heure'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Verificateur</dt>
                        <dd class="font-semibold"><?= htmlspecialchars($verification['agent'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Statut global</dt>
                        <dd class="font-semibold"><?= htmlspecialchars($verification['statut_global'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>

                <?php if (($verification['commentaire_global'] ?? null) !== null && trim((string) $verification['commentaire_global']) !== ''): ?>
                    <div class="mt-4">
                        <p class="text-sm text-slate-500">Commentaire global</p>
                        <p class="text-sm"><?= nl2br(htmlspecialchars((string) $verification['commentaire_global'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <?php foreach ($linesByZone as $zone => $zoneLines): ?>
                <section class="bg-white rounded-2xl shadow p-4 md:p-6 mb-4">
                    <h2 class="text-xl font-semibold mb-3"><?= htmlspecialchars($zone, ENT_QUOTES, 'UTF-8') ?></h2>

                    <ul class="space-y-3">
                        <?php foreach ($zoneLines as $line): ?>
                            <?php
                            $result = strtolower((string) $line['resultat']);
                            $resultClass = 'bg-slate-100 text-slate-700';
                            if ($result === 'ok') {
                                $resultClass = 'bg-emerald-100 text-emerald-700';
                            } elseif ($result === 'nok') {
                                $resultClass = 'bg-red-100 text-red-700';
                            }
                            ?>
                            <li class="border border-slate-200 rounded-xl p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-medium"><?= htmlspecialchars($line['libelle'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $resultClass ?>">
                                        <?= strtoupper(htmlspecialchars($result, ENT_QUOTES, 'UTF-8')) ?>
                                    </span>
                                </div>

                                <?php if (($line['commentaire'] ?? null) !== null && trim((string) $line['commentaire']) !== ''): ?>
                                    <p class="text-sm text-slate-600 mt-2">
                                        Commentaire : <?= nl2br(htmlspecialchars((string) $line['commentaire'], ENT_QUOTES, 'UTF-8')) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (($line['anomalie_id'] ?? null) !== null): ?>
                                    <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm">
                                        <p class="font-semibold text-red-700">
                                            Anomalie #<?= (int) $line['anomalie_id'] ?> - <?= htmlspecialchars((string) $line['anomalie_statut'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <?php if (($line['anomalie_commentaire'] ?? null) !== null && trim((string) $line['anomalie_commentaire']) !== ''): ?>
                                            <p class="text-red-700 mt-1"><?= nl2br(htmlspecialchars((string) $line['anomalie_commentaire'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
