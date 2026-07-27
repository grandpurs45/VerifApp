<?php

declare(strict_types=1);

$pageTitle = 'Vue mensuelle - VerifApp';
$pageHeading = 'Verifications mensuelles';
$pageSubtitle = 'Couverture quotidienne des postes de verification.';
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
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Postes / jour</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $expectedPostesPerDay ?></p>
    </article>
    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Journees a 100%</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $totals['jours_complets'] ?> / <?= (int) $eligibleDays ?></p>
    </article>
    <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm col-span-2 md:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Conformite</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $conformityRate ?>%</p>
    </article>
</section>

<section class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
    Regle appliquee: chaque poste de chaque engin selectionne doit etre verifie au moins une fois par jour.
    Plusieurs verifications du meme poste dans la journee comptent comme une seule couverture.
</section>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Couverture quotidienne</h2>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> 100%</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Partielle</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-slate-700"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Non commencee</span>
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
            $dayData = $dailyCoverage[$dateKey] ?? [];
            $verifiedPostes = (int) ($dayData['postes_verifies_count'] ?? 0);
            $expectedPostes = (int) ($dayData['postes_attendus'] ?? $expectedPostesPerDay);
            $dayRate = (int) ($dayData['pourcentage'] ?? 0);
            $dayComplete = (bool) ($dayData['complet'] ?? false);
            $dayEligible = (bool) ($dayData['eligible'] ?? false);
            $dayNonConformes = (int) ($dayData['non_conformes'] ?? 0);
            $dayStarted = $verifiedPostes > 0;
            $dayClass = !$dayEligible
                ? 'border-slate-200 bg-slate-50 text-slate-400'
                : ($dayComplete
                    ? 'border-emerald-200 bg-emerald-50'
                    : ($dayStarted ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'));
            ?>
            <article
                class="min-h-[104px] rounded-xl border p-2 <?= $dayClass ?>"
                title="<?= $dayEligible ? $verifiedPostes . ' poste(s) verifie(s) sur ' . $expectedPostes : 'Journee a venir' ?>"
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
                        <p class="text-center text-xs font-bold text-slate-700"><?= $verifiedPostes ?> / <?= $expectedPostes ?></p>
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
</section>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <h2 class="text-lg font-bold text-slate-900">Detail journalier</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2 text-left">Jour</th>
                    <th class="px-3 py-2 text-left">Postes verifies</th>
                    <th class="px-3 py-2 text-left">Postes attendus</th>
                    <th class="px-3 py-2 text-left">Conformite</th>
                    <th class="px-3 py-2 text-left">Verifications</th>
                    <th class="px-3 py-2 text-left">Non conformes</th>
                    <th class="px-3 py-2 text-left">Etat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyCoverage as $date => $dayData): ?>
                    <?php
                    $verifiedPostes = (int) ($dayData['postes_verifies_count'] ?? 0);
                    $expectedPostes = (int) ($dayData['postes_attendus'] ?? 0);
                    $dayRate = (int) ($dayData['pourcentage'] ?? 0);
                    $dayTotal = (int) ($dayData['total_verifs'] ?? 0);
                    $dayNok = (int) ($dayData['non_conformes'] ?? 0);
                    $dayEligible = (bool) ($dayData['eligible'] ?? false);
                    $dayComplete = (bool) ($dayData['complet'] ?? false);
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2 font-semibold text-slate-800"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-3 py-2 font-semibold"><?= $dayEligible ? $verifiedPostes : '-' ?></td>
                        <td class="px-3 py-2"><?= $dayEligible ? $expectedPostes : '-' ?></td>
                        <td class="px-3 py-2 font-semibold <?= $dayComplete ? 'text-emerald-700' : 'text-slate-700' ?>">
                            <?= $dayEligible ? $dayRate . '%' : '-' ?>
                        </td>
                        <td class="px-3 py-2"><?= $dayTotal ?></td>
                        <td class="px-3 py-2 text-red-700 font-semibold"><?= $dayNok ?></td>
                        <td class="px-3 py-2">
                            <?php if (!$dayEligible): ?>
                                <span class="text-slate-400">A venir</span>
                            <?php elseif ($dayComplete): ?>
                                <span class="font-semibold text-emerald-700">Complete</span>
                            <?php elseif ($verifiedPostes > 0): ?>
                                <span class="font-semibold text-amber-700">Partielle</span>
                            <?php else: ?>
                                <span class="text-slate-500">Non commencee</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
