<?php

declare(strict_types=1);

$pageTitle = 'Vue mensuelle - VerifApp';
$pageHeading = 'Verifications mensuelles';
$pageSubtitle = 'Lecture rapide matin/soir avec indicateurs globaux.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

$weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$firstDayTimestamp = strtotime($monthValue . '-01');
$firstWeekdayIso = (int) date('N', $firstDayTimestamp); // 1=lundi ... 7=dimanche
$leadingBlanks = $firstWeekdayIso - 1;

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <form method="get" action="/index.php" class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <input type="hidden" name="controller" value="verifications">
        <input type="hidden" name="action" value="monthly">

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Mois</label>
            <input type="month" name="month" value="<?= htmlspecialchars($monthValue, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Vehicule</label>
            <select name="vehicule_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="0">Tous les vehicules</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= (int) $vehicle['id'] ?>" <?= $selectedVehicleId === (int) $vehicle['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-2 flex items-end gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Afficher</button>
            <a href="/index.php?controller=verifications&action=history" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Vue liste</a>
        </div>
    </form>
</section>

<section class="grid grid-cols-2 gap-3 md:grid-cols-5">
    <article class="rounded-2xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Periode</p>
        <p class="mt-2 text-lg font-extrabold text-slate-900"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total verifs</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $totals['total_verifs'] ?></p>
    </article>
    <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Creneaux couverts</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $totals['slots_couverts'] ?> / <?= (int) $totalSlots ?></p>
    </article>
    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Couverture</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $coverageRate ?>%</p>
    </article>
    <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm col-span-2 md:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Conformite</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $conformityRate ?>%</p>
    </article>
</section>

<section class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
    Decoupage applique: <strong>matin &lt; <?= (int) $eveningStartHour ?>h00</strong> et <strong>soir >= <?= (int) $eveningStartHour ?>h00</strong>.
</section>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Calendrier matin / soir</h2>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Creneau couvre</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-slate-700"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Pas de verification</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-1 text-red-700"><span class="h-2 w-2 rounded-full bg-red-500"></span> Non conforme presente</span>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-2">
        <?php foreach ($weekdayLabels as $label): ?>
            <div class="rounded-lg bg-slate-100 px-2 py-1 text-center text-xs font-bold uppercase tracking-wide text-slate-600"><?= $label ?></div>
        <?php endforeach; ?>

        <?php for ($blank = 0; $blank < $leadingBlanks; $blank++): ?>
            <div class="min-h-[92px] rounded-xl border border-dashed border-slate-200 bg-slate-50/50"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
            <?php
            $dateKey = sprintf('%04d-%02d-%02d', (int) substr($monthValue, 0, 4), (int) substr($monthValue, 5, 2), $day);
            $daySlots = $slotsByDay[$dateKey] ?? ['matin' => ['total' => 0, 'non_conformes' => 0], 'soir' => ['total' => 0, 'non_conformes' => 0]];
            $matinTotal = (int) ($daySlots['matin']['total'] ?? 0);
            $soirTotal = (int) ($daySlots['soir']['total'] ?? 0);
            $matinNok = (int) ($daySlots['matin']['non_conformes'] ?? 0);
            $soirNok = (int) ($daySlots['soir']['non_conformes'] ?? 0);
            $dayDone = $matinTotal > 0 && $soirTotal > 0;
            ?>
            <article class="min-h-[92px] rounded-xl border p-2 <?= $dayDone ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' ?>">
                <p class="text-sm font-extrabold text-slate-900"><?= $day ?></p>
                <div class="mt-2 space-y-1">
                    <div class="flex items-center justify-between rounded-lg px-2 py-1 text-xs <?= $matinTotal > 0 ? ($matinNok > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700') : 'bg-slate-100 text-slate-500' ?>">
                        <span class="font-semibold">M</span>
                        <span><?= $matinTotal ?></span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg px-2 py-1 text-xs <?= $soirTotal > 0 ? ($soirNok > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700') : 'bg-slate-100 text-slate-500' ?>">
                        <span class="font-semibold">S</span>
                        <span><?= $soirTotal ?></span>
                    </div>
                </div>
            </article>
        <?php endfor; ?>
    </div>
</section>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <h2 class="text-lg font-bold text-slate-900">Detail journalier</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2 text-left">Jour</th>
                    <th class="px-3 py-2 text-left">Matin</th>
                    <th class="px-3 py-2 text-left">Soir</th>
                    <th class="px-3 py-2 text-left">Total</th>
                    <th class="px-3 py-2 text-left">Conformes</th>
                    <th class="px-3 py-2 text-left">Non conformes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slotsByDay as $date => $slots): ?>
                    <?php
                    $matin = (int) ($slots['matin']['total'] ?? 0);
                    $soir = (int) ($slots['soir']['total'] ?? 0);
                    $total = $matin + $soir;
                    $ok = (int) ($slots['matin']['conformes'] ?? 0) + (int) ($slots['soir']['conformes'] ?? 0);
                    $nok = (int) ($slots['matin']['non_conformes'] ?? 0) + (int) ($slots['soir']['non_conformes'] ?? 0);
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2 font-semibold text-slate-800"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-3 py-2"><?= $matin ?></td>
                        <td class="px-3 py-2"><?= $soir ?></td>
                        <td class="px-3 py-2 font-semibold"><?= $total ?></td>
                        <td class="px-3 py-2 text-emerald-700 font-semibold"><?= $ok ?></td>
                        <td class="px-3 py-2 text-red-700 font-semibold"><?= $nok ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
