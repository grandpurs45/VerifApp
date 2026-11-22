<section class="bg-white px-3 py-4 sm:px-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-bold">Taux de couverture mensuel</h2>
        <span class="text-xs text-slate-500">Janvier - decembre &middot; 0 a 100 %</span>
    </div>
    <?php if ($expectedPostes === []): ?><p class="mt-3 text-sm text-amber-800" role="status">Aucun poste verifiable dans ce perimetre.</p><?php endif; ?>
    <?php if ($primarySummary['verifications'] === 0): ?><p class="mt-3 text-sm text-slate-500" role="status">Aucune verification enregistree en <?= $selectedYear ?> dans ce perimetre.</p><?php endif; ?>
    <div class="relative mt-3 h-[320px] min-w-0 w-full sm:h-[380px]">
        <canvas id="coverage-year-chart" role="img" aria-label="Taux de couverture par mois pour <?= implode(' et ',array_keys($yearSummaries)) ?>. Donnees disponibles dans le tableau ci-dessous."></canvas>
    </div>
    <p id="coverage-chart-error" class="mt-2 hidden text-sm text-amber-800" role="status">Graphique indisponible. Les donnees restent accessibles dans le tableau.</p>
    <p class="mt-3 text-xs leading-relaxed text-slate-500">Objectifs bases sur la configuration actuelle des vehicules. Le mois en cours est calcule jusqu au <?= $today->format('d/m/Y') ?>; les mois futurs sont exclus. Une garde de 24 h couvre matin et soir en mode deux creneaux.</p>
</section>
<section class="bg-white px-3 py-4 sm:px-5">
    <h2 class="text-lg font-bold">Detail mensuel</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full <?= $comparisonYear > 0 ? 'min-w-[680px]' : 'min-w-[440px]' ?> text-sm">
            <thead class="border-b border-slate-300 text-left text-slate-500"><tr><th class="px-3 py-3 font-semibold">Mois</th>
                <?php foreach ($yearSummaries as $year => $summary): ?><th class="px-3 py-3 font-semibold">Couverture <?= $year ?></th><th class="px-3 py-3 font-semibold">Verifications <?= $year ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody>
                <?php for ($month = 1; $month <= 12; $month++): ?><tr class="border-b border-slate-100">
                    <th scope="row" class="px-3 py-3 text-left font-semibold"><?= $monthNames[$month-1] ?></th>
                    <?php foreach ($yearSummaries as $year => $summary): ?>
                        <?php $data=$summary['months'][$month]; $href=$chartDatasets[array_search($year,array_keys($yearSummaries),true)]['links'][$month-1]; ?>
                        <td class="px-3 py-3">
                            <?php if ($data['rate'] === null): ?><span class="text-slate-400"><?= $data['eligibleDays'] === 0 ? 'A venir' : 'Sans objectif' ?></span>
                            <?php else: ?><a href="<?= htmlspecialchars($href,ENT_QUOTES,'UTF-8') ?>" class="font-semibold text-sky-800 underline decoration-sky-200 underline-offset-4"><?= $data['rate'] ?>%</a><span class="ml-2 whitespace-nowrap text-xs text-slate-500"><?= $data['covered'] ?> / <?= $data['expected'] ?></span><?php if ($data['eligibleDays'] < $data['daysInMonth']): ?><span class="ml-2 text-xs text-amber-700">En cours</span><?php endif; ?><?php endif; ?>
                        </td><td class="px-3 py-3 tabular-nums"><?= $data['eligibleDays'] > 0 ? $data['verifications'] : '-' ?></td>
                    <?php endforeach; ?>
                </tr><?php endfor; ?>
            </tbody>
        </table>
    </div>
</section>
<script type="application/json" id="coverage-year-data"><?= json_encode($chartData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="/assets/chart.umd.min.js?v=<?= hash_file('sha256',__DIR__.'/../../assets/chart.umd.min.js') ?>" defer></script>
<script src="/assets/verification-yearly.js?v=<?= hash_file('sha256',__DIR__.'/../../assets/verification-yearly.js') ?>" defer></script>
