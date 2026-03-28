<?php

declare(strict_types=1);

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
    'type_in_use' => 'Suppression impossible: ce type est utilise par des vehicules ou postes.',
    'invalid_poste' => 'Donnees poste invalides.',
    'poste_save_failed' => 'Impossible d enregistrer le poste.',
    'poste_delete_failed' => 'Suppression poste impossible (contraintes).',
    'poste_in_use' => 'Suppression impossible: ce poste est deja utilise par du materiel ou des verifications.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;

$pageTitle = 'Configuration types - VerifApp';
$pageHeading = 'Configuration - Types d engins';
$pageSubtitle = 'Chaque type porte ses postes standards (mode caserne scalable).';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<nav class="bg-white rounded-2xl shadow p-2 flex flex-wrap gap-2">
    <a href="/index.php?controller=manager_assets&action=types" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Types & postes</a>
    <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-xl bg-slate-200 text-slate-800 px-4 py-2 text-sm font-semibold">Vehicules & zones</a>
</nav>

<?php if ($successMessage !== null || $errorMessage !== null): ?>
    <section id="manager-toast" class="fixed top-4 right-4 z-50 max-w-sm rounded-xl border p-4 text-sm shadow-lg <?= $errorMessage !== null ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
        <?= htmlspecialchars((string) ($errorMessage ?? $successMessage), ENT_QUOTES, 'UTF-8') ?>
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-xl font-semibold">Types d engins</h2>
        <input id="types-search" type="search" placeholder="Rechercher un type..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
    </div>

    <form method="post" action="/index.php?controller=manager_assets&action=type_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-5">
        <input type="hidden" name="id" value="0">
        <input type="text" name="nom" placeholder="Nom type (ex: VSAV, FPT)" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-10">
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
    </form>

    <div id="types-list" class="space-y-2">
        <?php foreach ($typesVehicules as $typeVehicule): ?>
            <form method="post" action="/index.php?controller=manager_assets&action=type_save" data-type-name="<?= htmlspecialchars(strtolower((string) $typeVehicule['nom']), ENT_QUOTES, 'UTF-8') ?>" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="hidden" name="id" value="<?= (int) $typeVehicule['id'] ?>">
                <input type="text" name="nom" value="<?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-8">
                <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                <button formaction="/index.php?controller=manager_assets&action=type_delete" type="submit" data-confirm="Supprimer ce type ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-xl font-semibold">Postes par type</h2>
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            <input id="postes-search" type="search" placeholder="Rechercher un poste..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
            <select id="postes-type-filter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-56">
                <option value="">Tous les types</option>
                <?php foreach ($typesVehicules as $typeVehicule): ?>
                    <option value="<?= (int) $typeVehicule['id'] ?>"><?= htmlspecialchars($typeVehicule['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

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
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-2 w-full">Ajouter</button>
    </form>

    <div id="postes-list" class="space-y-2">
        <?php foreach ($postes as $poste): ?>
            <form method="post" action="/index.php?controller=manager_assets&action=poste_save" data-poste-name="<?= htmlspecialchars(strtolower((string) $poste['nom'] . ' ' . (string) $poste['code']), ENT_QUOTES, 'UTF-8') ?>" data-poste-type-id="<?= (int) $poste['type_vehicule_id'] ?>" class="grid grid-cols-1 md:grid-cols-12 gap-2">
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
                <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                <button formaction="/index.php?controller=manager_assets&action=poste_delete" type="submit" data-confirm="Supprimer ce poste ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<script>
    (function () {
        const typesSearch = document.getElementById('types-search');
        const typesRows = Array.from(document.querySelectorAll('#types-list form[data-type-name]'));
        const postesSearch = document.getElementById('postes-search');
        const postesTypeFilter = document.getElementById('postes-type-filter');
        const postesRows = Array.from(document.querySelectorAll('#postes-list form[data-poste-name]'));

        function filterTypes() {
            const q = (typesSearch.value || '').trim().toLowerCase();
            typesRows.forEach(function (row) {
                const hay = row.dataset.typeName || '';
                row.style.display = hay.includes(q) ? '' : 'none';
            });
        }

        function filterPostes() {
            const q = (postesSearch.value || '').trim().toLowerCase();
            const typeId = postesTypeFilter.value || '';
            postesRows.forEach(function (row) {
                const hay = row.dataset.posteName || '';
                const rowTypeId = row.dataset.posteTypeId || '';
                const okText = hay.includes(q);
                const okType = typeId === '' || rowTypeId === typeId;
                row.style.display = okText && okType ? '' : 'none';
            });
        }

        if (typesSearch) typesSearch.addEventListener('input', filterTypes);
        if (postesSearch) postesSearch.addEventListener('input', filterPostes);
        if (postesTypeFilter) postesTypeFilter.addEventListener('change', filterPostes);

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
                if (!submitter) return;

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
