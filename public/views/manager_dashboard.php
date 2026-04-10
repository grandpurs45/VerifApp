<?php

declare(strict_types=1);

$pageTitle = 'Dashboard gestionnaire - VerifApp';
$pageHeading = 'Tableau de bord';
$pageSubtitle = 'Bonjour ' . (string) ($managerUser['nom'] ?? 'Gestionnaire') . '. Voici ce qui demande de l action aujourd hui.';
$pageBackUrl = '';
$canVerificationDashboard = isset($canVerificationDashboard) ? (bool) $canVerificationDashboard : false;
$hasQrSection = $canVerificationDashboard || $canPharmacy;
$openAnomaliesCount = (int) ($anomalyStats['ouverte'] ?? 0);
$nonConformesCount = (int) ($stats['non_conformes_today'] ?? 0);
$pharmacyAlertCount = (int) ($pharmacyStats['alert_articles'] ?? 0);
$monthlyCoverageRate = (int) ($stats['month_coverage_rate'] ?? 0);
$monthlySlotsDone = (int) ($stats['month_slots_done'] ?? 0);
$monthlySlotsExpected = (int) ($stats['month_slots_expected'] ?? 0);

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (isset($_GET['password_changed']) && $_GET['password_changed'] === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Mot de passe modifie avec succes.
    </section>
<?php endif; ?>

<?php if ($canAnomalies || $canVerificationDashboard || $canPharmacy): ?>
    <section class="rounded-2xl bg-white shadow p-4 md:p-5">
        <div class="mb-4">
            <h2 class="text-lg font-bold">Indicateurs par module</h2>
            <p class="text-sm text-slate-600">Lecture rapide des priorites par domaine.</p>
        </div>

        <div class="space-y-5">
            <?php if ($canAnomalies): ?>
                <article class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Anomalies</h3>
                        <span class="text-xs uppercase tracking-wide text-slate-500">Suivi</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <a href="/index.php?controller=anomalies&action=index&statut=ouverte" class="rounded-2xl p-4 shadow-sm hover:shadow <?= $openAnomaliesCount === 0 ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' ?>">
                            <p class="text-sm font-semibold <?= $openAnomaliesCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>">A traiter maintenant</p>
                            <p class="text-3xl font-extrabold mt-1 <?= $openAnomaliesCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>"><?= $openAnomaliesCount ?></p>
                            <p class="text-sm mt-1 <?= $openAnomaliesCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>">Anomalies ouvertes</p>
                        </a>
                        <a href="/index.php?controller=anomalies&action=index&statut=en_cours&assigne_a=<?= isset($managerUser['id']) ? (int) $managerUser['id'] : 0 ?>" class="rounded-2xl bg-amber-50 border border-amber-200 p-4 shadow-sm hover:shadow">
                            <p class="text-sm text-amber-700 font-semibold">Mon suivi</p>
                            <p class="text-3xl font-extrabold text-amber-700 mt-1"><?= (int) ($assignmentStats['mes_anomalies'] ?? 0) ?></p>
                            <p class="text-sm text-amber-700 mt-1">Anomalies assignees</p>
                        </a>
                        <a href="/index.php?controller=anomalies&action=index&assigne_a=none" class="rounded-2xl bg-sky-50 border border-sky-200 p-4 shadow-sm hover:shadow">
                            <p class="text-sm text-sky-700 font-semibold">A distribuer</p>
                            <p class="text-3xl font-extrabold text-sky-700 mt-1"><?= (int) ($assignmentStats['non_assignees'] ?? 0) ?></p>
                            <p class="text-sm text-sky-700 mt-1">Non assignees</p>
                        </a>
                    </div>
                </article>
            <?php endif; ?>

            <?php if ($canVerificationDashboard): ?>
                <article class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Verifications</h3>
                        <span class="text-xs uppercase tracking-wide text-slate-500">Performance</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <article class="bg-slate-50 rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Verifs aujourd hui</p>
                            <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_today'] ?? 0) ?></p>
                        </article>
                        <article class="bg-slate-50 rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Conformes</p>
                            <p class="text-2xl font-bold mt-1 text-emerald-700"><?= (int) ($stats['conformes_today'] ?? 0) ?></p>
                        </article>
                        <article class="rounded-2xl p-4 <?= $nonConformesCount === 0 ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' ?>">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Non conformes</p>
                            <p class="text-2xl font-bold mt-1 <?= $nonConformesCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>"><?= $nonConformesCount ?></p>
                        </article>
                        <article class="bg-slate-50 rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Total verifs</p>
                            <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_all'] ?? 0) ?></p>
                        </article>
                    </div>
                    <article class="mt-3 rounded-2xl p-4 <?= $monthlyCoverageRate >= 90 ? 'bg-emerald-50 border border-emerald-200' : ($monthlyCoverageRate >= 70 ? 'bg-amber-50 border border-amber-200' : 'bg-red-50 border border-red-200') ?>">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold <?= $monthlyCoverageRate >= 90 ? 'text-emerald-700' : ($monthlyCoverageRate >= 70 ? 'text-amber-700' : 'text-red-700') ?>">
                                Taux de verification du mois (jusqu a hier)
                            </p>
                            <p class="text-2xl font-extrabold <?= $monthlyCoverageRate >= 90 ? 'text-emerald-700' : ($monthlyCoverageRate >= 70 ? 'text-amber-700' : 'text-red-700') ?>">
                                <?= $monthlyCoverageRate ?>%
                            </p>
                        </div>
                        <p class="mt-1 text-xs <?= $monthlyCoverageRate >= 90 ? 'text-emerald-700' : ($monthlyCoverageRate >= 70 ? 'text-amber-700' : 'text-red-700') ?>">
                            Creneaux couverts: <?= $monthlySlotsDone ?> / <?= $monthlySlotsExpected ?> (matin + soir, jour en cours exclu)
                        </p>
                    </article>
                </article>
            <?php endif; ?>

            <?php if ($canPharmacy): ?>
                <article class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Pharmacie</h3>
                        <span class="text-xs uppercase tracking-wide text-slate-500">Stock</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <article class="bg-slate-50 rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Articles actifs</p>
                            <p class="text-2xl font-bold mt-1"><?= (int) ($pharmacyStats['total_articles'] ?? 0) ?></p>
                        </article>
                        <article class="rounded-2xl p-4 <?= $pharmacyAlertCount === 0 ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' ?>">
                            <p class="text-xs uppercase tracking-wide <?= $pharmacyAlertCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>">En alerte stock</p>
                            <p class="text-2xl font-bold mt-1 <?= $pharmacyAlertCount === 0 ? 'text-emerald-700' : 'text-red-700' ?>"><?= $pharmacyAlertCount ?></p>
                        </article>
                        <article class="bg-slate-50 rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Sorties (7 jours)</p>
                            <p class="text-2xl font-bold mt-1"><?= (int) ($pharmacyStats['outputs_last_7_days'] ?? 0) ?></p>
                        </article>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-3">
    <?php if ($canAnomalies): ?>
        <a href="/index.php?controller=anomalies&action=index" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Traiter les anomalies</p>
            <p class="text-sm text-slate-600 mt-1">Voir, assigner et suivre l avancement.</p>
        </a>
    <?php endif; ?>
    <?php if ($canHistory): ?>
        <a href="/index.php?controller=verifications&action=history" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Historique des verifications</p>
            <p class="text-sm text-slate-600 mt-1">Retrouver une verification en quelques clics.</p>
        </a>
    <?php endif; ?>
    <?php if ($canAssets): ?>
        <a href="/index.php?controller=manager_assets&action=types" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Configurer les types</p>
            <p class="text-sm text-slate-600 mt-1">Postes standards par type d engin.</p>
        </a>
        <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Configurer les vehicules</p>
            <p class="text-sm text-slate-600 mt-1">Zones, sous-zones et materiel.</p>
        </a>
    <?php endif; ?>
    <?php if ($canPharmacy): ?>
        <a href="/index.php?controller=manager_pharmacy&action=index" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Module pharmacie</p>
            <p class="text-sm text-slate-600 mt-1">Stock et sorties via QR code.</p>
        </a>
    <?php endif; ?>
    <?php if ($canUsers): ?>
        <a href="/index.php?controller=manager_admin&action=menu" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
            <p class="font-semibold">Administration</p>
            <p class="text-sm text-slate-600 mt-1">Roles, acces et gestion des utilisateurs.</p>
        </a>
    <?php endif; ?>
</section>

<?php if ($hasQrSection): ?>
    <section class="rounded-2xl bg-white shadow p-4 md:p-5">
        <h2 class="text-xl font-bold">Acces invites (QR)</h2>
        <p class="text-sm text-slate-600 mt-1">Liens directs a transformer en QR code pour les modules terrain.</p>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php if ($canVerificationDashboard): ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-800">Verification terrain</p>
                    <p class="text-xs text-slate-500 mt-1 break-all"><?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-2 flex gap-2">
                        <a href="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir</a>
                        <button type="button" data-copy="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                    </div>
                </article>
            <?php endif; ?>

            <?php if ($canPharmacy): ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-800">Sortie pharmacie</p>
                    <p class="text-xs text-slate-500 mt-1 break-all"><?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-2 flex gap-2">
                        <a href="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir</a>
                        <button type="button" data-copy="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<script>
    (function () {
        const buttons = document.querySelectorAll('button[data-copy]');
        buttons.forEach((button) => {
            button.addEventListener('click', async function () {
                const value = button.getAttribute('data-copy') || '';
                if (!value) return;
                try {
                    await navigator.clipboard.writeText(value);
                    const previous = button.textContent;
                    button.textContent = 'Copie';
                    setTimeout(() => {
                        button.textContent = previous;
                    }, 1200);
                } catch (error) {
                    window.prompt('Copiez ce lien:', value);
                }
            });
        });
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
