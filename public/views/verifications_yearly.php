<?php
declare(strict_types=1);
$pageTitle = 'Couverture annuelle - VerifApp';
$pageHeading = 'Couverture annuelle';
$pageSubtitle = $selectedVehicleId > 0 ? (string)$vehiclesById[$selectedVehicleId]['nom'] : 'Tous les vehicules';
$pageBackUrl = $monthlyUrl;
$pageBackLabel = 'Retour vue mensuelle';
$comparisonOptions = array_values(array_filter($availableYears,static fn(int $year): bool => $year !== $selectedYear));
require __DIR__ . '/partials/backoffice_shell_top.php';
?>
<section class="border-b border-slate-300 pb-4">
    <form method="get" action="/index.php" class="grid grid-cols-2 gap-3 xl:grid-cols-[150px_180px_minmax(180px,1fr)_auto] xl:items-end">
        <input type="hidden" name="controller" value="verifications">
        <input type="hidden" name="action" value="yearly">
        <label class="text-sm font-semibold text-slate-700">Annee
            <select name="year" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                <?php foreach ($yearOptions as $year): ?><option value="<?= $year ?>" <?= $year === $selectedYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Comparer avec
            <select name="compare_year" <?= $comparisonOptions === [] ? 'disabled' : '' ?> class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 disabled:text-slate-400">
                <option value="0"><?= $comparisonOptions === [] ? 'Aucune autre annee' : 'Sans comparaison' ?></option>
                <?php foreach ($comparisonOptions as $year): ?><option value="<?= $year ?>" <?= $year === $comparisonYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Vehicule
            <select name="vehicule_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                <option value="0">Tous les vehicules</option>
                <?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>" <?= (int)$vehicle['id'] === $selectedVehicleId ? 'selected' : '' ?>><?= htmlspecialchars((string)$vehicle['nom'],ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="self-end rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Afficher</button>
    </form>
</section>
<section class="grid grid-cols-1 gap-4 border-b border-slate-300 pb-4 sm:grid-cols-3">
<?php if (\App\Core\ManagerAccess::hasPermission($managerRole ?? '', 'users.manage')): ?>
    <div class="sm:col-span-3"><a href="/index.php?controller=statistics_share&action=manage" class="text-sm font-semibold text-sky-800 underline underline-offset-4">Partager le suivi de la caserne</a></div>
<?php endif; ?>
    <div><p class="text-xs font-semibold uppercase text-slate-500">Couverture <?= $selectedYear ?></p><p class="mt-1 text-3xl font-bold"><?= $primarySummary['rate'] !== null ? $primarySummary['rate'].'%' : '-' ?></p></div>
    <div><p class="text-xs font-semibold uppercase text-slate-500">Creneaux couverts / attendus</p><p class="mt-1 text-2xl font-bold"><?= number_format($primarySummary['covered'],0,',',' ') ?> / <?= number_format($primarySummary['expected'],0,',',' ') ?></p></div>
    <div><p class="text-xs font-semibold uppercase text-slate-500">Verifications enregistrees</p><p class="mt-1 text-3xl font-bold"><?= number_format($primarySummary['verifications'],0,',',' ') ?></p></div>
</section>
<?php require __DIR__ . '/partials/verification_annual_chart.php'; ?>
<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
