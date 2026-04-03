<?php

declare(strict_types=1);

$successMap = [
    'vehicle_created' => 'Vehicule cree.',
    'vehicle_duplicated' => 'Vehicule duplique avec succes.',
    'vehicle_updated' => 'Vehicule modifie.',
    'vehicle_deleted' => 'Vehicule supprime.',
    'vehicle_deleted_force' => 'Vehicule, zones, materiel et historique supprimes.',
    'zone_created' => 'Zone creee.',
    'zone_deleted' => 'Zone supprimee.',
    'controle_created' => 'Controle cree.',
    'controle_updated' => 'Controle modifie.',
    'controle_deleted' => 'Controle supprime.',
];

$errorMap = [
    'invalid_vehicle' => 'Donnees vehicule invalides.',
    'vehicle_save_failed' => 'Impossible d enregistrer le vehicule.',
    'vehicle_duplicate' => 'Couple type + numero deja existant pour cette caserne.',
    'vehicle_delete_failed' => 'Suppression vehicule impossible (contraintes).',
    'vehicle_force_delete_failed' => 'Suppression totale impossible.',
    'vehicle_force_requires_inactive' => 'Suppression totale impossible: passe d abord le vehicule en inactif.',
    'vehicle_in_use' => 'Suppression impossible: ce vehicule est utilise par des controles, zones ou verifications (utiliser Supprimer tout apres passage en inactif).',
    'invalid_zone' => 'Donnees zone invalides.',
    'zones_table_missing' => 'Migration zones non appliquee.',
    'zone_save_failed' => 'Impossible d enregistrer la zone.',
    'zone_delete_failed' => 'Suppression zone impossible (contraintes).',
    'zone_in_use' => 'Suppression impossible: cette zone est utilisee par du materiel ou contient des sous-zones.',
    'invalid_controle' => 'Donnees controle invalides.',
    'invalid_controle_link' => 'Controle incoherent: lie vehicule + poste + zone.',
    'controle_save_failed' => 'Impossible d enregistrer le controle.',
    'controle_delete_failed' => 'Suppression controle impossible (contraintes).',
    'controle_in_use' => 'Suppression impossible: ce materiel est deja reference dans des verifications.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;
        
$pageTitle = 'Configuration vehicules - VerifApp';
$pageHeading = 'Configuration - Vehicules';
$pageSubtitle = 'Creer, dupliquer et ouvrir un vehicule pour gerer ses zones.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<nav class="bg-white rounded-2xl shadow p-2 flex flex-wrap gap-2">
    <a href="/index.php?controller=manager_assets&action=types" class="rounded-xl bg-slate-200 text-slate-800 px-4 py-2 text-sm font-semibold">Types & postes</a>
    <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Vehicules & zones</a>
</nav>

<?php if ($successMessage !== null || $errorMessage !== null): ?>
    <section id="manager-toast" class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-xl rounded-xl border p-4 text-sm shadow-lg <?= $errorMessage !== null ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
            <?= htmlspecialchars((string) ($errorMessage ?? $successMessage), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!$zonesAvailable): ?>
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
        Migration zones non appliquee. Lance `009_create_zones.sql` pour activer la gestion fine des zones.
    </section>
<?php endif; ?>
<?php if (!$hierarchyAvailable): ?>
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
        Mode compatibilite actif. Applique `010_link_controles_to_vehicle_zone.sql` pour lier chaque materiel a un vehicule + une zone.
    </section>
<?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="text-xl font-semibold">Vehicules</h2>
                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                    <input id="vehicles-search" type="search" placeholder="Rechercher un vehicule..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
                    <select id="vehicles-type-filter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-56">
                        <option value="">Tous les types</option>
                        <?php foreach ($typesVehicules as $typeVehicule): ?>
                            <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <form method="post" action="/index.php?controller=manager_assets&action=vehicle_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
                    <option value="">Type</option>
                    <?php foreach ($typesVehicules as $typeVehicule): ?>
                        <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="indicatif" placeholder="Indicatif / numero (ex: 75)" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
                <select name="actif" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold w-12">Sel.</th>
                            <th class="px-3 py-2 text-left font-semibold">Nom vehicule</th>
                            <th class="px-3 py-2 text-left font-semibold">Type</th>
                            <th class="px-3 py-2 text-left font-semibold">Numero</th>
                            <th class="px-3 py-2 text-left font-semibold">Zones</th>
                            <th class="px-3 py-2 text-left font-semibold">Materiels</th>
                            <th class="px-3 py-2 text-left font-semibold">QR engin</th>
                            <th class="px-3 py-2 text-left font-semibold">Etat</th>
                            <th class="px-3 py-2 text-left font-semibold">Actions rapides</th>
                        </tr>
                    </thead>
                    <tbody id="vehicles-list" class="divide-y divide-slate-200">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr data-vehicle-row="1"
                                data-vehicle-id="<?= (int) $vehicle['id'] ?>"
                                data-vehicle-name="<?= htmlspecialchars(strtolower((string) $vehicle['nom']), ENT_QUOTES, 'UTF-8') ?>"
                                data-vehicle-type-id="<?= (int) $vehicle['type_vehicule_id'] ?>"
                                data-vehicle-indicatif="<?= htmlspecialchars((string) ($vehicle['indicatif'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-vehicle-active="<?= (int) $vehicle['actif'] ?>">
                                <td class="px-3 py-2">
                                    <input type="checkbox" data-vehicle-select class="h-4 w-4 rounded border-slate-300">
                                </td>
                                <td class="px-3 py-2 font-semibold text-slate-900">
                                    <a href="/index.php?controller=manager_assets&action=vehicle_detail&id=<?= (int) $vehicle['id'] ?>" class="text-slate-900 hover:underline">
                                        <?= htmlspecialchars((string) $vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td class="px-3 py-2"><?= htmlspecialchars((string) $vehicle['type_vehicule'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars((string) ($vehicle['indicatif'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-3 py-2"><?= (int) ($vehicle['zones_count'] ?? 0) ?></td>
                                <td class="px-3 py-2"><?= (int) ($vehicle['controles_count'] ?? 0) ?></td>
                                <td class="px-3 py-2">
                                    <?php if (!empty($vehicle['has_vehicle_qr'])): ?>
                                        <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2 py-1 text-xs font-semibold">Genere</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2 py-1 text-xs font-semibold">Non genere</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold <?= (int) $vehicle['actif'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                        <?= (int) $vehicle['actif'] === 1 ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="/index.php?controller=manager_assets&action=vehicle_zones&id=<?= (int) $vehicle['id'] ?>" class="rounded-lg border border-slate-300 bg-white text-slate-900 px-3 py-1.5 text-xs font-semibold">Configurer engin</a>
                                        <form method="post" action="/index.php?controller=manager_assets&action=vehicle_delete" class="relative flex items-center">
                                            <input type="hidden" name="id" value="<?= (int) $vehicle['id'] ?>">
                                            <input type="hidden" name="delete_mode" value="safe" data-delete-mode-input>
                                            <button type="submit" data-delete-mode="safe" data-confirm-safe="Supprimer ce vehicule ?" data-loading-label="Suppression..." class="rounded-l-lg bg-red-600 text-white px-3 py-1.5 text-xs font-semibold border-r border-red-500">
                                                Supprimer
                                            </button>
                                            <details class="group">
                                                <summary class="list-none cursor-pointer rounded-r-lg bg-red-600 text-white px-2 py-1.5 text-xs font-semibold select-none">
                                                    ▼
                                                </summary>
                                                <div class="absolute right-0 z-30 mt-1 min-w-[180px] rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                                                    <button type="submit" data-delete-mode="force" data-loading-label="Suppression..." class="w-full rounded-md px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50">
                                                        Supprimer tout
                                                    </button>
                                                </div>
                                            </details>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
                <form id="vehicle-edit-form" method="post" action="/index.php?controller=manager_assets&action=vehicle_save" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3">
                    <input type="hidden" name="id" id="selected-vehicle-id" value="">
                    <p class="text-xs text-slate-600">Action selection: <span id="selected-vehicle-name" class="font-semibold text-slate-900">Aucun vehicule selectionne</span></p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <select id="selected-vehicle-type" name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <?php foreach ($typesVehicules as $typeVehicule): ?>
                                <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input id="selected-vehicle-indicatif" type="text" name="indicatif" placeholder="Indicatif / numero" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select id="selected-vehicle-active" name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                    <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Modifier selection
                    </button>
                </form>

                <form id="vehicle-duplicate-form" method="post" action="/index.php?controller=manager_assets&action=vehicle_duplicate" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3">
                    <input type="hidden" name="source_vehicle_id" id="selected-duplicate-source" value="">
                    <p class="text-xs text-slate-600">Duplication depuis: <span id="selected-duplicate-name" class="font-semibold text-slate-900">Aucun vehicule selectionne</span></p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input id="selected-duplicate-indicatif" type="text" name="indicatif" placeholder="Nouvel indicatif / numero" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select name="duplicate_scope" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="vehicle_only">Dupliquer vehicule vide</option>
                            <option value="with_zones">Dupliquer vehicule + zones</option>
                            <option value="with_zones_controls">Dupliquer vehicule + zones + materiel</option>
                        </select>
                    </div>
                    <button type="submit" data-loading-label="Duplication..." class="rounded-xl border border-slate-300 bg-white text-slate-900 px-4 py-2 text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Dupliquer selection
                    </button>
                </form>
            </div>
        </section>

    </main>
    <div id="danger-delete-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/70 p-4">
        <div class="w-full max-w-xl rounded-2xl border-2 border-red-300 bg-white p-5 shadow-2xl">
            <p class="text-xs font-bold uppercase tracking-wider text-red-700">Attention</p>
            <h3 class="mt-1 text-xl font-extrabold text-slate-900">Suppression irreversible</h3>
            <p class="mt-3 text-sm text-slate-700">
                Vous allez supprimer le vehicule, les zones et le materiel associe.
                Cette action est irreversible.
            </p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button type="button" id="danger-delete-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800">Annuler</button>
                <button type="button" id="danger-delete-confirm" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Oui, supprimer tout</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const vehiclesSearch = document.getElementById('vehicles-search');
            const vehiclesTypeFilter = document.getElementById('vehicles-type-filter');
            const vehicleRows = Array.from(document.querySelectorAll('#vehicles-list tr[data-vehicle-row]'));
            const vehicleCheckboxes = Array.from(document.querySelectorAll('#vehicles-list input[data-vehicle-select]'));
            const vehicleEditForm = document.getElementById('vehicle-edit-form');
            const vehicleDuplicateForm = document.getElementById('vehicle-duplicate-form');
            const selectedVehicleIdInput = document.getElementById('selected-vehicle-id');
            const selectedVehicleNameLabel = document.getElementById('selected-vehicle-name');
            const selectedVehicleTypeSelect = document.getElementById('selected-vehicle-type');
            const selectedVehicleIndicatifInput = document.getElementById('selected-vehicle-indicatif');
            const selectedVehicleActiveSelect = document.getElementById('selected-vehicle-active');
            const selectedDuplicateSourceInput = document.getElementById('selected-duplicate-source');
            const selectedDuplicateNameLabel = document.getElementById('selected-duplicate-name');
            const selectedDuplicateIndicatifInput = document.getElementById('selected-duplicate-indicatif');

            const dangerDeleteModal = document.getElementById('danger-delete-modal');
            const dangerDeleteConfirm = document.getElementById('danger-delete-confirm');
            const dangerDeleteCancel = document.getElementById('danger-delete-cancel');
            let pendingForceForm = null;
            let pendingForceSubmitter = null;

            function filterVehicles() {
                const q = (vehiclesSearch.value || '').trim().toLowerCase();
                const typeId = vehiclesTypeFilter.value || '';
                vehicleRows.forEach(function (row) {
                    const name = row.dataset.vehicleName || '';
                    const rowTypeId = row.dataset.vehicleTypeId || '';
                    const okText = name.includes(q);
                    const okType = typeId === '' || rowTypeId === typeId;
                    row.style.display = okText && okType ? 'table-row' : 'none';
                });
            }

            function getSelectedVehicleRow() {
                for (const checkbox of vehicleCheckboxes) {
                    if (checkbox.checked) {
                        return checkbox.closest('tr[data-vehicle-row]');
                    }
                }

                return null;
            }

            function syncSelectedVehicleActions() {
                const selectedRow = getSelectedVehicleRow();
                const editSubmit = vehicleEditForm ? vehicleEditForm.querySelector('button[type="submit"]') : null;
                const duplicateSubmit = vehicleDuplicateForm ? vehicleDuplicateForm.querySelector('button[type="submit"]') : null;

                if (!selectedRow) {
                    if (selectedVehicleIdInput) selectedVehicleIdInput.value = '';
                    if (selectedVehicleNameLabel) selectedVehicleNameLabel.textContent = 'Aucun vehicule selectionne';
                    if (selectedVehicleIndicatifInput) selectedVehicleIndicatifInput.value = '';
                    if (selectedDuplicateSourceInput) selectedDuplicateSourceInput.value = '';
                    if (selectedDuplicateNameLabel) selectedDuplicateNameLabel.textContent = 'Aucun vehicule selectionne';
                    if (selectedDuplicateIndicatifInput) selectedDuplicateIndicatifInput.value = '';
                    if (editSubmit) editSubmit.disabled = true;
                    if (duplicateSubmit) duplicateSubmit.disabled = true;
                    return;
                }

                const vehicleId = selectedRow.dataset.vehicleId || '';
                const vehicleName = selectedRow.children[1] ? selectedRow.children[1].textContent.trim() : '';
                const vehicleTypeId = selectedRow.dataset.vehicleTypeId || '';
                const vehicleIndicatif = selectedRow.dataset.vehicleIndicatif || '';
                const vehicleActive = selectedRow.dataset.vehicleActive || '1';

                if (selectedVehicleIdInput) selectedVehicleIdInput.value = vehicleId;
                if (selectedVehicleNameLabel) selectedVehicleNameLabel.textContent = vehicleName;
                if (selectedVehicleTypeSelect) selectedVehicleTypeSelect.value = vehicleTypeId;
                if (selectedVehicleIndicatifInput) selectedVehicleIndicatifInput.value = vehicleIndicatif;
                if (selectedVehicleActiveSelect) selectedVehicleActiveSelect.value = vehicleActive;

                if (selectedDuplicateSourceInput) selectedDuplicateSourceInput.value = vehicleId;
                if (selectedDuplicateNameLabel) selectedDuplicateNameLabel.textContent = vehicleName;
                if (selectedDuplicateIndicatifInput) selectedDuplicateIndicatifInput.value = '';

                if (editSubmit) editSubmit.disabled = false;
                if (duplicateSubmit) duplicateSubmit.disabled = false;
            }


            vehicleCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        vehicleCheckboxes.forEach(function (other) {
                            if (other !== checkbox) {
                                other.checked = false;
                            }
                        });
                    }

                    syncSelectedVehicleActions();
                });
            });

            document.querySelectorAll('input[name="indicatif"]').forEach(function (input) {
                input.addEventListener('input', function () {
                    input.value = (input.value || '').toUpperCase();
                });
            });

            if (vehiclesSearch) {
                vehiclesSearch.addEventListener('input', filterVehicles);
            }
            if (vehiclesTypeFilter) {
                vehiclesTypeFilter.addEventListener('change', filterVehicles);
            }
            syncSelectedVehicleActions();

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

                    const deleteModeInput = form.querySelector('input[data-delete-mode-input]');
                    if (deleteModeInput) {
                        deleteModeInput.value = submitter.dataset.deleteMode || 'safe';
                    }

                    const isForceDelete = (submitter.dataset.deleteMode || '') === 'force';
                    const forceConfirmed = form.dataset.forceConfirmed === '1';
                    if (isForceDelete && !forceConfirmed) {
                        event.preventDefault();
                        pendingForceForm = form;
                        pendingForceSubmitter = submitter;
                        if (dangerDeleteModal) {
                            dangerDeleteModal.classList.remove('hidden');
                            dangerDeleteModal.classList.add('flex');
                        }
                        return;
                    }
                    if (isForceDelete && forceConfirmed) {
                        form.dataset.forceConfirmed = '0';
                    }

                    let confirmMessage = submitter.dataset.confirm || '';
                    if (confirmMessage === '') {
                        const submitterMode = submitter.dataset.deleteMode || '';
                        if (submitterMode === 'safe') {
                            confirmMessage = submitter.dataset.confirmSafe || '';
                        } else if (submitterMode === 'force') {
                            confirmMessage = submitter.dataset.confirmForce || '';
                        }
                    }
                    if (confirmMessage === '') {
                        const modeSelect = form.querySelector('select[name="delete_mode"]');
                        if (modeSelect) {
                            const mode = modeSelect.value || 'safe';
                            confirmMessage = mode === 'force'
                                ? (submitter.dataset.confirmForce || '')
                                : (submitter.dataset.confirmSafe || '');
                        }
                    }
                    if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
                        event.preventDefault();
                        return;
                    }

                    const loadingLabel = submitter.dataset.loadingLabel || '';
                    if (loadingLabel !== '') {
                        submitter.dataset.originalLabel = submitter.textContent;
                        submitter.textContent = loadingLabel;
                    }
                    submitter.disabled = true;
                    submitter.classList.add('opacity-60', 'cursor-not-allowed');
                });
            });

            if (dangerDeleteCancel) {
                dangerDeleteCancel.addEventListener('click', function () {
                    pendingForceForm = null;
                    pendingForceSubmitter = null;
                    if (dangerDeleteModal) {
                        dangerDeleteModal.classList.add('hidden');
                        dangerDeleteModal.classList.remove('flex');
                    }
                });
            }

            if (dangerDeleteConfirm) {
                dangerDeleteConfirm.addEventListener('click', function () {
                    if (!pendingForceForm || !pendingForceSubmitter) {
                        if (dangerDeleteModal) {
                            dangerDeleteModal.classList.add('hidden');
                            dangerDeleteModal.classList.remove('flex');
                        }
                        return;
                    }

                    pendingForceForm.dataset.forceConfirmed = '1';
                    if (dangerDeleteModal) {
                        dangerDeleteModal.classList.add('hidden');
                        dangerDeleteModal.classList.remove('flex');
                    }
                    pendingForceForm.requestSubmit(pendingForceSubmitter);
                    pendingForceForm = null;
                    pendingForceSubmitter = null;
                });
            }
        })();
    </script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
