<?php

declare(strict_types=1);

$pageTitle = 'Vue mensuelle - VerifApp';
$pageHeading = 'Verifications mensuelles';
$pageSubtitle = 'Couverture quotidienne selon la frequence de chaque vehicule.';
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

        <div class="md:col-span-2 flex flex-wrap items-end gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Afficher</button>
            <a href="/index.php?controller=verifications&action=history" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Vue liste</a>
            <a href="/index.php?<?= htmlspecialchars(http_build_query(['controller'=>'verifications','action'=>'yearly','year'=>(int)substr($monthValue,0,4),'vehicule_id'=>$selectedVehicleId]), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Vue annuelle</a>
        </div>
    </form>
</section>

<section class="grid grid-cols-2 gap-3 md:grid-cols-5">
<?php if (\App\Core\ManagerAccess::hasPermission($managerRole ?? '', 'users.manage')): ?>
    <div class="col-span-2 md:col-span-5"><a href="/index.php?controller=statistics_share&action=manage" class="text-sm font-semibold text-sky-800 underline underline-offset-4">Partager le suivi de la caserne</a></div>
<?php endif; ?>
    <article class="rounded-2xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Periode</p>
        <p class="mt-2 text-lg font-extrabold text-slate-900"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Verifications enregistrees</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $totals['total_verifs'] ?></p>
    </article>
    <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Creneaux attendus / jour</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $expectedPostesPerDay ?></p>
    </article>
    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Journees a 100%</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $totals['jours_complets'] ?> / <?= (int) $eligibleDays ?></p>
    </article>
    <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm col-span-2 md:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Taux de couverture</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= (int) $conformityRate ?>%</p>
    </article>
</section>

<section class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
    <p>
        L objectif est exprime en creneaux par poste: journee, ou matin et soir selon le vehicule.
        En mode matin et soir, une verification en garde de 24 h couvre 2 creneaux; une garde de 12 h en couvre 1
        (soir a partir de <?= (int) $verificationEveningHour ?> h). Chaque formulaire compte pour une seule verification enregistree.
    </p>
    <details class="mt-2">
        <summary class="cursor-pointer font-semibold text-slate-900">
            Voir les <?= (int) $expectedPostesPerDay ?> creneau(x) attendu(s) par jour
        </summary>
        <?php if ($expectedPostes === []): ?>
            <p class="mt-2 text-slate-500">Aucun poste verifiable dans ce perimetre.</p>
        <?php else: ?>
            <ul class="mt-2 grid grid-cols-1 gap-1 md:grid-cols-2">
                <?php foreach ($expectedPostes as $expectedPoste): ?>
                    <li class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                        <span class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($expectedPoste['vehicule_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-slate-400">/</span>
                        <?= htmlspecialchars((string) ($expectedPoste['poste_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="ml-1 font-semibold text-slate-500">
                            (<?= ($expectedPoste['verification_frequency'] ?? 'daily') === 'twice_daily' ? 'matin + soir' : '1 fois / jour' ?>)
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
</section>

<?php require __DIR__ . '/partials/verification_calendar.php'; ?>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <h2 class="text-lg font-bold text-slate-900">Detail journalier</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-[1000px] w-full whitespace-nowrap text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-2 text-left">Jour</th>
                    <th class="px-3 py-2 text-left">Creneaux couverts</th>
                    <th class="px-3 py-2 text-left">Creneaux attendus</th>
                    <th class="px-3 py-2 text-left">Matin / soir / jour</th>
                    <th class="px-3 py-2 text-left">Couverture</th>
                    <th class="px-3 py-2 text-left">Verifications enregistrees</th>
                    <th class="px-3 py-2 text-left">Verifications non conformes</th>
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
                    $morningCovered = (int) ($dayData['matin_couverts'] ?? 0);
                    $eveningCovered = (int) ($dayData['soir_couverts'] ?? 0);
                    $dailyCovered = (int) ($dayData['jour_couverts'] ?? 0);
                    $dayEligible = (bool) ($dayData['eligible'] ?? false);
                    $dayComplete = (bool) ($dayData['complet'] ?? false);
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2 font-semibold text-slate-800"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-3 py-2 font-semibold"><?= $dayEligible ? $verifiedPostes : '-' ?></td>
                        <td class="px-3 py-2"><?= $dayEligible ? $expectedPostes : '-' ?></td>
                        <td class="px-3 py-2"><?= $dayEligible ? 'M ' . $morningCovered . ' / S ' . $eveningCovered . ' / J ' . $dailyCovered : '-' ?></td>
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
