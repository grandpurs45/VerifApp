<?php

declare(strict_types=1);

$successMap = [
    'zone_created' => 'Zone creee.',
    'zone_updated' => 'Zone modifiee.',
    'zone_deleted' => 'Zone supprimee.',
    'controle_created' => 'Materiel ajoute.',
    'controle_updated' => 'Materiel modifie.',
    'controle_deleted' => 'Materiel supprime.',
];

$errorMap = [
    'invalid_vehicle' => 'Vehicule invalide.',
    'invalid_zone' => 'Donnees zone invalides.',
    'zones_table_missing' => 'Migration zones non appliquee.',
    'zone_save_failed' => 'Impossible d enregistrer la zone.',
    'zone_duplicate' => 'Cette zone existe deja au meme niveau. Utilise un autre nom ou un autre parent.',
    'zone_delete_failed' => 'Suppression zone impossible.',
    'zone_in_use' => 'Suppression impossible: cette zone est utilisee par du materiel ou contient des sous-zones.',
    'invalid_controle' => 'Donnees materiel invalides.',
    'invalid_controle_link' => 'Le materiel doit etre lie au vehicule, a un poste et a une zone.',
    'controle_save_failed' => 'Impossible d enregistrer le materiel.',
    'controle_delete_failed' => 'Suppression materiel impossible.',
    'controle_in_use' => 'Suppression impossible: ce materiel est deja reference dans des verifications.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;

$vehicleName = (string) ($vehicle['nom'] ?? '');
$vehicleId = (int) ($vehicle['id'] ?? 0);
$vehicleType = (string) ($vehicle['type_vehicule'] ?? '');

$pageTitle = 'Configuration engin - VerifApp';
$pageHeading = 'Configuration engin';
$pageSubtitle = $vehicleName . ' - zones et materiel';
$pageBackUrl = '/index.php?controller=manager_assets&action=vehicle_detail&id=' . $vehicleId;
$pageBackLabel = 'Retour fiche vehicule';

$postesById = [];
foreach ($postes as $poste) {
    $postesById[(int) $poste['id']] = (string) ($poste['nom'] ?? '');
}

$zonesById = [];
foreach ($zones as $zone) {
    $zonesById[(int) $zone['id']] = (string) ($zone['chemin'] ?? $zone['nom'] ?? '');
}

$zonesByParent = [];
foreach ($zones as $zone) {
    $parentId = (int) ($zone['parent_id'] ?? 0);
    if (!isset($zonesByParent[$parentId])) {
        $zonesByParent[$parentId] = [];
    }
    $zonesByParent[$parentId][] = $zone;
}

$zoneDescendants = [];
$collectDescendants = function (int $zoneId) use (&$collectDescendants, $zonesByParent, &$zoneDescendants): array {
    if (isset($zoneDescendants[$zoneId])) {
        return $zoneDescendants[$zoneId];
    }

    $descendants = [];
    foreach (($zonesByParent[$zoneId] ?? []) as $child) {
        $childId = (int) ($child['id'] ?? 0);
        if ($childId <= 0) {
            continue;
        }
        $descendants[] = $childId;
        foreach ($collectDescendants($childId) as $nestedId) {
            $descendants[] = $nestedId;
        }
    }

    $zoneDescendants[$zoneId] = array_values(array_unique($descendants));
    return $zoneDescendants[$zoneId];
};

$zoneSubtreeCount = [];
$countSubtree = function (int $zoneId) use (&$countSubtree, $zonesByParent, &$zoneSubtreeCount): int {
    if (isset($zoneSubtreeCount[$zoneId])) {
        return $zoneSubtreeCount[$zoneId];
    }

    $count = 0;
    foreach (($zonesByParent[$zoneId] ?? []) as $child) {
        $childId = (int) ($child['id'] ?? 0);
        if ($childId <= 0) {
            continue;
        }
        $count += 1 + $countSubtree($childId);
    }

    $zoneSubtreeCount[$zoneId] = $count;
    return $count;
};

$controlesByZone = [];
foreach ($controles as $controle) {
    $controleZoneId = (int) ($controle['zone_id'] ?? 0);
    if (!isset($controlesByZone[$controleZoneId])) {
        $controlesByZone[$controleZoneId] = [];
    }
    $controlesByZone[$controleZoneId][] = $controle;
}

$materialGroups = [];
foreach ($zones as $zone) {
    $groupZoneId = (int) ($zone['id'] ?? 0);
    if (!isset($controlesByZone[$groupZoneId])) {
        continue;
    }
    $materialGroups[] = [
        'zone_id' => $groupZoneId,
        'label' => (string) ($zone['chemin'] ?? $zone['nom'] ?? 'Zone'),
        'controles' => $controlesByZone[$groupZoneId],
    ];
    unset($controlesByZone[$groupZoneId]);
}
foreach ($controlesByZone as $groupZoneId => $groupControles) {
    $materialGroups[] = [
        'zone_id' => (int) $groupZoneId,
        'label' => $groupZoneId > 0 ? 'Zone inconnue' : 'Sans zone',
        'controles' => $groupControles,
    ];
}

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

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
        Mode compatibilite actif: la liaison stricte vehicule + zone + materiel n est pas disponible.
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Engin</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Type</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars($vehicleType, ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Synthese</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= count($zones) ?> zone(s) / <?= count($controles) ?> materiel(s)</p>
        </article>
    </div>
</section>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-xl font-semibold">Zones de l engin</h2>
        <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-xl border border-slate-300 bg-slate-100 text-slate-900 px-3 py-2 text-sm font-semibold">Retour liste engins</a>
    </div>

    <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
        <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
        <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
        <select name="parent_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
            <option value="">Zone parent (optionnel)</option>
            <?php foreach ($zones as $zone): ?>
                <?php
                $zoneLevel = isset($zone['niveau']) ? max(1, (int) $zone['niveau']) : 1;
                $zonePrefix = $zoneLevel > 1 ? str_repeat('- ', $zoneLevel - 1) : '';
                ?>
                <option value="<?= (int) $zone['id'] ?>">
                    <?= htmlspecialchars($zonePrefix . (string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="nom" placeholder="Nom zone / sous-zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
        <label class="md:col-span-2">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Position terrain</span>
            <input type="number" name="ordre" min="0" step="1" placeholder="ex: 10" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </label>
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter zone</button>
    </form>
    <p class="mb-3 text-xs text-slate-500">Glisse la poignee pour reordonner les zones d un meme niveau. Le nouvel ordre est enregistre automatiquement.</p>

    <div class="space-y-3" data-zone-sortable data-parent-id="">
        <?php
        $renderZoneNode = function (array $zone, int $level) use (&$renderZoneNode, $zonesByParent, $zones, $vehicleId, $collectDescendants, $countSubtree): void {
            $zoneId = (int) ($zone['id'] ?? 0);
            $zoneName = (string) ($zone['nom'] ?? '');
            $zonePath = (string) ($zone['chemin'] ?? $zoneName);
            $selectedParentId = (int) ($zone['parent_id'] ?? 0);
            $displayOrder = (int) ($zone['ordre'] ?? 0);
            $children = $zonesByParent[$zoneId] ?? [];
            $subtreeCount = $countSubtree($zoneId);
            $excludedParentIds = array_merge([$zoneId], $collectDescendants($zoneId));
            $detailsOpen = '';
            ?>
            <details class="rounded-xl border border-slate-200 bg-slate-50/70" data-zone-item data-zone-id="<?= $zoneId ?>"<?= $detailsOpen ?>>
                <summary class="list-none cursor-pointer px-3 py-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span draggable="true" data-zone-drag-handle class="inline-flex h-7 w-7 cursor-grab items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600" title="Glisser pour reordonner" aria-label="Glisser pour reordonner">&#8942;&#8942;</span>
                            <span class="inline-flex rounded-full bg-slate-200 text-slate-700 px-2 py-0.5 text-xs font-semibold">N<?= $level + 1 ?></span>
                            <span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-xs font-semibold" title="Position d affichage terrain">Pos. <?= $displayOrder ?></span>
                            <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($zoneName, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($subtreeCount > 0): ?>
                                <span class="inline-flex rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-semibold"><?= $subtreeCount ?> sous-zone(s)</span>
                            <?php endif; ?>
                            <span class="text-xs text-slate-500"><?= htmlspecialchars($zonePath, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-300 bg-white text-base font-bold text-slate-800 hover:bg-slate-100"
                            data-add-subzone
                            data-parent-id="<?= $zoneId ?>"
                            data-parent-name="<?= htmlspecialchars($zonePath, ENT_QUOTES, 'UTF-8') ?>"
                            title="Ajouter une sous-zone"
                            aria-label="Ajouter une sous-zone"
                        >
                            +
                        </button>
                    </div>
                </summary>
                <div class="px-3 pb-3">
                    <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= $zoneId ?>">
                        <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
                        <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
                        <select name="parent_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                            <option value="">Zone parent (racine)</option>
                            <?php foreach ($zones as $candidateZone): ?>
                                <?php
                                $candidateId = (int) $candidateZone['id'];
                                if (in_array($candidateId, $excludedParentIds, true)) {
                                    continue;
                                }
                                $candidateLevel = isset($candidateZone['niveau']) ? max(1, (int) $candidateZone['niveau']) : 1;
                                $candidatePrefix = $candidateLevel > 1 ? str_repeat('- ', $candidateLevel - 1) : '';
                                ?>
                                <option value="<?= $candidateId ?>" <?= $selectedParentId === $candidateId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($candidatePrefix . (string) ($candidateZone['chemin'] ?? $candidateZone['nom']), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="nom" value="<?= htmlspecialchars($zoneName, ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                        <label class="md:col-span-2">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Position terrain</span>
                            <input type="number" name="ordre" min="0" step="1" value="<?= $displayOrder ?>" title="Position d affichage terrain" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <input type="text" readonly value="<?= htmlspecialchars($zonePath, ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 md:col-span-3">
                        <div class="md:col-span-12 flex flex-wrap justify-end gap-2">
                            <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm min-w-[120px]">Enregistrer</button>
                            <button
                                type="submit"
                                formaction="/index.php?controller=manager_assets&action=zone_delete"
                                formmethod="post"
                                data-confirm="Supprimer cette zone ?"
                                data-loading-label="Suppression..."
                                class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm min-w-[120px]"
                            >
                                Supprimer
                            </button>
                        </div>
                    </form>
                    <?php if ($children !== []): ?>
                        <div class="mt-2 ml-2 border-l-2 border-slate-200 pl-2 space-y-2" data-zone-sortable data-parent-id="<?= $zoneId ?>">
                            <?php foreach ($children as $childZone): ?>
                                <?php $renderZoneNode($childZone, $level + 1); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </details>
            <?php
        };
        ?>

        <?php
        $rootZones = $zonesByParent[0] ?? [];
        if ($rootZones === []):
        ?>
            <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Aucune zone pour cet engin.</p>
        <?php else: ?>
            <?php foreach ($rootZones as $rootZone): ?>
                <?php $renderZoneNode($rootZone, 0); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section id="vehicle-materials" class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold">Materiel de l engin</h2>
            <p class="mt-1 text-sm text-slate-600"><?= count($controles) ?> materiel(s) configure(s), regroupes par emplacement.</p>
        </div>
        <?php if ($postes !== [] && $zones !== []): ?>
            <button type="button" id="vehicle-control-add-focus" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                Ajouter un materiel
            </button>
        <?php endif; ?>
    </div>

    <?php if ($postes === []): ?>
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
            Aucun poste configure pour le type <strong><?= htmlspecialchars($vehicleType, ENT_QUOTES, 'UTF-8') ?></strong>. Configure d abord les postes dans "Types & postes".
        </div>
    <?php elseif ($zones === []): ?>
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
            Cree d abord au moins une zone avant d ajouter du materiel.
        </div>
    <?php else: ?>
        <form
            id="vehicle-control-create-form"
            method="post"
            action="/index.php?controller=manager_assets&action=controle_save"
            class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
            data-control-form
            data-control-create-form
        >
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="actif" value="1">

            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-base font-semibold text-slate-900">Nouveau materiel</h3>
                <span class="text-xs text-slate-500">Les champs marques * sont obligatoires.</span>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <label class="md:col-span-4">
                    <span class="mb-1 block text-xs font-semibold text-slate-700">Nom du materiel *</span>
                    <input type="text" name="libelle" placeholder="Ex: Radio cabine" required autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="md:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-700">Poste de verification *</span>
                    <select name="poste_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Selectionner</option>
                        <?php foreach ($postes as $poste): ?>
                            <option value="<?= (int) $poste['id'] ?>"><?= htmlspecialchars((string) $poste['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="md:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-700">Emplacement *</span>
                    <select name="zone_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Selectionner</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-slate-700">Position *</span>
                    <input type="number" name="ordre" min="0" step="1" value="<?= (int) $nextOrder ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="md:col-span-4">
                    <span class="mb-1 block text-xs font-semibold text-slate-700">Reponse demandee *</span>
                    <select name="type_saisie" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="statut">Fonctionnel / non fonctionnel</option>
                        <option value="quantite">Present / manquant avec quantite</option>
                        <option value="mesure">Valeur mesuree avec seuils</option>
                    </select>
                </label>
                <div class="hidden md:col-span-8" data-wrap="quantity-fields">
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Quantite attendue *</span>
                        <input type="number" step="1" min="1" name="valeur_attendue" placeholder="Ex: 2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                    </label>
                </div>
                <div class="grid grid-cols-1 gap-3 md:col-span-8 md:grid-cols-3 hidden" data-wrap="measure-fields">
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Unite *</span>
                        <input type="text" name="unite" placeholder="Bars, L, %" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Seuil minimum</span>
                        <input type="number" step="any" name="seuil_min" placeholder="Optionnel" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Seuil maximum</span>
                        <input type="number" step="any" name="seuil_max" placeholder="Optionnel" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
                    </label>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="vehicle-control-remember" class="h-4 w-4 rounded border-slate-300">
                    Conserver le poste, l emplacement et le type apres ajout
                </label>
                <div class="flex gap-2">
                    <button type="button" id="vehicle-control-create-reset" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Reinitialiser</button>
                    <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Ajouter</button>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <div class="mt-6 border-t border-slate-200 pt-5">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
            <label class="md:col-span-7">
                <span class="sr-only">Rechercher un materiel</span>
                <input
                    id="vehicle-control-search"
                    type="search"
                    placeholder="Rechercher par nom, poste, zone ou type"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                >
            </label>
            <label class="md:col-span-5">
                <span class="sr-only">Filtrer par emplacement</span>
                <select id="vehicle-control-zone-filter" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) ($zone['id'] ?? 0) ?>">
                            <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="mt-4 space-y-4" id="vehicle-control-list">
            <?php foreach ($materialGroups as $materialGroup): ?>
                <section class="space-y-2" data-control-group>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars((string) $materialGroup['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600" data-control-group-count>
                            <?= count($materialGroup['controles']) ?>
                        </span>
                    </div>
                    <?php foreach ($materialGroup['controles'] as $controle): ?>
                        <?php
                        $controlId = (int) ($controle['id'] ?? 0);
                        $controlType = (string) ($controle['type_saisie'] ?? 'statut');
                        $controlZoneId = (int) ($controle['zone_id'] ?? 0);
                        $zonePath = $zonesById[$controlZoneId] ?? (string) ($controle['zone'] ?? '');
                        $posteName = (string) ($postesById[(int) ($controle['poste_id'] ?? 0)] ?? '');
                        $typeLabel = $controlType === 'mesure'
                            ? 'Valeur mesuree'
                            : ($controlType === 'quantite' ? 'Presence et quantite' : 'Etat fonctionnel');
                        $expectedQuantity = $controlType === 'quantite' && is_numeric($controle['valeur_attendue'] ?? null)
                            ? max(0, (int) round((float) $controle['valeur_attendue']))
                            : null;
                        $quantityLabel = $controlType === 'quantite'
                            ? ($expectedQuantity !== null ? 'Quantite attendue: ' . $expectedQuantity : 'Quantite non renseignee')
                            : '';
                        $searchText = mb_strtolower(trim(implode(' ', [
                            (string) ($controle['libelle'] ?? ''),
                            $posteName,
                            $zonePath,
                            $typeLabel,
                            $quantityLabel,
                        ])));
                        $isActive = (int) ($controle['actif'] ?? 0) === 1;
                        ?>
                        <details
                            class="rounded-xl border border-slate-200 bg-white"
                            data-control-item
                            data-control-search-text="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                            data-control-zone-id="<?= $controlZoneId ?>"
                        >
                            <summary class="cursor-pointer list-none px-3 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($controle['libelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                            <span><?= htmlspecialchars($posteName, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="text-slate-300">|</span>
                                            <span><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($controlType === 'quantite'): ?>
                                                <span class="text-slate-300">|</span>
                                                <span class="rounded-full px-2 py-0.5 font-semibold <?= $expectedQuantity !== null ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700' ?>">
                                                    <?= htmlspecialchars($quantityLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-slate-300">|</span>
                                            <span>Position <?= (int) ($controle['ordre'] ?? 0) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold <?= $isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                            <?= $isActive ? 'Actif' : 'Inactif' ?>
                                        </span>
                                        <span class="text-xs font-semibold text-slate-600">Modifier</span>
                                    </div>
                                </div>
                            </summary>

                            <form
                                method="post"
                                action="/index.php?controller=manager_assets&action=controle_save"
                                class="border-t border-slate-200 bg-slate-50 p-3"
                                data-control-form
                            >
                                <input type="hidden" name="id" value="<?= $controlId ?>">
                                <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
                                <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                    <label class="md:col-span-4">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Nom du materiel *</span>
                                        <input type="text" name="libelle" value="<?= htmlspecialchars((string) ($controle['libelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Poste *</span>
                                        <select name="poste_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                            <?php foreach ($postes as $poste): ?>
                                                <option value="<?= (int) $poste['id'] ?>" <?= (int) ($controle['poste_id'] ?? 0) === (int) $poste['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Emplacement *</span>
                                        <select name="zone_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                            <?php foreach ($zones as $zone): ?>
                                                <option value="<?= (int) $zone['id'] ?>" <?= $controlZoneId === (int) $zone['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="md:col-span-2">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Position *</span>
                                        <input type="number" name="ordre" min="0" step="1" value="<?= (int) ($controle['ordre'] ?? 0) ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="md:col-span-4">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Reponse demandee *</span>
                                        <select name="type_saisie" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                            <option value="statut" <?= $controlType === 'statut' ? 'selected' : '' ?>>Fonctionnel / non fonctionnel</option>
                                            <option value="quantite" <?= $controlType === 'quantite' ? 'selected' : '' ?>>Present / manquant avec quantite</option>
                                            <option value="mesure" <?= $controlType === 'mesure' ? 'selected' : '' ?>>Valeur mesuree avec seuils</option>
                                        </select>
                                    </label>
                                    <div class="<?= $controlType === 'quantite' ? '' : 'hidden' ?> md:col-span-8" data-wrap="quantity-fields">
                                        <label>
                                            <span class="mb-1 block text-xs font-semibold text-slate-700">Quantite attendue *</span>
                                            <input type="number" step="1" min="1" name="valeur_attendue" value="<?= htmlspecialchars((string) ($controle['valeur_attendue'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:col-span-8 md:grid-cols-3 <?= $controlType === 'mesure' ? '' : 'hidden' ?>" data-wrap="measure-fields">
                                        <label>
                                            <span class="mb-1 block text-xs font-semibold text-slate-700">Unite *</span>
                                            <input type="text" name="unite" value="<?= htmlspecialchars((string) ($controle['unite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                                        </label>
                                        <label>
                                            <span class="mb-1 block text-xs font-semibold text-slate-700">Seuil minimum</span>
                                            <input type="number" step="any" name="seuil_min" value="<?= htmlspecialchars((string) ($controle['seuil_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                                        </label>
                                        <label>
                                            <span class="mb-1 block text-xs font-semibold text-slate-700">Seuil maximum</span>
                                            <input type="number" step="any" name="seuil_max" value="<?= htmlspecialchars((string) ($controle['seuil_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
                                        </label>
                                    </div>
                                    <label class="md:col-span-4">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Etat</span>
                                        <select name="actif" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                            <option value="1" <?= $isActive ? 'selected' : '' ?>>Actif</option>
                                            <option value="0" <?= !$isActive ? 'selected' : '' ?>>Inactif</option>
                                        </select>
                                    </label>
                                </div>

                                <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-3">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700"
                                        data-duplicate-control
                                    >
                                        Dupliquer
                                    </button>
                                    <button
                                        type="submit"
                                        formaction="/index.php?controller=manager_assets&action=controle_delete"
                                        data-confirm="Supprimer ce materiel ?"
                                        data-loading-label="Suppression..."
                                        class="rounded-xl bg-red-600 px-3 py-2 text-sm font-semibold text-white"
                                    >
                                        Supprimer
                                    </button>
                                    <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Enregistrer</button>
                                </div>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <?php if ($controles === []): ?>
                <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Aucun materiel configure pour cet engin.</p>
            <?php endif; ?>
            <p id="vehicle-control-search-empty" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Aucun materiel ne correspond a la recherche.
            </p>
        </div>
    </div>
</section>
<div id="subzone-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/70 p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <h3 class="text-xl font-extrabold text-slate-900">Ajouter une sous-zone</h3>
        <p class="mt-1 text-sm text-slate-600">Parent: <span id="subzone-parent-label" class="font-semibold text-slate-900">-</span></p>
        <form method="post" action="/index.php?controller=manager_assets&action=zone_save" class="mt-4 space-y-3">
            <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" id="subzone-parent-id" name="parent_id" value="">
            <input type="text" id="subzone-name-input" name="nom" placeholder="Nom sous-zone" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button type="button" id="subzone-modal-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800">Annuler</button>
                <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Ajouter sous-zone</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const toast = document.getElementById('manager-toast');
        const subzoneModal = document.getElementById('subzone-modal');
        const subzoneParentIdInput = document.getElementById('subzone-parent-id');
        const subzoneParentLabel = document.getElementById('subzone-parent-label');
        const subzoneNameInput = document.getElementById('subzone-name-input');
        const subzoneModalCancel = document.getElementById('subzone-modal-cancel');
        const controlSearchInput = document.getElementById('vehicle-control-search');
        const controlZoneFilter = document.getElementById('vehicle-control-zone-filter');
        const controlItems = Array.from(document.querySelectorAll('[data-control-item]'));
        const controlGroups = Array.from(document.querySelectorAll('[data-control-group]'));
        const controlSearchEmpty = document.getElementById('vehicle-control-search-empty');
        const controlCreateForm = document.getElementById('vehicle-control-create-form');
        const controlAddFocus = document.getElementById('vehicle-control-add-focus');
        const controlCreateReset = document.getElementById('vehicle-control-create-reset');
        const controlRemember = document.getElementById('vehicle-control-remember');
        const controlDefaultsKey = 'verifapp-control-defaults-<?= $vehicleId ?>';
        if (toast) {
            setTimeout(function () {
                toast.style.transition = 'opacity 240ms ease';
                toast.style.opacity = '0';
                setTimeout(function () {
                    toast.remove();
                }, 260);
            }, 2800);
        }

        function syncControlForm(form) {
            const inputType = form.querySelector('select[name="type_saisie"]');
            const measureWrap = form.querySelector('[data-wrap="measure-fields"]');
            const quantityWrap = form.querySelector('[data-wrap="quantity-fields"]');
            const unitInput = form.querySelector('[data-field="unit"]');
            const expectedInput = form.querySelector('[data-field="expected"]');
            const minInput = form.querySelector('[data-field="min"]');
            const maxInput = form.querySelector('[data-field="max"]');
            if (!inputType || !measureWrap) {
                return;
            }
            const isMeasure = inputType.value === 'mesure';
            const isQuantity = inputType.value === 'quantite';
            measureWrap.classList.toggle('hidden', !isMeasure);
            if (quantityWrap) {
                quantityWrap.classList.toggle('hidden', !isQuantity);
            }
            if (unitInput) {
                unitInput.required = isMeasure;
            }
            if (expectedInput) {
                expectedInput.required = isQuantity;
            }
            if (minInput) {
                minInput.setCustomValidity('');
            }
            if (maxInput) {
                maxInput.setCustomValidity('');
            }
            if (isMeasure && minInput && maxInput) {
                const minValue = minInput.value.trim();
                const maxValue = maxInput.value.trim();
                if (minValue === '' && maxValue === '') {
                    minInput.setCustomValidity('Renseigne au moins un seuil.');
                } else if (minValue !== '' && maxValue !== '' && Number(minValue) > Number(maxValue)) {
                    maxInput.setCustomValidity('Le seuil maximum doit etre superieur ou egal au seuil minimum.');
                }
            }
        }

        document.querySelectorAll('form[data-control-form]').forEach(function (form) {
            const inputType = form.querySelector('select[name="type_saisie"]');
            if (inputType) {
                inputType.addEventListener('change', function () {
                    syncControlForm(form);
                });
            }
            form.addEventListener('input', function () {
                syncControlForm(form);
            });
            syncControlForm(form);
        });

        function applyControlFilter() {
            if (!controlSearchInput) {
                return;
            }

            const query = (controlSearchInput.value || '').toLowerCase().trim();
            const selectedZoneId = controlZoneFilter ? (controlZoneFilter.value || '').trim() : '';
            let visibleCount = 0;
            controlItems.forEach(function (item) {
                const haystack = (item.getAttribute('data-control-search-text') || '').toLowerCase();
                const zoneId = (item.getAttribute('data-control-zone-id') || '').trim();
                const matchText = query === '' || haystack.includes(query);
                const matchZone = selectedZoneId === '' || zoneId === selectedZoneId;
                const match = matchText && matchZone;
                item.classList.toggle('hidden', !match);
                if (match) {
                    visibleCount += 1;
                }
            });

            controlGroups.forEach(function (group) {
                const visibleItems = Array.from(group.querySelectorAll('[data-control-item]')).filter(function (item) {
                    return !item.classList.contains('hidden');
                });
                group.classList.toggle('hidden', visibleItems.length === 0);
                const count = group.querySelector('[data-control-group-count]');
                if (count) {
                    count.textContent = String(visibleItems.length);
                }
            });

            if (controlSearchEmpty) {
                controlSearchEmpty.classList.toggle('hidden', controlItems.length === 0 || visibleCount > 0);
            }
        }

        if (controlSearchInput) {
            controlSearchInput.addEventListener('input', applyControlFilter);
            if (controlZoneFilter) {
                controlZoneFilter.addEventListener('change', applyControlFilter);
            }
            applyControlFilter();
        }

        function setCreateFormValue(name, value) {
            if (!controlCreateForm) {
                return;
            }
            const field = controlCreateForm.elements.namedItem(name);
            if (field && 'value' in field) {
                field.value = value;
            }
        }

        function focusCreateForm() {
            if (!controlCreateForm) {
                return;
            }
            controlCreateForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const labelInput = controlCreateForm.querySelector('input[name="libelle"]');
            if (labelInput) {
                window.setTimeout(function () {
                    labelInput.focus();
                }, 350);
            }
        }

        if (controlAddFocus) {
            controlAddFocus.addEventListener('click', focusCreateForm);
        }

        if (controlCreateForm) {
            try {
                const storedDefaults = JSON.parse(window.localStorage.getItem(controlDefaultsKey) || 'null');
                if (storedDefaults && typeof storedDefaults === 'object') {
                    setCreateFormValue('poste_id', String(storedDefaults.posteId || ''));
                    setCreateFormValue('zone_id', String(storedDefaults.zoneId || ''));
                    setCreateFormValue('type_saisie', String(storedDefaults.inputType || 'statut'));
                    if (controlRemember) {
                        controlRemember.checked = true;
                    }
                    syncControlForm(controlCreateForm);
                }
            } catch (error) {
                window.localStorage.removeItem(controlDefaultsKey);
            }

            controlCreateForm.addEventListener('submit', function () {
                try {
                    if (controlRemember && controlRemember.checked) {
                        const posteField = controlCreateForm.elements.namedItem('poste_id');
                        const zoneField = controlCreateForm.elements.namedItem('zone_id');
                        const typeField = controlCreateForm.elements.namedItem('type_saisie');
                        window.localStorage.setItem(controlDefaultsKey, JSON.stringify({
                            posteId: posteField && 'value' in posteField ? posteField.value : '',
                            zoneId: zoneField && 'value' in zoneField ? zoneField.value : '',
                            inputType: typeField && 'value' in typeField ? typeField.value : 'statut'
                        }));
                    } else {
                        window.localStorage.removeItem(controlDefaultsKey);
                    }
                } catch (error) {
                    // La saisie reste fonctionnelle si le stockage local est bloque.
                }
            });
        }

        if (controlCreateReset && controlCreateForm) {
            controlCreateReset.addEventListener('click', function () {
                controlCreateForm.reset();
                try {
                    window.localStorage.removeItem(controlDefaultsKey);
                } catch (error) {
                    // Aucun blocage du formulaire si le stockage local est indisponible.
                }
                syncControlForm(controlCreateForm);
                const orderInput = controlCreateForm.querySelector('input[name="ordre"]');
                if (orderInput) {
                    orderInput.value = '<?= (int) $nextOrder ?>';
                }
                focusCreateForm();
            });
        }

        document.querySelectorAll('[data-duplicate-control]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!controlCreateForm) {
                    return;
                }
                const sourceForm = button.closest('form[data-control-form]');
                if (!sourceForm) {
                    return;
                }
                ['libelle', 'poste_id', 'zone_id', 'type_saisie', 'valeur_attendue', 'unite', 'seuil_min', 'seuil_max'].forEach(function (name) {
                    const sourceField = sourceForm.elements.namedItem(name);
                    if (sourceField && 'value' in sourceField) {
                        setCreateFormValue(name, sourceField.value);
                    }
                });
                syncControlForm(controlCreateForm);
                focusCreateForm();
                const labelInput = controlCreateForm.querySelector('input[name="libelle"]');
                if (labelInput) {
                    labelInput.select();
                }
            });
        });

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
                    submitter.disabled = true;
                    submitter.classList.add('opacity-60', 'cursor-not-allowed');
                }
            });
        });

        function closeAllInfoPanels() {
            document.querySelectorAll('[data-info-panel]').forEach(function (panel) {
                panel.classList.add('hidden');
            });
        }

        document.querySelectorAll('[data-info-trigger]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const panel = button.parentElement ? button.parentElement.querySelector('[data-info-panel]') : null;
                if (!panel) {
                    return;
                }
                const isHidden = panel.classList.contains('hidden');
                closeAllInfoPanels();
                if (isHidden) {
                    panel.classList.remove('hidden');
                }
            });
        });

        document.addEventListener('click', function () {
            closeAllInfoPanels();
        });

        document.querySelectorAll('[data-add-subzone]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (!subzoneModal || !subzoneParentIdInput || !subzoneParentLabel || !subzoneNameInput) {
                    return;
                }
                const parentId = button.getAttribute('data-parent-id') || '';
                const parentName = button.getAttribute('data-parent-name') || '-';
                subzoneParentIdInput.value = parentId;
                subzoneParentLabel.textContent = parentName;
                subzoneNameInput.value = '';
                subzoneModal.classList.remove('hidden');
                subzoneModal.classList.add('flex');
                subzoneNameInput.focus();
            });
        });

        if (subzoneModalCancel) {
            subzoneModalCancel.addEventListener('click', function () {
                if (!subzoneModal) {
                    return;
                }
                subzoneModal.classList.add('hidden');
                subzoneModal.classList.remove('flex');
            });
        }

        if (subzoneModal) {
            subzoneModal.addEventListener('click', function (event) {
                if (event.target === subzoneModal) {
                    subzoneModal.classList.add('hidden');
                    subzoneModal.classList.remove('flex');
                }
            });
        }

        let draggedZoneItem = null;
        let draggedZoneContainer = null;
        document.querySelectorAll('[data-zone-drag-handle]').forEach(function (handle) {
            handle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
            });
            handle.addEventListener('dragstart', function (event) {
                draggedZoneItem = handle.closest('[data-zone-item]');
                draggedZoneContainer = draggedZoneItem ? draggedZoneItem.parentElement.closest('[data-zone-sortable]') : null;
                if (!draggedZoneItem || !draggedZoneContainer) {
                    event.preventDefault();
                    return;
                }
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', draggedZoneItem.getAttribute('data-zone-id') || '');
                draggedZoneItem.classList.add('opacity-50');
            });
            handle.addEventListener('dragend', async function () {
                if (!draggedZoneItem || !draggedZoneContainer) {
                    return;
                }
                draggedZoneItem.classList.remove('opacity-50');
                const orderedIds = Array.from(draggedZoneContainer.children)
                    .filter(function (child) { return child.matches('[data-zone-item]'); })
                    .map(function (child) { return child.getAttribute('data-zone-id') || ''; })
                    .filter(Boolean);
                const parentId = draggedZoneContainer.getAttribute('data-parent-id') || '';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const body = new URLSearchParams({
                    vehicle_id: '<?= $vehicleId ?>',
                    parent_id: parentId,
                    ordered_ids: orderedIds.join(','),
                    _csrf_token: csrfToken
                });
                try {
                    const response = await fetch('/index.php?controller=manager_assets&action=zone_order_save', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: body.toString()
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        throw new Error('save_failed');
                    }
                    Array.from(draggedZoneContainer.children)
                        .filter(function (child) { return child.matches('[data-zone-item]'); })
                        .forEach(function (item, index) {
                            const badge = item.querySelector('summary [title="Position d affichage terrain"]');
                            if (badge) {
                                badge.textContent = 'Pos. ' + String((index + 1) * 10);
                            }
                            const input = item.querySelector(':scope > div input[name="ordre"]');
                            if (input) {
                                input.value = String((index + 1) * 10);
                            }
                        });
                } catch (error) {
                    window.alert('Impossible d enregistrer le nouvel ordre des zones.');
                    window.location.reload();
                } finally {
                    draggedZoneItem = null;
                    draggedZoneContainer = null;
                }
            });
        });

        document.querySelectorAll('[data-zone-sortable]').forEach(function (container) {
            container.addEventListener('dragover', function (event) {
                if (!draggedZoneItem || draggedZoneContainer !== container) {
                    return;
                }
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                const candidates = Array.from(container.children).filter(function (child) {
                    return child.matches('[data-zone-item]') && child !== draggedZoneItem;
                });
                const next = candidates.find(function (candidate) {
                    const box = candidate.getBoundingClientRect();
                    return event.clientY < box.top + (box.height / 2);
                });
                if (next) {
                    container.insertBefore(draggedZoneItem, next);
                } else {
                    container.appendChild(draggedZoneItem);
                }
            });
        });
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
