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
    'zone_in_use' => 'Suppression impossible: cette zone est utilisee par du materiel ou contient des sous-zones.',
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

        <nav class="bg-white rounded-2xl shadow p-2 flex flex-wrap gap-2">
            <a href="/index.php?controller=manager_assets&action=types" class="rounded-xl bg-slate-200 text-slate-800 px-4 py-2 text-sm font-semibold">Types & postes</a>
            <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Vehicules & zones</a>
        </nav>

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
                <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div id="vehicles-list" class="space-y-2">
                <?php foreach ($vehicles as $vehicle): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=vehicle_save" data-vehicle-name="<?= htmlspecialchars(strtolower((string) $vehicle['nom']), ENT_QUOTES, 'UTF-8') ?>" data-vehicle-type-id="<?= (int) $vehicle['type_vehicule_id'] ?>" class="grid grid-cols-1 md:grid-cols-12 gap-2">
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
                        <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                        <button formaction="/index.php?controller=manager_assets&action=vehicle_delete" type="submit" data-confirm="Supprimer ce vehicule ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="text-xl font-semibold">Zones par vehicule</h2>
                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                    <input id="zones-search" type="search" placeholder="Rechercher une zone..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
                    <select id="zones-vehicle-filter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-56">
                        <option value="">Tous les vehicules</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <select name="vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4" data-zone-vehicle-select>
                    <option value="">Vehicule</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="parent_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3" data-zone-parent-select>
                    <option value="">Zone parent (optionnel)</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) $zone['id'] ?>" data-vehicle-id="<?= (int) $zone['vehicule_id'] ?>">
                            <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="nom" placeholder="Nom zone / sous-zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div id="zones-list" class="space-y-2">
                <?php foreach ($zones as $zone): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=zone_delete" data-zone-name="<?= htmlspecialchars(strtolower((string) ($zone['chemin'] ?? $zone['nom'])), ENT_QUOTES, 'UTF-8') ?>" data-zone-vehicle-id="<?= (int) $zone['vehicule_id'] ?>" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                        <input type="text" readonly value="<?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <input type="text" readonly value="<?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <button type="submit" data-confirm="Supprimer cette zone ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <h2 class="text-xl font-semibold">Materiel (controles)</h2>
                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                    <input id="controls-search" type="search" placeholder="Rechercher un materiel..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
                    <select id="controls-vehicle-filter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-56">
                        <option value="">Tous les vehicules</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="controls-poste-filter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-56">
                        <option value="">Tous les postes</option>
                        <?php foreach ($postes as $poste): ?>
                            <option value="<?= (int) $poste['id'] ?>"><?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-2">Edition rapide en ligne avec filtres instantanes.</p>
            <p class="text-xs text-slate-500 mb-4">
                Regle: <strong>Statut</strong> = aucun champ requis. <strong>Quantite</strong> = renseigner au moins <em>Attendue</em> (et optionnellement <em>Unite</em>). <strong>Mesure</strong> = renseigner <em>Unite</em> et au moins un seuil <em>Min</em> ou <em>Max</em>.
            </p>

            <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2 mb-4">
                <input type="hidden" name="id" value="0">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                    <input type="text" name="libelle" placeholder="Libelle controle" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
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
                                        <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="zone_nom" placeholder="Zone" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-8 gap-2">
                    <select name="type_saisie" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-input-type>
                        <option value="statut">Statut</option>
                        <option value="quantite">Quantite</option>
                        <option value="mesure">Mesure</option>
                    </select>
                    <div data-wrap="expected">
                        <input type="number" step="0.01" min="0" name="valeur_attendue" placeholder="Attendue (ex: 5)" aria-label="Valeur attendue" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                    </div>
                    <div data-wrap="unit">
                        <input type="text" name="unite" placeholder="Unite (ex: bar)" aria-label="Unite" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                    </div>
                    <div data-wrap="min">
                        <input type="number" step="0.01" name="seuil_min" placeholder="Min (ex: 120)" aria-label="Seuil minimum" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                    </div>
                    <div data-wrap="max">
                        <input type="number" step="0.01" name="seuil_max" placeholder="Max (ex: 300)" aria-label="Seuil maximum" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
                    </div>
                    <input type="number" name="ordre" value="0" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                    <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm font-semibold">Ajouter</button>
                </div>
            </form>

            <div id="controls-list" class="space-y-3">
                <?php foreach ($controles as $controle): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=controle_save" data-control-form="1" data-control-label="<?= htmlspecialchars(strtolower((string) $controle['libelle']), ENT_QUOTES, 'UTF-8') ?>" data-control-vehicle-id="<?= (int) ($controle['vehicule_id'] ?? 0) ?>" data-control-poste-id="<?= (int) $controle['poste_id'] ?>" class="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                        <input type="hidden" name="id" value="<?= (int) $controle['id'] ?>">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                            <input type="text" name="libelle" value="<?= htmlspecialchars($controle['libelle'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
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
                                            <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="zone_nom" value="<?= htmlspecialchars($controle['zone'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-8 gap-2">
                            <select name="type_saisie" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-input-type>
                                <option value="statut" <?= (($controle['type_saisie'] ?? 'statut') === 'statut') ? 'selected' : '' ?>>Statut</option>
                                <option value="quantite" <?= (($controle['type_saisie'] ?? 'statut') === 'quantite') ? 'selected' : '' ?>>Quantite</option>
                                <option value="mesure" <?= (($controle['type_saisie'] ?? 'statut') === 'mesure') ? 'selected' : '' ?>>Mesure</option>
                            </select>
                            <div data-wrap="expected">
                                <input type="number" step="0.01" min="0" name="valeur_attendue" value="<?= htmlspecialchars((string) ($controle['valeur_attendue'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Attendue" aria-label="Valeur attendue" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                            </div>
                            <div data-wrap="unit">
                                <input type="text" name="unite" value="<?= htmlspecialchars((string) ($controle['unite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Unite" aria-label="Unite" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                            </div>
                            <div data-wrap="min">
                                <input type="number" step="0.01" name="seuil_min" value="<?= htmlspecialchars((string) ($controle['seuil_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Min" aria-label="Seuil minimum" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                            </div>
                            <div data-wrap="max">
                                <input type="number" step="0.01" name="seuil_max" value="<?= htmlspecialchars((string) ($controle['seuil_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Max" aria-label="Seuil maximum" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
                            </div>
                            <input type="number" name="ordre" value="<?= (int) $controle['ordre'] ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="1" <?= (int) $controle['actif'] === 1 ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= (int) $controle['actif'] === 0 ? 'selected' : '' ?>>Inactif</option>
                            </select>
                            <div class="flex gap-2">
                                <button type="submit" data-loading-label="Maj..." class="flex-1 rounded-xl bg-slate-900 text-white px-3 py-2 text-sm">Modifier</button>
                                <button formaction="/index.php?controller=manager_assets&action=controle_delete" type="submit" data-confirm="Supprimer ce controle ?" data-loading-label="Suppression..." class="flex-1 rounded-xl bg-red-600 text-white px-3 py-2 text-sm">Supprimer</button>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script>
        (function () {
            const vehiclesSearch = document.getElementById('vehicles-search');
            const vehiclesTypeFilter = document.getElementById('vehicles-type-filter');
            const vehicleRows = Array.from(document.querySelectorAll('#vehicles-list form[data-vehicle-name]'));

            const zonesSearch = document.getElementById('zones-search');
            const zonesVehicleFilter = document.getElementById('zones-vehicle-filter');
            const zoneRows = Array.from(document.querySelectorAll('#zones-list form[data-zone-name]'));
            const zoneVehicleSelect = document.querySelector('[data-zone-vehicle-select]');
            const zoneParentSelect = document.querySelector('[data-zone-parent-select]');

            const controlsSearch = document.getElementById('controls-search');
            const controlsVehicleFilter = document.getElementById('controls-vehicle-filter');
            const controlsPosteFilter = document.getElementById('controls-poste-filter');
            const controlRows = Array.from(document.querySelectorAll('#controls-list form[data-control-form]'));

            const forms = Array.from(document.querySelectorAll('form[data-control-form], form[action*="action=controle_save"]'));

            function filterVehicles() {
                const q = (vehiclesSearch.value || '').trim().toLowerCase();
                const typeId = vehiclesTypeFilter.value || '';
                vehicleRows.forEach(function (row) {
                    const name = row.dataset.vehicleName || '';
                    const rowTypeId = row.dataset.vehicleTypeId || '';
                    const okText = name.includes(q);
                    const okType = typeId === '' || rowTypeId === typeId;
                    row.style.display = okText && okType ? '' : 'none';
                });
            }

            function filterZones() {
                const q = (zonesSearch.value || '').trim().toLowerCase();
                const vehicleId = zonesVehicleFilter.value || '';
                zoneRows.forEach(function (row) {
                    const name = row.dataset.zoneName || '';
                    const rowVehicleId = row.dataset.zoneVehicleId || '';
                    const okText = name.includes(q);
                    const okVehicle = vehicleId === '' || rowVehicleId === vehicleId;
                    row.style.display = okText && okVehicle ? '' : 'none';
                });
            }

            function syncZoneParentSelect() {
                if (!zoneVehicleSelect || !zoneParentSelect) {
                    return;
                }

                const vehicleId = zoneVehicleSelect.value || '';
                const currentValue = zoneParentSelect.value;
                let keepCurrent = false;

                Array.from(zoneParentSelect.options).forEach(function (option, index) {
                    if (index === 0 || option.value === '') {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionVehicleId = option.dataset.vehicleId || '';
                    const keep = vehicleId === '' || optionVehicleId === vehicleId;
                    option.hidden = !keep;
                    option.disabled = !keep;
                    if (keep && option.value === currentValue) {
                        keepCurrent = true;
                    }
                });

                if (!keepCurrent) {
                    zoneParentSelect.value = '';
                }
            }

            function filterControlsList() {
                const q = (controlsSearch.value || '').trim().toLowerCase();
                const vehicleId = controlsVehicleFilter.value || '';
                const posteId = controlsPosteFilter.value || '';

                controlRows.forEach(function (row) {
                    const label = row.dataset.controlLabel || '';
                    const rowVehicleId = row.dataset.controlVehicleId || '';
                    const rowPosteId = row.dataset.controlPosteId || '';
                    const okText = label.includes(q);
                    const okVehicle = vehicleId === '' || rowVehicleId === vehicleId;
                    const okPoste = posteId === '' || rowPosteId === posteId;
                    row.style.display = okText && okVehicle && okPoste ? '' : 'none';
                });
            }

            function snapshotOptions(select) {
                return Array.from(select.options).map(function (option) {
                    return {
                        value: option.value,
                        label: option.textContent,
                        selected: option.selected,
                        typeId: option.dataset.typeId || '',
                        vehicleId: option.dataset.vehicleId || ''
                    };
                });
            }

            function refillSelect(select, sourceOptions, predicate) {
                const currentValue = select.value;
                select.innerHTML = '';
                let hasCurrent = false;

                sourceOptions.forEach(function (entry) {
                    if (!predicate(entry)) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = entry.value;
                    option.textContent = entry.label;
                    if (entry.typeId !== '') {
                        option.dataset.typeId = entry.typeId;
                    }
                    if (entry.vehicleId !== '') {
                        option.dataset.vehicleId = entry.vehicleId;
                    }
                    if (entry.value === currentValue) {
                        option.selected = true;
                        hasCurrent = true;
                    }
                    select.appendChild(option);
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

                refillSelect(posteSelect, form._allPosteOptions, function (entry) {
                    if (entry.value === '') {
                        return true;
                    }
                    if (typeId === '') {
                        return true;
                    }
                    return entry.typeId === typeId;
                });

                if (zoneSelect) {
                    refillSelect(zoneSelect, form._allZoneOptions, function (entry) {
                        if (entry.value === '') {
                            return true;
                        }
                        if (vehicleId === '') {
                            return true;
                        }
                        return entry.vehicleId === vehicleId;
                    });
                }
            }

            function syncInputTypeFields(form) {
                const inputType = form.querySelector('select[name="type_saisie"]');
                if (!inputType) {
                    return;
                }

                const expectedInput = form.querySelector('[data-field="expected"]');
                const unitInput = form.querySelector('[data-field="unit"]');
                const minInput = form.querySelector('[data-field="min"]');
                const maxInput = form.querySelector('[data-field="max"]');
                const expectedWrap = form.querySelector('[data-wrap="expected"]');
                const unitWrap = form.querySelector('[data-wrap="unit"]');
                const minWrap = form.querySelector('[data-wrap="min"]');
                const maxWrap = form.querySelector('[data-wrap="max"]');
                const type = inputType.value || 'statut';

                if (expectedInput) {
                    expectedInput.disabled = type !== 'quantite';
                    expectedInput.required = type === 'quantite';
                    if (type !== 'quantite') {
                        expectedInput.value = '';
                    }
                }
                if (expectedWrap) {
                    expectedWrap.classList.toggle('hidden', type !== 'quantite');
                }

                if (unitInput) {
                    unitInput.disabled = type === 'statut';
                    unitInput.required = type === 'mesure';
                    if (type === 'statut') {
                        unitInput.value = '';
                    }
                }
                if (unitWrap) {
                    unitWrap.classList.toggle('hidden', type === 'statut');
                }

                if (minInput) {
                    minInput.disabled = type !== 'mesure';
                    if (type !== 'mesure') {
                        minInput.value = '';
                    }
                }
                if (minWrap) {
                    minWrap.classList.toggle('hidden', type !== 'mesure');
                }

                if (maxInput) {
                    maxInput.disabled = type !== 'mesure';
                    if (type !== 'mesure') {
                        maxInput.value = '';
                    }
                }
                if (maxWrap) {
                    maxWrap.classList.toggle('hidden', type !== 'mesure');
                }
            }

            forms.forEach(function (form) {
                const vehicleSelect = form.querySelector('select[name="vehicule_id"]');
                const posteSelect = form.querySelector('select[name="poste_id"]');
                const zoneSelect = form.querySelector('select[name="zone_id"]');
                const inputTypeSelect = form.querySelector('select[name="type_saisie"]');
                if (!vehicleSelect) {
                    return;
                }

                form._allPosteOptions = posteSelect ? snapshotOptions(posteSelect) : [];
                form._allZoneOptions = zoneSelect ? snapshotOptions(zoneSelect) : [];

                vehicleSelect.addEventListener('change', function () {
                    syncForm(form);
                });

                if (inputTypeSelect) {
                    inputTypeSelect.addEventListener('change', function () {
                        syncInputTypeFields(form);
                    });
                }

                syncForm(form);
                syncInputTypeFields(form);
            });

            if (vehiclesSearch) {
                vehiclesSearch.addEventListener('input', filterVehicles);
            }
            if (vehiclesTypeFilter) {
                vehiclesTypeFilter.addEventListener('change', filterVehicles);
            }
            if (zonesSearch) {
                zonesSearch.addEventListener('input', filterZones);
            }
            if (zonesVehicleFilter) {
                zonesVehicleFilter.addEventListener('change', filterZones);
            }
            if (zoneVehicleSelect) {
                zoneVehicleSelect.addEventListener('change', syncZoneParentSelect);
                syncZoneParentSelect();
            }
            if (controlsSearch) {
                controlsSearch.addEventListener('input', filterControlsList);
            }
            if (controlsVehicleFilter) {
                controlsVehicleFilter.addEventListener('change', filterControlsList);
            }
            if (controlsPosteFilter) {
                controlsPosteFilter.addEventListener('change', filterControlsList);
            }

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
                        submitter.dataset.originalLabel = submitter.textContent;
                        submitter.textContent = loadingLabel;
                    }
                    submitter.disabled = true;
                    submitter.classList.add('opacity-60', 'cursor-not-allowed');
                });
            });
        })();
    </script>
</body>
</html>
