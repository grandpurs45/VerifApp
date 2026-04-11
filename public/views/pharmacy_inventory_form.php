<?php

declare(strict_types=1);

$pageTitle = 'Inventaire pharmacie - VerifApp';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <main class="mx-auto max-w-md px-4 py-6 space-y-4">
        <header class="rounded-3xl border border-slate-700/80 bg-slate-800/85 p-4 shadow-lg">
            <p class="text-xs uppercase tracking-[0.18em] text-amber-300">Pharmacie</p>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Inventaire terrain</h1>
            <p class="mt-2 text-slate-300">Compter les articles et enregistrer les ecarts.</p>
        </header>

        <?php if (!$isAvailable): ?>
            <section class="rounded-2xl border border-amber-300 bg-amber-100 p-4 text-sm text-amber-900">
                Module pharmacie non initialise.
            </section>
        <?php elseif (!$inventoryAvailable): ?>
            <section class="rounded-2xl border border-amber-300 bg-amber-100 p-4 text-sm text-amber-900">
                Module inventaire non initialise.
            </section>
        <?php else: ?>
            <?php if ($success): ?>
                <section class="rounded-2xl border border-emerald-300 bg-emerald-100 p-4 text-sm text-emerald-900">
                    Inventaire enregistre (<?= (int) $savedItems ?> ligne(s)).
                </section>
            <?php elseif ($errorCode !== ''): ?>
                <section class="rounded-2xl border border-red-300 bg-red-100 p-4 text-sm text-red-900">
                    <?php if ($errorCode === 'declarant_required'): ?>
                        Nom du declarant requis.
                    <?php elseif ($errorCode === 'inventory_missing_values'): ?>
                        Renseigne une quantite pour chaque article.
                    <?php elseif ($errorCode === 'inventory_invalid_qty'): ?>
                        Quantite invalide (entier >= 0 attendu).
                    <?php else: ?>
                        Action impossible.
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <form method="post" action="/index.php?controller=pharmacy&action=inventory_save" class="rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg space-y-3" id="inventoryForm">
                <section class="sticky top-0 z-20 -mx-4 -mt-4 mb-1 rounded-t-3xl border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur">
                    <div class="flex items-center justify-between text-xs text-slate-300">
                        <span>Progression inventaire</span>
                        <strong id="inventoryProgressText">0 / <?= count($articles) ?></strong>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-700">
                        <div id="inventoryProgressBar" class="h-full w-0 rounded-full bg-amber-300 transition-all"></div>
                    </div>
                </section>

                <p class="rounded-xl border border-cyan-300/70 bg-cyan-400/15 px-3 py-2 text-xs text-cyan-100">
                    Inventaire complet: une quantite est obligatoire pour chaque article.
                </p>

                <div>
                    <label for="inventorySearch" class="text-sm font-semibold text-slate-200">Rechercher un article</label>
                    <input id="inventorySearch" type="search" placeholder="Tape le nom d un article..." class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" data-filter="all" class="rounded-xl border border-slate-500 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-100 active:scale-[0.99]">
                        Tous
                    </button>
                    <button type="button" data-filter="missing" class="rounded-xl border border-amber-400 bg-amber-400/10 px-3 py-2 text-sm font-semibold text-amber-200 active:scale-[0.99]">
                        A saisir
                    </button>
                </div>

                <div class="space-y-2" id="inventoryLines">
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $articleName = trim((string) ($article['nom'] ?? 'Article'));
                        $articleUnit = trim((string) ($article['unite'] ?? 'u'));
                        $theoretical = (int) round((float) ($article['stock_actuel'] ?? 0));
                        ?>
                        <section class="rounded-2xl border border-slate-600 bg-slate-900/60 p-3 space-y-2" data-inventory-line data-article-name="<?= htmlspecialchars(mb_strtolower($articleName), ENT_QUOTES, 'UTF-8') ?>" data-theoretical="<?= $theoretical ?>">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-slate-100"><?= htmlspecialchars($articleName, ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="rounded-full bg-slate-700 px-2 py-1 text-xs font-semibold text-slate-200">
                                    Theo: <?= $theoretical ?> <?= htmlspecialchars($articleUnit, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <input type="hidden" name="article_id[]" value="<?= (int) ($article['id'] ?? 0) ?>">
                            <label class="block text-sm font-semibold text-slate-200">
                                Quantite comptee
                                <input name="stock_compte[]" type="number" min="0" step="1" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-lg font-bold text-white" placeholder="Ex: 2">
                            </label>
                            <details class="rounded-xl border border-slate-700 bg-slate-900/50 px-3 py-2">
                                <summary class="cursor-pointer text-xs font-semibold text-slate-300">Ajouter un commentaire ecart (optionnel)</summary>
                                <input name="commentaire[]" type="text" class="mt-2 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Precision utile">
                            </details>
                        </section>
                    <?php endforeach; ?>
                </div>

                <section class="rounded-2xl border border-slate-600 bg-slate-900/40 p-3 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-100">Materiel en trop (hors liste)</p>
                        <button type="button" id="addExtraInventoryLine" class="rounded-xl border border-slate-500 px-3 py-2 text-xs font-semibold text-slate-200 active:scale-[0.99]">
                            + Ajouter
                        </button>
                    </div>
                    <div id="extraInventoryLines" class="space-y-2"></div>
                </section>

                <div>
                    <label for="declarant" class="text-sm font-semibold text-slate-200">Nom du declarant</label>
                    <input id="declarant" name="declarant" type="text" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Nom et prenom">
                </div>

                <div>
                    <label for="inventoryNote" class="text-sm font-semibold text-slate-200">Note (optionnel)</label>
                    <input id="inventoryNote" name="note" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Contexte inventaire">
                </div>

                <button type="submit" class="w-full rounded-2xl bg-amber-300 px-5 py-4 text-center text-xl font-extrabold text-slate-900 shadow-lg active:scale-[0.99]">
                    Enregistrer inventaire
                </button>
            </form>

            <template id="extraInventoryLineTemplate">
                <section class="rounded-2xl border border-slate-600 bg-slate-900/60 p-3 space-y-2" data-extra-line>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-bold text-slate-100">Materiel hors liste</p>
                        <button type="button" class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white active:scale-[0.99]" data-remove-extra-line>
                            Supprimer
                        </button>
                    </div>
                    <label class="block text-sm font-semibold text-slate-200">
                        Nom materiel
                        <input name="article_libre_nom[]" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Ex: Garrot adulte">
                    </label>
                    <label class="block text-sm font-semibold text-slate-200">
                        Quantite comptee
                        <input name="stock_compte_libre[]" type="number" min="0" step="1" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Ex: 1">
                    </label>
                    <label class="block text-sm font-semibold text-slate-200">
                        Commentaire (optionnel)
                        <input name="commentaire_libre[]" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Precision utile">
                    </label>
                </section>
            </template>
        <?php endif; ?>
    </main>

    <script>
        (function () {
            const form = document.getElementById('inventoryForm');
            if (!form) return;

            const search = document.getElementById('inventorySearch');
            const lineContainer = document.getElementById('inventoryLines');
            const extraContainer = document.getElementById('extraInventoryLines');
            const extraTemplate = document.getElementById('extraInventoryLineTemplate');
            const addExtraButton = document.getElementById('addExtraInventoryLine');
            const progressText = document.getElementById('inventoryProgressText');
            const progressBar = document.getElementById('inventoryProgressBar');
            const filterButtons = form.querySelectorAll('[data-filter]');
            if (!search || !lineContainer) return;

            const normalize = (v) => (v || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            let currentFilter = 'all';

            const applySearchAndFilter = () => {
                const needle = normalize(search.value.trim());
                lineContainer.querySelectorAll('[data-inventory-line]').forEach((line) => {
                    const name = normalize(line.getAttribute('data-article-name') || '');
                    const qtyInput = line.querySelector('input[name="stock_compte[]"]');
                    const hasQty = qtyInput && qtyInput.value.trim() !== '';
                    const matchSearch = needle === '' || name.includes(needle);
                    const matchFilter = currentFilter === 'missing' ? !hasQty : true;
                    line.classList.toggle('hidden', !(matchSearch && matchFilter));
                });
            };

            const refreshProgress = () => {
                const lines = Array.from(lineContainer.querySelectorAll('[data-inventory-line]'));
                const total = lines.length;
                const completed = lines.filter((line) => {
                    const qtyInput = line.querySelector('input[name="stock_compte[]"]');
                    return qtyInput && qtyInput.value.trim() !== '';
                }).length;

                lines.forEach((line) => {
                    const qtyInput = line.querySelector('input[name="stock_compte[]"]');
                    const hasQty = qtyInput && qtyInput.value.trim() !== '';
                    line.classList.toggle('border-red-500', !hasQty);
                    line.classList.toggle('bg-red-950/20', !hasQty);
                });

                if (progressText) {
                    progressText.textContent = completed + ' / ' + total;
                }
                if (progressBar) {
                    const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
                    progressBar.style.width = percent + '%';
                }
                applySearchAndFilter();
            };

            search.addEventListener('input', applySearchAndFilter);

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    currentFilter = button.getAttribute('data-filter') || 'all';
                    filterButtons.forEach((item) => {
                        item.classList.remove('ring-2', 'ring-cyan-300');
                    });
                    button.classList.add('ring-2', 'ring-cyan-300');
                    applySearchAndFilter();
                });
            });

            lineContainer.querySelectorAll('input[name="stock_compte[]"]').forEach((input) => {
                input.addEventListener('input', refreshProgress);
            });

            if (extraContainer && extraTemplate && addExtraButton) {
                addExtraButton.addEventListener('click', () => {
                    const fragment = extraTemplate.content.cloneNode(true);
                    const line = fragment.querySelector('[data-extra-line]');
                    if (!line) return;
                    line.querySelector('[data-remove-extra-line]')?.addEventListener('click', () => {
                        line.remove();
                    });
                    extraContainer.appendChild(line);
                });
            }

            form.addEventListener('submit', (event) => {
                const lines = lineContainer.querySelectorAll('[data-inventory-line]');
                for (const line of lines) {
                    const qty = line.querySelector('input[name="stock_compte[]"]');
                    if (!qty || qty.value.trim() === '') {
                        event.preventDefault();
                        window.alert('Renseigne une quantite pour chaque article.');
                        qty?.focus();
                        return;
                    }
                }

                const extraLines = extraContainer ? extraContainer.querySelectorAll('[data-extra-line]') : [];
                for (const line of extraLines) {
                    const extraName = line.querySelector('input[name="article_libre_nom[]"]');
                    const extraQty = line.querySelector('input[name="stock_compte_libre[]"]');
                    const hasAny =
                        (extraName && extraName.value.trim() !== '') ||
                        (extraQty && extraQty.value.trim() !== '') ||
                        (line.querySelector('input[name="commentaire_libre[]"]')?.value.trim() !== '');
                    if (!hasAny) {
                        continue;
                    }
                    if (!extraName || extraName.value.trim() === '' || !extraQty || extraQty.value.trim() === '') {
                        event.preventDefault();
                        window.alert('Renseigne nom et quantite pour chaque materiel en trop.');
                        (extraName && extraName.value.trim() === '' ? extraName : extraQty)?.focus();
                        return;
                    }
                }
            });

            refreshProgress();
        })();
    </script>
</body>
</html>
