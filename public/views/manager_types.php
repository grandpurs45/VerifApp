<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();

$successMap = [
    'type_created' => 'Type d engin cree.',
    'type_updated' => 'Type d engin modifie.',
    'type_deleted' => 'Type d engin supprime.',
    'poste_created' => 'Poste cree.',
    'poste_updated' => 'Poste modifie.',
    'poste_deleted' => 'Poste supprime.',
];

$errorMap = [
    'invalid_type' => 'Donnees type invalides.',
    'type_save_failed' => 'Impossible d enregistrer le type.',
    'type_delete_failed' => 'Suppression type impossible (contraintes).',
    'invalid_poste' => 'Donnees poste invalides.',
    'poste_save_failed' => 'Impossible d enregistrer le poste.',
    'poste_delete_failed' => 'Suppression poste impossible (contraintes).',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration types - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <header class="flex items-start justify-between gap-3">
            <div>
                <a href="/index.php?controller=manager&action=dashboard" class="text-sm text-slate-500 hover:text-slate-700"><- Retour dashboard</a>
                <h1 class="text-3xl font-bold mt-2">Configuration - Types d engins</h1>
                <p class="text-slate-600 mt-1">Chaque type porte ses postes standards (mode caserne scalable).</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-lg bg-slate-700 text-white px-4 py-2 text-sm font-medium">Page Vehicules</a>
                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <?php if ($successMessage !== null): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></section>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Types d engins</h2>
            <form method="post" action="/index.php?controller=manager_assets&action=type_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" placeholder="Nom type (ex: VSAV, FPT)" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-10">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="space-y-2">
                <?php foreach ($typesVehicules as $typeVehicule): ?>
                    <form method="post" action="/index.php?controller=manager_assets&action=type_save" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <input type="hidden" name="id" value="<?= (int) $typeVehicule['id'] ?>">
                        <input type="text" name="nom" value="<?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-8">
                        <button type="submit" class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                        <button formaction="/index.php?controller=manager_assets&action=type_delete" type="submit" onclick="return confirm('Supprimer ce type ?')" class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <h2 class="text-xl font-semibold mb-4">Postes par type</h2>
            <form method="post" action="/index.php?controller=manager_assets&action=poste_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" placeholder="Nom poste" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4">
                <input type="text" name="code" placeholder="Code poste" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                <select name="type_vehicule_id" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-3">
                    <option value="">Type</option>
                    <?php foreach ($typesVehicules as $typeVehicule): ?>
                        <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
            </form>

            <div class="space-y-2">
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
                        <button formaction="/index.php?controller=manager_assets&action=poste_delete" type="submit" onclick="return confirm('Supprimer ce poste ?')" class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
