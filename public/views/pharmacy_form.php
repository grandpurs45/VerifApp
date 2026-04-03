<?php

declare(strict_types=1);

$errorMessage = null;
if ($errorCode === 'invalid') {
    $errorMessage = 'Saisie invalide. Verifie article et quantite.';
} elseif ($errorCode === 'stock') {
    $errorMessage = 'Stock insuffisant ou article inactif.';
} elseif ($errorCode === 'declarant_required') {
    $errorMessage = 'Nom du declarant obligatoire.';
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
                    <div id="lineItems" class="space-y-3"></div>

                    <button type="button" id="addLineButton" class="w-full rounded-2xl border border-slate-500 bg-slate-900 px-5 py-3 text-sm font-bold text-slate-100 active:scale-[0.99]">
                        + Ajouter un article
                    </button>

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
                    <label class="text-sm font-semibold text-slate-200">Article</label>
                    <select name="article_id[]" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white">
                        <option value="">Selectionner...</option>
                        <?php foreach ($articles as $article): ?>
                            <option value="<?= (int) $article['id'] ?>">
                                <?= htmlspecialchars((string) $article['nom'], ENT_QUOTES, 'UTF-8') ?>
                                (stock: <?= htmlspecialchars((string) $article['stock_actuel'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) $article['unite'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-200">Quantite sortie</label>
                    <input name="quantite[]" type="number" min="1" step="1" required class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Ex: 2">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-200">Commentaire (optionnel)</label>
                    <input name="commentaire_ligne[]" type="text" class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white" placeholder="Precision utile">
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
                const addButton = document.getElementById('addLineButton');

                function bindRemove(button) {
                    button.addEventListener('click', function () {
                        const lines = lineItems.querySelectorAll('[data-line]');
                        if (lines.length <= 1) {
                            return;
                        }
                        button.closest('[data-line]').remove();
                    });
                }

                function addLine() {
                    if (!lineItems || !template) {
                        return;
                    }
                    const fragment = template.content.cloneNode(true);
                    const removeButton = fragment.querySelector('[data-remove-line]');
                    if (removeButton) {
                        bindRemove(removeButton);
                    }
                    lineItems.appendChild(fragment);
                }

                if (addButton) {
                    addButton.addEventListener('click', addLine);
                }

                addLine();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
