<?php

declare(strict_types=1);

$pageTitle = 'Inventaires pharmacie - VerifApp';
$pageHeading = 'Inventaires pharmacie';
$pageSubtitle = 'Comparer le stock compte au stock theorique et tracer les ecarts.';
$pageBackUrl = '/index.php?controller=manager_pharmacy&action=index';
$pageBackLabel = 'Retour stock pharmacie';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (!$isAvailable): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Module pharmacie non initialise.
    </section>
<?php endif; ?>

<?php if (!$inventoryAvailable): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Module inventaire non initialise. Lance la migration <code>028_create_pharmacy_inventory_module.sql</code>.
    </section>
<?php endif; ?>

<?php if ($success === 'inventory_saved'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Inventaire enregistre.
    </section>
<?php elseif ($error !== ''): ?>
    <section class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 text-sm">
        <?php if ($error === 'inventory_unavailable'): ?>
            Module inventaire indisponible.
        <?php elseif ($error === 'inventory_missing_values'): ?>
            Renseigne une quantite comptee pour chaque article.
        <?php elseif ($error === 'inventory_not_found'): ?>
            Inventaire introuvable.
        <?php elseif ($error === 'inventory_save_failed'): ?>
            Inventaire non enregistre. Verifie les quantites saisies.
        <?php else: ?>
            Action impossible.
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <article class="rounded-2xl bg-white shadow p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Articles actifs</p>
        <p class="text-3xl font-extrabold mt-1"><?= count($articles) ?></p>
    </article>
    <article class="rounded-2xl bg-white shadow p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Inventaires recents</p>
        <p class="text-3xl font-extrabold mt-1"><?= count($inventories) ?></p>
    </article>
    <article class="rounded-2xl bg-white shadow p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Dernier inventaire</p>
        <p class="text-lg font-bold mt-1">
            <?= htmlspecialchars((string) ($inventories[0]['cree_le'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </p>
    </article>
</section>

<section class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-700">
            Terrain mobile:
            <strong>realiser l inventaire directement au smartphone.</strong>
        </p>
        <a
            href="<?= htmlspecialchars((string) ($inventoryMobileUrl ?? '/index.php?controller=pharmacy&action=access&next=inventory_form'), ENT_QUOTES, 'UTF-8') ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
        >
            Ouvrir inventaire mobile
        </a>
    </div>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold">Nouvel inventaire</h2>
        <input id="inventoryArticleSearch" type="search" placeholder="Rechercher un article..." class="w-72 max-w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
    </div>

    <?php if ($articles === []): ?>
        <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucun article actif a inventorier.
        </p>
    <?php else: ?>
        <form method="post" action="/index.php?controller=manager_pharmacy&action=inventory_save" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" name="declarant" required value="<?= htmlspecialchars((string) ($managerUser['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom declarant" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="note" placeholder="Note inventaire (optionnel)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full table-fixed text-sm">
                    <colgroup>
                        <col style="width:34%">
                        <col style="width:11%">
                        <col style="width:14%">
                        <col style="width:14%">
                        <col style="width:27%">
                    </colgroup>
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 px-2">Article</th>
                            <th class="py-2 px-2 text-center">Unite</th>
                            <th class="py-2 px-2 text-center">Theorique</th>
                            <th class="py-2 px-2 text-center">Compte</th>
                            <th class="py-2 px-2">Commentaire ecart</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr data-inventory-row data-article-name="<?= htmlspecialchars(mb_strtolower((string) ($article['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" class="border-b border-slate-100">
                                <td class="px-2 py-2">
                                    <input type="hidden" name="article_id[]" value="<?= (int) ($article['id'] ?? 0) ?>">
                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($article['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <?= htmlspecialchars((string) ($article['unite'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <?= (int) round((float) ($article['stock_actuel'] ?? 0)) ?>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input type="number" name="stock_compte[]" min="0" step="1" required class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm" value="" placeholder="Compter">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" name="commentaire[]" placeholder="Precision si ecart" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">Materiel en trop (hors liste)</h3>
                    <button type="button" id="addExtraInventoryLine" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        + Ajouter une ligne
                    </button>
                </div>
                <div id="extraInventoryLines" class="space-y-2"></div>
                <p class="mt-2 text-xs text-slate-500">
                    Renseigne ces lignes uniquement si tu trouves du materiel non reference dans la liste.
                </p>
            </section>

            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">
                Enregistrer inventaire
            </button>
        </form>
    <?php endif; ?>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <h2 class="text-xl font-bold">Historique inventaires</h2>
    <?php if ($inventories === []): ?>
        <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucun inventaire enregistre.
        </p>
    <?php else: ?>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3">Date</th>
                            <th class="py-2 pr-3">Declarant</th>
                            <th class="py-2 pr-3">Lignes</th>
                            <th class="py-2 pr-3">Lignes en ecart</th>
                            <th class="py-2 pr-3">Ecart total</th>
                            <th class="py-2 pr-3">Note</th>
                            <th class="py-2 pr-3">Etat</th>
                            <th class="py-2 pr-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inventories as $inventory): ?>
                        <?php $hasDiff = abs((float) ($inventory['ecart_total'] ?? 0)) > 0.00001; ?>
                        <?php $isApplied = !empty($inventory['applique_le']); ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) ($inventory['cree_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-2 pr-3"><?= htmlspecialchars((string) ($inventory['cree_par'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-2 pr-3"><?= (int) ($inventory['total_lignes'] ?? 0) ?></td>
                            <td class="py-2 pr-3"><?= (int) ($inventory['lignes_ecart'] ?? 0) ?></td>
                            <td class="py-2 pr-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $hasDiff ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                    <?= (int) round((float) ($inventory['ecart_total'] ?? 0)) ?>
                                </span>
                            </td>
                            <td class="py-2 pr-3"><?= htmlspecialchars((string) ($inventory['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-2 pr-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $isApplied ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                    <?= $isApplied ? 'Applique' : 'Non applique' ?>
                                </span>
                            </td>
                            <td class="py-2 pr-3">
                                <a href="/index.php?controller=manager_pharmacy&action=inventory_show&id=<?= (int) ($inventory['id'] ?? 0) ?>" class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Voir detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>

<script>
    (function () {
        const searchInput = document.getElementById('inventoryArticleSearch');
        const rows = Array.from(document.querySelectorAll('[data-inventory-row]'));
        const extraContainer = document.getElementById('extraInventoryLines');
        const addExtraButton = document.getElementById('addExtraInventoryLine');
        if (!searchInput || rows.length === 0) {
            return;
        }
        const normalize = (value) => (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        const apply = () => {
            const needle = normalize(searchInput.value.trim());
            rows.forEach((row) => {
                const name = normalize(row.getAttribute('data-article-name') || '');
                const match = needle === '' || name.includes(needle);
                row.classList.toggle('hidden', !match);
            });
        };
        searchInput.addEventListener('input', apply);

        const buildExtraLine = () => {
            const wrap = document.createElement('div');
            wrap.className = 'grid grid-cols-1 md:grid-cols-[1.6fr_0.6fr_1fr_auto] gap-2 items-start';
            wrap.innerHTML = `
                <input type="text" name="article_libre_nom[]" placeholder="Nom materiel hors liste" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <input type="number" name="stock_compte_libre[]" min="0" step="1" placeholder="Quantite" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="commentaire_libre[]" placeholder="Commentaire (optionnel)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <button type="button" class="rounded-xl bg-red-100 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-200">Supprimer</button>
            `;
            const removeButton = wrap.querySelector('button');
            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    wrap.remove();
                });
            }

            return wrap;
        };

        if (extraContainer && addExtraButton) {
            addExtraButton.addEventListener('click', () => {
                extraContainer.appendChild(buildExtraLine());
            });
        }
    })();
</script>
