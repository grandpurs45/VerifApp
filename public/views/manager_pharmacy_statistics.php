<?php

declare(strict_types=1);

$pageTitle = 'Statistiques pharmacie - VerifApp';
$pageHeading = 'Statistiques pharmacie';
$pageSubtitle = 'Tendances de consommation sur 12 mois glissants.';
$pageBackUrl = '/index.php?controller=manager_pharmacy&action=index';
$pageBackLabel = 'Retour pharmacie';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (!$isAvailable): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Module non initialise. Lance la migration <code>016_create_pharmacy_module.sql</code>.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-xl font-bold">Consommation 12 mois</h2>
            <p class="text-sm text-slate-600 mt-1">Vue annuelle des sorties pour reperer les pics de consommation.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total 12 mois</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1"><?= (int) ($consumptionTotal12m ?? 0) ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Moyenne / mois</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1"><?= (int) ($consumptionAveragePerMonth ?? 0) ?></p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <p class="text-xs uppercase tracking-wide text-amber-700">Mois de pic</p>
            <p class="text-xl font-extrabold text-amber-800 mt-1">
                <?= htmlspecialchars((string) ($consumptionPeakLabel ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                <span class="text-sm font-semibold">(<?= (int) ($consumptionMax ?? 0) ?>)</span>
            </p>
        </article>
    </div>

    <?php
    $maxChartValue = max(1, (int) ($consumptionMax ?? 0));
    $chartData = is_array($monthlyConsumption ?? null) ? $monthlyConsumption : [];
    ?>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="space-y-2">
            <?php foreach ($chartData as $point): ?>
                <?php
                $qty = (int) ($point['total_quantite'] ?? 0);
                $widthPct = (int) round(($qty / $maxChartValue) * 100);
                $displayWidth = $qty > 0 ? max(2, $widthPct) : 0;
                ?>
                <div class="grid grid-cols-[72px_1fr_52px] items-center gap-2">
                    <span class="text-xs font-semibold text-slate-600"><?= htmlspecialchars((string) ($point['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="h-5 w-full rounded-full bg-slate-200">
                        <div
                            class="h-5 rounded-full <?= $qty > 0 ? 'bg-sky-500' : 'bg-slate-300' ?>"
                            style="width: <?= $displayWidth ?>%;"
                            title="<?= (int) ($point['lignes'] ?? 0) ?> ligne(s)"
                        ></div>
                    </div>
                    <span class="text-right text-xs font-bold text-slate-700"><?= $qty ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <h3 class="text-sm font-bold text-slate-900">Top articles consommes (12 mois)</h3>
        <?php if (($topConsumedArticles ?? []) === []): ?>
            <p class="mt-2 text-sm text-slate-500">Aucune sortie enregistree sur la periode.</p>
        <?php else: ?>
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3">Article</th>
                            <th class="py-2 pr-3">Quantite</th>
                            <th class="py-2 pr-3">Lignes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topConsumedArticles as $line): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) ($line['article_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 pr-3">
                                    <?= (int) round((float) ($line['total_quantite'] ?? 0)) ?>
                                    <?= htmlspecialchars((string) ($line['article_unite'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-2 pr-3"><?= (int) ($line['lignes'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
