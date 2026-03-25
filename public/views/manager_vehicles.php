<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();

$successMap = [
    'vehicle_created' => 'Vehicule cree.',
    'vehicle_updated' => 'Vehicule modifie.',
    'vehicle_deleted' => 'Vehicule supprime.',
    'zone_created' => 'Zone creee.',
    'zone_deleted' => 'Zone supprimee.',
    'controle_created' => 'Controle cree.',
    'controle_updated' => 'Controle modifie.',
    'controle_deleted' => 'Controle supprime.',
];

$errorMap = [
    'invalid_vehicle' => 'Donnees vehicule invalides.',
    'vehicle_save_failed' => 'Impossible d enregistrer le vehicule.',
    'vehicle_delete_failed' => 'Suppression vehicule impossible (contraintes).',
    'vehicle_in_use' => 'Suppression impossible: ce vehicule est utilise par des controles, zones ou verifications.',
    'invalid_zone' => 'Donnees zone invalides.',
    'zones_table_missing' => 'Migration zones non appliquee.',
    'zone_save_failed' => 'Impossible d enregistrer la zone.',
    'zone_delete_failed' => 'Suppression zone impossible (contraintes).',
    'zone_in_use' => 'Suppression impossible: cette zone est utilisee par du materiel.',
    'invalid_controle' => 'Donnees controle invalides.',
    'invalid_controle_link' => 'Controle incoherent: lie vehicule + poste + zone.',
    'controle_save_failed' => 'Impossible d enregistrer le controle.',
    'controle_delete_failed' => 'Suppression controle impossible (contraintes).',
    'controle_in_use' => 'Suppression impossible: ce materiel est deja reference dans des verifications.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration vehicules - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <header class="flex items-start justify-between gap-3">
            <div>
                <a href="/index.php?controller=manager&action=dashboard" class="text-sm text-slate-500 hover:text-slate-700"><- Retour dashboard</a>
                <h1 class="text-3xl font-bold mt-2">Configuration - Vehicules</h1>
                <p class="text-slate-600 mt-1">Chaque vehicule est rattache a un type et possede ses zones.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/index.php?controller=manager_assets&action=types" class="rounded-lg bg-slate-700 text-white px-4 py-2 text-sm font-medium">Page Types</a>
                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <?php if ($successMessage !== null): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></section>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></section>
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
            <h2 class="text-xl font-semibold mb-4">Vehicules</h2>
            <form method="post" action="/index.php?controller=manager_assets&action=vehicle_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" placeholder="Nom vehicule" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-5">
                <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                    <option value="">Type</option>
                    <?php foreach ($typesVehicules as $typeVehicule): ?>
                        <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="actif" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="space-y-2">
                <?php foreach ($vehicles as $vehicle): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=vehicle_save" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $vehicle['id'] ?>">
                        <input type="text" name="nom" value="<?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                        <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                            <?php foreach ($typesVehicules as $typeVehicule): ?>
                                <option value="<?= (int) $typeVehicule['id'] ?>" <?= (int) $vehicle['type_vehicule_id'] === (int) $typeVehicule['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                            <option value="1" <?= (int) $vehicle['actif'] === 1 ? 'selected' : '' ?>>Actif</option>
                            <option value="0" <?= (int) $vehicle['actif'] === 0 ? 'selected' : '' ?>>Inactif</option>
                        </select>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                        <button formaction="/index.php?controller=manager_assets&action=vehicle_delete" type="submit" onclick="return confirm('Supprimer ce vehicule ?')" class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Zones par vehicule</h2>
            <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <select name="vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-5">
                    <option value="">Vehicule</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="nom" placeholder="Nom zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-5">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="space-y-2">
                <?php foreach ($zones as $zone): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=zone_delete" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                        <input type="text" readonly value="<?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <input type="text" readonly value="<?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <button type="submit" onclick="return confirm('Supprimer cette zone ?')" class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Materiel (controles)</h2>
            <p class="text-sm text-slate-600 mb-3">Edition rapide: toutes les actions restent sur la meme ligne.</p>
            <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="grid grid-cols-1 md:grid-cols-[2.4fr_1.4fr_1.4fr_1.5fr_80px_100px_210px] gap-2 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="libelle" placeholder="Libelle controle" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <select name="vehicule_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" <?= $hierarchyAvailable ? 'required' : '' ?>>
                    <option value="">Vehicule</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int) $vehicle['id'] ?>" data-type-id="<?= (int) $vehicle['type_vehicule_id'] ?>">
                            <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="poste_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Poste</option>
                    <?php foreach ($postes as $poste): ?>
                        <option value="<?= (int) $poste['id'] ?>" data-type-id="<?= (int) $poste['type_vehicule_id'] ?>">
                            <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hierarchyAvailable): ?>
                    <select name="zone_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Zone</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>" data-vehicle-id="<?= (int) $zone['vehicule_id'] ?>">
                                <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="zone_nom" placeholder="Zone" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <?php endif; ?>
                <input type="number" name="ordre" value="0" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm font-semibold">Ajouter</button>
            </form>

            <div class="space-y-3">
                <?php foreach ($controles as $controle): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=controle_save" data-control-form="1" class="grid grid-cols-1 md:grid-cols-[2.4fr_1.4fr_1.4fr_1.5fr_80px_100px_210px] gap-2">
                        <input type="hidden" name="id" value="<?= (int) $controle['id'] ?>">
                        <input type="text" name="libelle" value="<?= htmlspecialchars($controle['libelle'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select name="vehicule_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" <?= $hierarchyAvailable ? 'required' : '' ?>>
                            <option value="">Vehicule</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= (int) $vehicle['id'] ?>" data-type-id="<?= (int) $vehicle['type_vehicule_id'] ?>" <?= (int) ($controle['vehicule_id'] ?? 0) === (int) $vehicle['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="poste_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Poste</option>
                            <?php foreach ($postes as $poste): ?>
                                <option value="<?= (int) $poste['id'] ?>" data-type-id="<?= (int) $poste['type_vehicule_id'] ?>" <?= (int) $controle['poste_id'] === (int) $poste['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($hierarchyAvailable): ?>
                            <select name="zone_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Zone</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?= (int) $zone['id'] ?>" data-vehicle-id="<?= (int) $zone['vehicule_id'] ?>" <?= (int) ($controle['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="zone_nom" value="<?= htmlspecialchars($controle['zone'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <?php endif; ?>
                        <input type="number" name="ordre" value="<?= (int) $controle['ordre'] ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="1" <?= (int) $controle['actif'] === 1 ? 'selected' : '' ?>>Actif</option>
                            <option value="0" <?= (int) $controle['actif'] === 0 ? 'selected' : '' ?>>Inactif</option>
                        </select>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-slate-900 text-white px-3 py-2 text-sm">Modifier</button>
                            <button formaction="/index.php?controller=manager_assets&action=controle_delete" type="submit" onclick="return confirm('Supprimer ce controle ?')" class="flex-1 rounded-xl bg-red-600 text-white px-3 py-2 text-sm">Supprimer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script>
        (function () {
            const forms = Array.from(document.querySelectorAll('form[data-control-form], form[action*="action=controle_save"]'));

            function filterSelect(select, predicate) {
                const currentValue = select.value;
                let hasCurrent = false;

                Array.from(select.options).forEach(function (option) {
                    const keep = predicate(option);
                    option.hidden = !keep;
                    option.disabled = !keep;
                    if (keep && option.value === currentValue) {
                        hasCurrent = true;
                    }
                });

                if (!hasCurrent) {
                    select.value = '';
                }
            }

            function syncForm(form) {
                const vehicleSelect = form.querySelector('select[name="vehicule_id"]');
                const posteSelect = form.querySelector('select[name="poste_id"]');
                const zoneSelect = form.querySelector('select[name="zone_id"]');

                if (!vehicleSelect || !posteSelect) {
                    return;
                }

                const selectedVehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];
                const vehicleId = vehicleSelect.value;
                const typeId = selectedVehicleOption ? (selectedVehicleOption.dataset.typeId || '') : '';

                filterSelect(posteSelect, function (option) {
                    if (option.value === '') {
                        return true;
                    }
                    if (typeId === '') {
                        return true;
                    }
                    return option.dataset.typeId === typeId;
                });

                if (zoneSelect) {
                    filterSelect(zoneSelect, function (option) {
                        if (option.value === '') {
                            return true;
                        }
                        if (vehicleId === '') {
                            return true;
                        }
                        return option.dataset.vehicleId === vehicleId;
                    });
                }
            }

            forms.forEach(function (form) {
                const vehicleSelect = form.querySelector('select[name="vehicule_id"]');
                if (!vehicleSelect) {
                    return;
                }

                vehicleSelect.addEventListener('change', function () {
                    syncForm(form);
                });

                syncForm(form);
            });
        })();
    </script>
</body>
</html>
