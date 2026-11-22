<?php
$weekdayLabels = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
$leadingBlanks = (int)date('N',strtotime($monthValue.'-01')) - 1;
?>
<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-bold text-slate-900">Couverture quotidienne</h2>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> 100%</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Partielle</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-slate-700"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Non commencee</span>
        </div>
    </div>

    <div class="overflow-x-auto" role="region" aria-label="Calendrier de couverture" tabindex="0">
    <div class="grid min-w-[560px] grid-cols-7 gap-2">
        <?php foreach ($weekdayLabels as $label): ?>
            <div class="rounded-lg bg-slate-100 px-2 py-1 text-center text-xs font-bold uppercase tracking-wide text-slate-600"><?= $label ?></div>
        <?php endforeach; ?>

        <?php for ($blank = 0; $blank < $leadingBlanks; $blank++): ?>
            <div class="min-h-[92px] rounded-xl border border-dashed border-slate-200 bg-slate-50/50"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
            <?php
            $dateKey = sprintf('%04d-%02d-%02d', (int) substr($monthValue, 0, 4), (int) substr($monthValue, 5, 2), $day);
            $dayData = $dailyCoverage[$dateKey] ?? [];
            $verifiedPostes = (int) ($dayData['postes_verifies_count'] ?? 0);
            $expectedSlots = (int) ($dayData['postes_attendus'] ?? $expectedPostesPerDay);
            $dayRate = (int) ($dayData['pourcentage'] ?? 0);
            $dayComplete = (bool) ($dayData['complet'] ?? false);
            $dayEligible = (bool) ($dayData['eligible'] ?? false);
            $dayNonConformes = (int) ($dayData['non_conformes'] ?? 0);
            $morningCovered = (int) ($dayData['matin_couverts'] ?? 0);
            $eveningCovered = (int) ($dayData['soir_couverts'] ?? 0);
            $dailyCovered = (int) ($dayData['jour_couverts'] ?? 0);
            $dayStarted = $verifiedPostes > 0;
            $dayClass = !$dayEligible
                ? 'border-slate-200 bg-slate-50 text-slate-400'
                : ($dayComplete
                    ? 'border-emerald-200 bg-emerald-50'
                    : ($dayStarted ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'));
            ?>
            <article
                class="min-h-[104px] rounded-xl border p-2 <?= $dayClass ?>"
                title="<?= $dayEligible ? $verifiedPostes . ' creneau(x) couvert(s) sur ' . $expectedSlots : 'Journee a venir' ?>"
            >
                <div class="flex items-center justify-between gap-1">
                    <p class="text-sm font-extrabold <?= $dayEligible ? 'text-slate-900' : 'text-slate-400' ?>"><?= $day ?></p>
                    <?php if ($dayEligible): ?>
                        <span class="text-[10px] font-extrabold <?= $dayComplete ? 'text-emerald-700' : ($dayStarted ? 'text-amber-700' : 'text-slate-500') ?>">
                            <?= $dayRate ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <?php if ($dayEligible): ?>
                        <p class="text-center text-xs font-bold text-slate-700"><?= $verifiedPostes ?> / <?= $expectedSlots ?></p>
                        <?php if ($morningCovered + $eveningCovered > 0): ?>
                            <p class="mt-1 text-center text-[10px] text-slate-500">M <?= $morningCovered ?> / S <?= $eveningCovered ?><?= $dailyCovered > 0 ? ' / J ' . $dailyCovered : '' ?></p>
                        <?php elseif ($dailyCovered > 0): ?>
                            <p class="mt-1 text-center text-[10px] text-slate-500">Jour <?= $dailyCovered ?></p>
                        <?php endif; ?>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                            <div
                                class="h-full rounded-full <?= $dayComplete ? 'bg-emerald-500' : ($dayStarted ? 'bg-amber-500' : 'bg-slate-400') ?>"
                                style="width: <?= $dayRate ?>%"
                            ></div>
                        </div>
                        <?php if ($dayNonConformes > 0): ?>
                            <p class="mt-2 text-center text-[10px] font-bold text-red-700"><?= $dayNonConformes ?> NOK</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mt-4 text-center text-[10px] font-semibold uppercase text-slate-400">A venir</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endfor; ?>
    </div>
    </div>
</section>
