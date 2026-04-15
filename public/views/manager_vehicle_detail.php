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
        <input type="text" name="nom" placeholder="Nom zone / sous-zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-6">
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter zone</button>
    </form>
    <p class="mb-3 text-xs text-slate-500">Sous-zones illimitees: ex. Cellule &gt; Sac PS &gt; Sacoche rouge.</p>

    <div class="space-y-3">
        <?php
        $renderZoneNode = function (array $zone, int $level) use (&$renderZoneNode, $zonesByParent, $zones, $vehicleId, $collectDescendants, $countSubtree): void {
            $zoneId = (int) ($zone['id'] ?? 0);
            $zoneName = (string) ($zone['nom'] ?? '');
            $zonePath = (string) ($zone['chemin'] ?? $zoneName);
            $selectedParentId = (int) ($zone['parent_id'] ?? 0);
            $children = $zonesByParent[$zoneId] ?? [];
            $subtreeCount = $countSubtree($zoneId);
            $excludedParentIds = array_merge([$zoneId], $collectDescendants($zoneId));
            $detailsOpen = $level <= 1 ? ' open' : '';
            ?>
            <details class="rounded-xl border border-slate-200 bg-slate-50/70"<?= $detailsOpen ?>>
                <summary class="list-none cursor-pointer px-3 py-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full bg-slate-200 text-slate-700 px-2 py-0.5 text-xs font-semibold">N<?= $level + 1 ?></span>
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
                        <input type="text" readonly value="<?= htmlspecialchars($zonePath, ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 md:col-span-5">
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
                        <div class="mt-2 ml-2 border-l-2 border-slate-200 pl-2 space-y-2">
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

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <h2 class="text-xl font-semibold">Materiel de l engin</h2>
        <p class="text-xs text-slate-500">Saisie simplifiee: libelle + poste + zone + type de reponse.</p>
    </div>
    <p class="mb-3 text-xs text-slate-500">Astuce Presence (check): quantite attendue + reponse terrain Present/Manquant.</p>

    <?php if ($postes === []): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
            Aucun poste configure pour le type <strong><?= htmlspecialchars($vehicleType, ENT_QUOTES, 'UTF-8') ?></strong>. Configure d abord les postes dans "Types & postes".
        </div>
    <?php elseif ($zones === []): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
            Cree d abord au moins une zone avant d ajouter du materiel.
        </div>
    <?php else: ?>
        <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2 mb-4" data-control-form>
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="ordre" value="<?= (int) $nextOrder ?>">
            <input type="hidden" name="actif" value="1">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="text" name="libelle" placeholder="Nom du materiel (ex: Radio cabine)" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                <select name="poste_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                    <option value="">Poste</option>
                    <?php foreach ($postes as $poste): ?>
                        <option value="<?= (int) $poste['id'] ?>"><?= htmlspecialchars((string) $poste['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="zone_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                    <option value="">Zone</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type_saisie" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <option value="statut">Fonctionnel / non fonctionnel</option>
                    <option value="quantite">Presence (check)</option>
                    <option value="mesure">Valeur mesuree</option>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 hidden" data-wrap="measure-fields">
                <input type="text" name="unite" placeholder="Unite (ex: Bars, L, %)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                <input type="number" step="1" name="seuil_min" placeholder="Seuil minimum" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                <input type="number" step="1" name="seuil_max" placeholder="Seuil maximum" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 hidden" data-wrap="quantity-fields">
                <input type="number" step="1" min="1" name="valeur_attendue" placeholder="Quantite attendue (ex: 2)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                <div class="md:col-span-2 flex items-center justify-start">
                    <div class="relative inline-flex">
                        <button
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-xs font-bold text-amber-800"
                            aria-label="Info type Presence"
                            data-info-trigger
                        >
                            i
                        </button>
                        <div
                            class="hidden absolute left-9 top-1/2 z-20 w-72 -translate-y-1/2 rounded-lg border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900 shadow-lg"
                            data-info-panel
                        >
                            Affiche sur terrain: quantite + libelle (ex: 2 Biseptine), reponse Present/Manquant.
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Ajouter materiel</button>
        </form>
    <?php endif; ?>

    <div class="space-y-3">
        <?php foreach ($controles as $controle): ?>
            <?php
            $controlId = (int) ($controle['id'] ?? 0);
            $controlType = (string) ($controle['type_saisie'] ?? 'statut');
            $zonePath = $zonesById[(int) ($controle['zone_id'] ?? 0)] ?? (string) ($controle['zone'] ?? '');
            ?>
            <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="rounded-xl border border-slate-200 p-3 space-y-2" data-control-form>
                <input type="hidden" name="id" value="<?= $controlId ?>">
                <input type="hidden" name="vehicule_id" value="<?= $vehicleId ?>">
                <input type="hidden" name="return_vehicle_id" value="<?= $vehicleId ?>">
                <input type="hidden" name="ordre" value="<?= (int) ($controle['ordre'] ?? 0) ?>">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <input type="text" name="libelle" value="<?= htmlspecialchars((string) ($controle['libelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                    <select name="poste_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                        <?php foreach ($postes as $poste): ?>
                            <option value="<?= (int) $poste['id'] ?>" <?= (int) ($controle['poste_id'] ?? 0) === (int) $poste['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="zone_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>" <?= (int) ($controle['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($zone['chemin'] ?? $zone['nom']), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type_saisie" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                        <option value="statut" <?= $controlType === 'statut' ? 'selected' : '' ?>>Fonctionnel / non fonctionnel</option>
                        <option value="quantite" <?= $controlType === 'quantite' ? 'selected' : '' ?>>Presence (check)</option>
                        <option value="mesure" <?= $controlType === 'mesure' ? 'selected' : '' ?>>Valeur mesuree</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 <?= $controlType === 'mesure' ? '' : 'hidden' ?>" data-wrap="measure-fields">
                    <input type="text" name="unite" value="<?= htmlspecialchars((string) ($controle['unite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Unite" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="unit">
                    <input type="number" step="1" name="seuil_min" value="<?= htmlspecialchars((string) ($controle['seuil_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Seuil minimum" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="min">
                    <input type="number" step="1" name="seuil_max" value="<?= htmlspecialchars((string) ($controle['seuil_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Seuil maximum" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="max">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 <?= $controlType === 'quantite' ? '' : 'hidden' ?>" data-wrap="quantity-fields">
                    <input type="number" step="1" min="1" name="valeur_attendue" value="<?= htmlspecialchars((string) ($controle['valeur_attendue'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite attendue" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" data-field="expected">
                    <div class="md:col-span-2 flex items-center justify-start">
                        <div class="relative inline-flex">
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-xs font-bold text-amber-800"
                                aria-label="Info type Presence"
                                data-info-trigger
                            >
                                i
                            </button>
                            <div
                                class="hidden absolute left-9 top-1/2 z-20 w-72 -translate-y-1/2 rounded-lg border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900 shadow-lg"
                                data-info-panel
                            >
                                Affiche sur terrain: quantite + libelle (ex: 2 Biseptine), reponse Present/Manquant.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-slate-500">
                        Poste: <strong><?= htmlspecialchars((string) ($postesById[(int) ($controle['poste_id'] ?? 0)] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($zonePath !== ''): ?>
                            | Zone: <strong><?= htmlspecialchars($zonePath, ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php endif; ?>
                    </p>
                    <div class="flex gap-2">
                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="1" <?= (int) ($controle['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                            <option value="0" <?= (int) ($controle['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactif</option>
                        </select>
                        <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm font-semibold">Enregistrer</button>
                        <button
                            type="submit"
                            formaction="/index.php?controller=manager_assets&action=controle_delete"
                            data-confirm="Supprimer ce materiel ?"
                            data-loading-label="Suppression..."
                            class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm font-semibold"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>

        <?php if ($controles === []): ?>
            <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Aucun materiel configure pour cet engin.</p>
        <?php endif; ?>
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
        }

        document.querySelectorAll('form[data-control-form]').forEach(function (form) {
            const inputType = form.querySelector('select[name="type_saisie"]');
            if (inputType) {
                inputType.addEventListener('change', function () {
                    syncControlForm(form);
                });
            }
            syncControlForm(form);
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
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
