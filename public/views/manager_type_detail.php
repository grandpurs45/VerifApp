<?php

declare(strict_types=1);

$successMap = [
    'poste_created' => 'Poste cree.',
    'poste_updated' => 'Poste modifie.',
    'poste_deleted' => 'Poste supprime.',
];

$errorMap = [
    'invalid_poste' => 'Donnees poste invalides.',
    'poste_save_failed' => 'Impossible d enregistrer le poste.',
    'poste_delete_failed' => 'Suppression poste impossible (contraintes).',
    'poste_in_use' => 'Suppression impossible: ce poste est deja utilise par du materiel ou des verifications.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;

$typeName = (string) ($typeVehicule['nom'] ?? '');
$typeId = (int) ($typeVehicule['id'] ?? 0);

$pageTitle = 'Type d engin - VerifApp';
$pageHeading = 'Type d engin : ' . $typeName;
$pageSubtitle = 'Edition des postes standards de ce type uniquement.';
$pageBackUrl = '/index.php?controller=manager_assets&action=types';
$pageBackLabel = 'Retour types';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($successMessage !== null || $errorMessage !== null): ?>
    <section id="manager-toast" class="fixed top-4 right-4 z-50 max-w-sm rounded-xl border p-4 text-sm shadow-lg <?= $errorMessage !== null ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
        <?= htmlspecialchars((string) ($errorMessage ?? $successMessage), ENT_QUOTES, 'UTF-8') ?>
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <a href="/index.php?controller=manager_assets&action=types" class="rounded-xl border border-slate-300 bg-slate-100 text-slate-900 px-3 py-2 text-sm font-semibold">
                Retour types
            </a>
            <h2 class="text-xl font-semibold">Postes du type <?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <input id="postes-search" type="search" placeholder="Rechercher un poste..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-full md:w-72">
    </div>

    <form method="post" action="/index.php?controller=manager_assets&action=poste_save" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="type_vehicule_id" value="<?= $typeId ?>">
        <input type="hidden" name="return_type_id" value="<?= $typeId ?>">
        <input type="text" name="nom" placeholder="Nom poste" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-5">
        <input
            type="text"
            name="code"
            placeholder="Code poste"
            required
            maxlength="10"
            pattern="[A-Z0-9_-]{1,10}"
            title="10 caracteres max, sans espaces, en majuscules"
            class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-4 uppercase"
            style="text-transform: uppercase;"
        >
        <button type="submit" data-loading-label="Ajout..." class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold md:col-span-3 w-full">Ajouter</button>
    </form>

    <div id="postes-list" class="space-y-2">
        <?php foreach ($postes as $poste): ?>
            <form method="post" action="/index.php?controller=manager_assets&action=poste_save" data-poste-name="<?= htmlspecialchars(strtolower((string) $poste['nom'] . ' ' . (string) $poste['code']), ENT_QUOTES, 'UTF-8') ?>" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="hidden" name="id" value="<?= (int) $poste['id'] ?>">
                <input type="hidden" name="type_vehicule_id" value="<?= $typeId ?>">
                <input type="hidden" name="return_type_id" value="<?= $typeId ?>">
                <input type="text" name="nom" value="<?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-5">
                <input
                    type="text"
                    name="code"
                    value="<?= htmlspecialchars($poste['code'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                    maxlength="10"
                    pattern="[A-Z0-9_-]{1,10}"
                    title="10 caracteres max, sans espaces, en majuscules"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3 uppercase"
                    style="text-transform: uppercase;"
                >
                <button type="submit" data-loading-label="Maj..." class="rounded-xl bg-slate-900 text-white px-3 py-2 text-sm md:col-span-2 w-full">Modifier</button>
                <button formaction="/index.php?controller=manager_assets&action=poste_delete" type="submit" data-confirm="Supprimer ce poste ?" data-loading-label="Suppression..." class="rounded-xl bg-red-600 text-white px-3 py-2 text-sm md:col-span-2 w-full">Supprimer</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<script>
    (function () {
        const postesSearch = document.getElementById('postes-search');
        const postesRows = Array.from(document.querySelectorAll('#postes-list form[data-poste-name]'));

        function filterPostes() {
            const q = (postesSearch.value || '').trim().toLowerCase();
            postesRows.forEach(function (row) {
                const hay = row.dataset.posteName || '';
                row.style.display = hay.includes(q) ? '' : 'none';
            });
        }

        if (postesSearch) postesSearch.addEventListener('input', filterPostes);

        function normalizePosteCode(value) {
            return (value || '')
                .toUpperCase()
                .replace(/\s+/g, '')
                .replace(/[^A-Z0-9_-]/g, '')
                .slice(0, 10);
        }

        document.querySelectorAll('input[name="code"]').forEach(function (input) {
            input.addEventListener('input', function () {
                const normalized = normalizePosteCode(input.value);
                if (input.value !== normalized) {
                    input.value = normalized;
                }
            });
        });

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
