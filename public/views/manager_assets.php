<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();

$successMap = [
    'vehicle_created' => 'Vehicule cree.',
    'vehicle_updated' => 'Vehicule modifie.',
    'vehicle_deleted' => 'Vehicule supprime.',
    'zone_created' => 'Zone creee.',
    'zone_deleted' => 'Zone supprimee.',
    'poste_created' => 'Poste cree.',
    'poste_updated' => 'Poste modifie.',
    'poste_deleted' => 'Poste supprime.',
    'controle_created' => 'Controle cree.',
    'controle_updated' => 'Controle modifie.',
    'controle_deleted' => 'Controle supprime.',
];

$errorMap = [
    'invalid_vehicle' => 'Donnees vehicule invalides.',
    'vehicle_save_failed' => 'Impossible d enregistrer le vehicule.',
    'vehicle_delete_failed' => 'Suppression vehicule impossible (contraintes).',
    'invalid_zone' => 'Donnees zone invalides.',
    'zones_table_missing' => 'Migration zones non appliquee.',
    'zone_save_failed' => 'Impossible d enregistrer la zone.',
    'zone_delete_failed' => 'Suppression zone impossible (contraintes).',
    'invalid_poste' => 'Donnees poste invalides.',
    'poste_save_failed' => 'Impossible d enregistrer le poste.',
    'poste_delete_failed' => 'Suppression poste impossible (contraintes).',
    'invalid_controle' => 'Donnees controle invalides.',
    'invalid_controle_link' => 'Controle incoherent: lie vehicule + poste + zone.',
    'controle_save_failed' => 'Impossible d enregistrer le controle.',
    'controle_delete_failed' => 'Suppression controle impossible (contraintes).',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc & materiel - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <header class="flex items-start justify-between gap-3">
            <div>
                <a href="/index.php?controller=manager&action=dashboard" class="text-sm text-slate-500 hover:text-slate-700">
                    <- Retour dashboard
                </a>
                <h1 class="text-3xl font-bold mt-2">Parc & materiel</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                    v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="/index.php?controller=manager_auth&action=logout" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-medium">
                    Deconnexion
                </a>
            </div>
        </header>

        <?php if ($successMessage !== null): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <?php if (!$zonesAvailable || !$hierarchyAvailable): ?>
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
                Mode compatibilite actif. Applique les migrations <code>009_create_zones.sql</code> et <code>010_link_controles_to_vehicle_zone.sql</code> pour activer la hierarchie complete.
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

            <div class="space-y-3">
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
                        <button
                            formaction="/index.php?controller=manager_assets&action=vehicle_delete"
                            type="submit"
                            onclick="return confirm('Supprimer ce vehicule ?')"
                            class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full"
                        >
                            Supprimer
                        </button>
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
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">
                    Ajouter
                </button>
            </form>

            <div class="space-y-3">
                <?php foreach ($zones as $zone): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=zone_delete" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                        <input type="text" readonly value="<?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <input type="text" readonly value="<?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm md:col-span-5">
                        <button type="submit" onclick="return confirm('Supprimer cette zone ?')" class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">
                            Supprimer
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Postes</h2>

            <form method="post" action="/index.php?controller=manager_assets&action=poste_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" placeholder="Nom poste" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                <input type="text" name="code" placeholder="Code poste" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
                    <option value="">Type vehicule</option>
                    <?php foreach ($typesVehicules as $typeVehicule): ?>
                        <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="space-y-3">
                <?php foreach ($postes as $poste): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=poste_save" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $poste['id'] ?>">
                        <input type="text" name="nom" value="<?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                        <input type="text" name="code" value="<?= htmlspecialchars($poste['code'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                        <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                            <?php foreach ($typesVehicules as $typeVehicule): ?>
                                <option value="<?= (int) $typeVehicule['id'] ?>" <?= (int) $poste['type_vehicule_id'] === (int) $typeVehicule['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                        <button
                            formaction="/index.php?controller=manager_assets&action=poste_delete"
                            type="submit"
                            onclick="return confirm('Supprimer ce poste ?')"
                            class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full"
                        >
                            Supprimer
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Controles (materiel)</h2>

            <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="libelle" placeholder="Libelle controle" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                <select name="vehicule_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2" <?= $hierarchyAvailable ? 'required' : '' ?>>
                    <option value="">Vehicule</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int) $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="poste_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                    <option value="">Poste</option>
                    <?php foreach ($postes as $poste): ?>
                        <option value="<?= (int) $poste['id'] ?>"><?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hierarchyAvailable): ?>
                    <select name="zone_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                        <option value="">Zone</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>">
                                <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="zone_nom" placeholder="Zone" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                <?php endif; ?>
                <input type="number" name="ordre" value="0" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-1">
                <select name="actif" class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-1">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-1 w-full">Ajouter</button>
            </form>

            <div class="space-y-3">
                <?php foreach ($controles as $controle): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=controle_save" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $controle['id'] ?>">
                        <input type="text" name="libelle" value="<?= htmlspecialchars($controle['libelle'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                        <select name="vehicule_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2" <?= $hierarchyAvailable ? 'required' : '' ?>>
                            <option value="">Vehicule</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= (int) $vehicle['id'] ?>" <?= (int) ($controle['vehicule_id'] ?? 0) === (int) $vehicle['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="poste_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                            <?php foreach ($postes as $poste): ?>
                                <option value="<?= (int) $poste['id'] ?>" <?= (int) $controle['poste_id'] === (int) $poste['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($hierarchyAvailable): ?>
                            <select name="zone_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?= (int) $zone['id'] ?>" <?= (int) ($controle['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($zone['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="zone_nom" value="<?= htmlspecialchars($controle['zone'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                        <?php endif; ?>
                        <input type="number" name="ordre" value="<?= (int) $controle['ordre'] ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-1">
                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-1">
                            <option value="1" <?= (int) $controle['actif'] === 1 ? 'selected' : '' ?>>Actif</option>
                            <option value="0" <?= (int) $controle['actif'] === 0 ? 'selected' : '' ?>>Inactif</option>
                        </select>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-1 w-full">Modifier</button>
                        <button
                            formaction="/index.php?controller=manager_assets&action=controle_delete"
                            type="submit"
                            onclick="return confirm('Supprimer ce controle ?')"
                            class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-1 w-full"
                        >
                            Supprimer
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
