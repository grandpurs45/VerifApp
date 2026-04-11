<?php

declare(strict_types=1);

$errorMessage = null;
if ($errorCode === 'invalid') {
    $errorMessage = 'Saisie invalide. Verifie article et quantite.';
} elseif ($errorCode === 'stock') {
    $errorMessage = 'Article inactif ou donnees invalides.';
} elseif ($errorCode === 'declarant_required') {
    $errorMessage = 'Nom du declarant obligatoire.';
} elseif ($errorCode === 'other_comment_required') {
    $errorMessage = 'Pour "Autre", commentaire obligatoire (minimum 5 caracteres).';
} elseif ($errorCode === 'other_requires_migration') {
    $errorMessage = 'Option "Autre" indisponible: migration base requise (026_allow_free_label_outputs).';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sortie pharmacie - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <main class="mx-auto w-full max-w-md px-4 pb-10 pt-5">
        <header class="mb-5 rounded-3xl border border-slate-700/80 bg-slate-800/85 p-4 shadow-lg">
            <p class="text-xs uppercase tracking-[0.18em] text-amber-300">Pharmacie</p>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Declarer une sortie de stock</h1>
            <p class="mt-2 text-sm text-slate-300">Formulaire rapide via QR code.</p>
        </header>

        <?php if (!$isAvailable): ?>
            <section class="rounded-2xl border border-amber-300 bg-amber-100 p-4 text-sm text-amber-900">
                Module pharmacie non initialise.
            </section>
        <?php else: ?>
            <?php if ($success): ?>
                <section class="mb-4 rounded-2xl border border-emerald-300 bg-emerald-100 p-4 text-sm text-emerald-900">
                    <p class="font-bold">Sortie de materiels enregistree avec succes.</p>
                    <p class="mt-1">
                        <?= $successItems > 0 ? htmlspecialchars((string) $successItems, ENT_QUOTES, 'UTF-8') . ' article(s) pris en compte.' : 'La sortie a bien ete prise en compte.' ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <section class="mb-4 rounded-2xl border border-red-300 bg-red-100 p-4 text-sm text-red-900">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </section>
            <?php endif; ?>

            <?php if ($success): ?>
                <a href="/index.php?controller=pharmacy&action=form" class="block w-full rounded-2xl border border-slate-500 bg-slate-900 px-5 py-4 text-center text-base font-extrabold text-slate-100 shadow-lg active:scale-[0.99]">
                    Declarer une nouvelle sortie
                </a>
            <?php else: ?>
                <form method="post" action="/index.php?controller=pharmacy&action=save" class="rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg space-y-3" id="pharmacyForm">
                    <section class="space-y-2">
                        <label for="articleSearchMain" class="text-sm font-semibold text-slate-200">Ajouter un article</label>
                        <input
                            id="articleSearchMain"
                            type="text"
                            data-main-article-search
                            class="w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white"
                            placeholder="Tape le nom d'un article..."
                            autocomplete="off"
                        >
                        <div class="max-h-60 overflow-y-auto rounded-2xl border border-slate-500 bg-slate-950/80" data-main-article-list>
                            <?php foreach ($articles as $article): ?>
                                <?php
                                $articleName = (string) $article['nom'];
                                $articleStock = (string) $article['stock_actuel'];
                                $articleUnit = (string) $article['unite'];
                                $articleLabel = $articleName . ' (stock: ' . $articleStock . ' ' . $articleUnit . ')';
                                ?>
                                <button
                                    type="button"
                                    class="w-full border-b border-slate-800 px-4 py-3 text-left text-base text-slate-100 last:border-b-0 active:bg-slate-700"
                                    data-main-article-option
                                    data-value="<?= (int) $article['id'] ?>"
                                    data-label="<?= htmlspecialchars($articleLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-nom="<?= htmlspecialchars($articleName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-reason-required="<?= (int) ($article['motif_sortie_obligatoire'] ?? 0) === 1 ? '1' : '0' ?>"
                                    data-is-other="0"
                                >
                                    <?= htmlspecialchars($articleLabel, ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            <?php endforeach; ?>
                            <button
                                type="button"
                                class="w-full border-t border-slate-700 bg-slate-800/80 px-4 py-3 text-left text-base font-semibold text-amber-200 active:bg-slate-700"
                                data-main-article-option
                                data-value="other"
                                data-label="Autre (hors liste)"
                                data-nom="Autre (hors liste)"
                                data-reason-required="0"
                                data-is-other="1"
                            >
                                Autre (hors liste)
                            </button>
                        </div>
                        <p class="text-xs text-slate-300">Selection rapide: touche un article pour ajouter sa ligne de quantite.</p>
                    </section>

                    <div id="lineItems" class="space-y-3"></div>
                    <p id="noLineNotice" class="rounded-xl border border-amber-400/70 bg-amber-500/20 px-3 py-2 text-xs text-amber-100">
                        Aucun article selectionne.
                    </p>

                    <div>
                        <label for="declarant" class="text-sm font-semibold text-slate-200">Nom du declarant</label>
                        <input id="declarant" name="declarant" type="text" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Nom et prenom">
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-amber-300 px-5 py-4 text-base font-extrabold text-slate-900 shadow-lg active:scale-[0.99]">
                        Enregistrer les sorties
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php if ($isAvailable && !$success): ?>
        <template id="lineTemplate">
            <section class="rounded-2xl border border-slate-600 bg-slate-900/60 p-3 space-y-2" data-line>
                <div>
                    <p class="text-sm font-semibold text-slate-100" data-line-label></p>
                    <input type="hidden" name="article_id[]" required data-article-id>
                </div>
                <div>
                    <div data-quantity-wrap>
                    <label class="text-sm font-semibold text-slate-200">Quantite sortie</label>
                    <input name="quantite[]" type="number" min="1" step="1" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Ex: 2" data-quantity-input>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-200" data-motif-label>Motif (optionnel)</label>
                    <select name="motif[]" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" data-motif-select>
                        <option value="" data-motif-empty>Aucun</option>
                        <option value="perime">Materiel perime</option>
                        <option value="utilise">Materiel utilise en intervention</option>
                        <option value="perdu">Materiel perdu</option>
                    </select>
                    <p class="mt-1 hidden text-xs text-amber-200" data-motif-required-hint>Motif obligatoire pour cet article.</p>
                </div>
                <div class="hidden" data-intervention-wrap>
                    <label class="text-sm font-semibold text-slate-200">Numero intervention</label>
                    <input name="intervention_numero[]" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Ex: 2026-001245" data-intervention-input>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-200" data-comment-label>Commentaire (optionnel)</label>
                    <input name="commentaire_ligne[]" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Precision utile" data-comment-input>
                </div>
                <button type="button" class="w-full rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white active:scale-[0.99]" data-remove-line>
                    Supprimer cette ligne
                </button>
            </section>
        </template>

        <script>
            (function () {
                const lineItems = document.getElementById('lineItems');
                const template = document.getElementById('lineTemplate');
                const searchInput = document.querySelector('[data-main-article-search]');
                const mainOptions = Array.from(document.querySelectorAll('[data-main-article-option]'));
                const noLineNotice = document.getElementById('noLineNotice');

                function updateMainOptions(query) {
                    const search = (query || '').trim().toLowerCase();
                    mainOptions.forEach(function (option) {
                        const label = (option.dataset.label || '').toLowerCase();
                        const isVisible = search === '' || label.indexOf(search) !== -1;
                        option.classList.toggle('hidden', !isVisible);
                    });
                }

                function refreshNoLineNotice() {
                    if (!noLineNotice) {
                        return;
                    }
                    const lineCount = lineItems.querySelectorAll('[data-line]').length;
                    noLineNotice.classList.toggle('hidden', lineCount > 0);
                }

                function bindRemove(button) {
                    button.addEventListener('click', function () {
                        button.closest('[data-line]').remove();
                        refreshNoLineNotice();
                    });
                }

                function bindMotifBehavior(line, reasonRequired, isOther) {
                    const motifSelect = line.querySelector('[data-motif-select]');
                    const interventionWrap = line.querySelector('[data-intervention-wrap]');
                    const interventionInput = line.querySelector('[data-intervention-input]');
                    const motifHint = line.querySelector('[data-motif-required-hint]');
                    const motifLabel = line.querySelector('[data-motif-label]');
                    const motifContainer = motifSelect ? motifSelect.closest('div') : null;
                    const motifEmptyOption = motifSelect ? motifSelect.querySelector('[data-motif-empty]') : null;
                    const commentLabel = line.querySelector('[data-comment-label]');
                    const commentInput = line.querySelector('[data-comment-input]');
                    const quantityWrap = line.querySelector('[data-quantity-wrap]');
                    const quantityInput = line.querySelector('[data-quantity-input]');
                    if (!motifSelect || !interventionWrap || !interventionInput || !motifHint || !motifLabel || !motifContainer) {
                        return;
                    }

                    function update() {
                        if (isOther) {
                            if (quantityWrap) {
                                quantityWrap.classList.add('hidden');
                            }
                            if (quantityInput) {
                                quantityInput.required = false;
                                quantityInput.value = '1';
                            }
                            motifContainer.classList.add('hidden');
                            motifSelect.required = false;
                            motifSelect.value = '';
                            motifHint.classList.add('hidden');
                            interventionWrap.classList.add('hidden');
                            interventionInput.required = false;
                            interventionInput.value = '';
                            if (commentLabel) {
                                commentLabel.textContent = 'Commentaire (obligatoire min 5)';
                            }
                            if (commentInput) {
                                commentInput.required = true;
                                commentInput.minLength = 5;
                                commentInput.placeholder = 'Preciser l article sorti (min 5 caracteres)';
                            }
                            return;
                        }

                        if (reasonRequired) {
                            motifContainer.classList.remove('hidden');
                            motifSelect.required = true;
                            motifLabel.textContent = 'Motif (obligatoire)';
                            motifHint.classList.remove('hidden');
                            if (motifEmptyOption) {
                                motifEmptyOption.textContent = 'Selectionner un motif';
                            }
                        } else {
                            motifContainer.classList.add('hidden');
                            motifSelect.required = false;
                            motifSelect.value = '';
                            motifLabel.textContent = 'Motif (optionnel)';
                            motifHint.classList.add('hidden');
                            if (motifEmptyOption) {
                                motifEmptyOption.textContent = 'Aucun';
                            }
                        }

                        const isIntervention = motifSelect.value === 'utilise';
                        interventionWrap.classList.toggle('hidden', !isIntervention);
                        interventionInput.required = isIntervention;
                        if (!isIntervention) {
                            interventionInput.value = '';
                        }
                        if (quantityWrap) {
                            quantityWrap.classList.remove('hidden');
                        }
                        if (quantityInput) {
                            quantityInput.required = true;
                            if ((quantityInput.value || '').trim() === '') {
                                quantityInput.value = '';
                            }
                        }
                        if (commentLabel) {
                            commentLabel.textContent = 'Commentaire (optionnel)';
                        }
                        if (commentInput) {
                            commentInput.required = false;
                            commentInput.minLength = 0;
                            commentInput.placeholder = 'Precision utile';
                        }
                    }

                    motifSelect.addEventListener('change', update);
                    update();
                }

                function addLineFromArticle(articleId, articleLabel, articleName, reasonRequired, isOther) {
                    if (!lineItems || !template) {
                        return;
                    }

                    const existing = lineItems.querySelector('[data-article-id][value="' + articleId + '"]');
                    if (existing) {
                        const existingLine = existing.closest('[data-line]');
                        if (existingLine) {
                            const qty = existingLine.querySelector('[data-quantity-input]');
                            if (qty) {
                                qty.focus();
                            }
                        }
                        return;
                    }

                    const fragment = template.content.cloneNode(true);
                    const removeButton = fragment.querySelector('[data-remove-line]');
                    if (removeButton) {
                        bindRemove(removeButton);
                    }
                    const line = fragment.querySelector('[data-line]');
                    if (line) {
                        const hiddenInput = line.querySelector('[data-article-id]');
                        const label = line.querySelector('[data-line-label]');
                        if (hiddenInput) {
                            hiddenInput.value = articleId;
                        }
                        if (label) {
                            label.textContent = articleName || articleLabel;
                        }
                        bindMotifBehavior(line, reasonRequired, isOther);
                    }
                    lineItems.appendChild(fragment);
                    if (line) {
                        if (isOther) {
                            const comment = line.querySelector('[data-comment-input]');
                            if (comment) {
                                comment.focus();
                            }
                        } else {
                            const qty = line.querySelector('[data-quantity-input]');
                            if (qty) {
                                qty.focus();
                            }
                        }
                    }
                    refreshNoLineNotice();
                }

                mainOptions.forEach(function (option) {
                    option.addEventListener('click', function () {
                        const articleId = option.dataset.value || '';
                        const articleLabel = option.dataset.label || '';
                        const articleName = option.dataset.nom || articleLabel;
                        const reasonRequired = (option.dataset.reasonRequired || '0') === '1';
                        const isOther = (option.dataset.isOther || '0') === '1';
                        if (articleId === '') {
                            return;
                        }
                        addLineFromArticle(articleId, articleLabel, articleName, reasonRequired, isOther);
                        if (searchInput) {
                            searchInput.value = '';
                            updateMainOptions('');
                        }
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        updateMainOptions(searchInput.value || '');
                    });
                }

                refreshNoLineNotice();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
