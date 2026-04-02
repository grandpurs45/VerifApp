<?php

declare(strict_types=1);

$successMap = [
    'zone_created' => 'Zone creee.',
    'zone_deleted' => 'Zone supprimee.',
];

$errorMap = [
    'invalid_vehicle' => 'Vehicule invalide.',
    'invalid_zone' => 'Donnees zone invalides.',
    'zones_table_missing' => 'Migration zones non appliquee.',
    'zone_save_failed' => 'Impossible d enregistrer la zone.',
    'zone_delete_failed' => 'Suppression zone impossible.',
    'zone_in_use' => 'Suppression impossible: cette zone est utilisee par du materiel ou contient des sous-zones.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;

$vehicleName = (string) ($vehicle['nom'] ?? '');
$vehicleId = (int) ($vehicle['id'] ?? 0);

$pageTitle = 'Zones vehicule - VerifApp';
$pageHeading = 'Zones - ' . $vehicleName;
$pageSubtitle = 'Gestion des zones et sous-zones de ce vehicule.';
$pageBackUrl = '/index.php?controller=manager_assets&action=vehicle_detail&id=' . $vehicleId;
$pageBackLabel = 'Retour fiche vehicule';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($successMessage !== null || $errorMessage !== null): ?>
    <section id="manager-toast" class="fixed top-4 right-4 z-50 max-w-sm rounded-xl border p-4 text-sm shadow-lg <?= $errorMessage !== null ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
        <?= htmlspecialchars((string) ($errorMessage ?? $successMessage), ENT_QUOTES, 'UTF-8') ?>
    </section>
<?php endif; ?>

<?php if (!$zonesAvailable): ?>
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
        Migration zones non appliquee. Lance `009_create_zones.sql` pour activer la gestion fine des zones.
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-xl font-semibold">Zones du vehicule</h2>
        <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-xl border border-slate-300 bg-slate-100 text-slate-900 px-3 py-2 text-sm font-semibold">Retour liste</a>
    </div>

    <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
        <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
        <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
        <select name="parent_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
            <option value="">Zone parent (optionnel)</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?= (int) $zone['id'] ?>">
                    <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="nom" placeholder="Nom zone / sous-zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-6">
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
    </form>

    <div class="space-y-2">
        <?php foreach ($zones as $zone): ?>
            <form method="post" action="/index.php?controller=manager_assets&action=zone_delete" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
                <input type="text" readonly value="<?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-10">
                <button type="submit" data-confirm="Supprimer cette zone ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<script>
    (function () {
        const toast = document.getElementById('manager-toast');
        if (toast) {
            setTimeout(function () {
                toast.style.transition = 'opacity 240ms ease';
                toast.style.opacity = '0';
                setTimeout(function () {
                    toast.remove();
                }, 260);
            }, 2800);
        }

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const submitter = event.submitter;
                if (!submitter) {
                    return;
                }

                const confirmMessage = submitter.dataset.confirm || '';
                if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
                    event.preventDefault();
                    return;
                }

                const loadingLabel = submitter.dataset.loadingLabel || '';
                if (loadingLabel !== '') {
                    submitter.textContent = loadingLabel;
                }
                submitter.disabled = true;
                submitter.classList.add('opacity-60', 'cursor-not-allowed');
            });
        });

    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
