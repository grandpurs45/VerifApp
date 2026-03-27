<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacie - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-7xl mx-auto p-4 md:p-8 space-y-6">
        <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-5 md:p-6 shadow">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <a href="/index.php?controller=manager&action=dashboard" class="text-xs text-slate-300 hover:text-white"><- Retour dashboard</a>
                    <h1 class="text-3xl font-extrabold mt-2">Pharmacie</h1>
                    <p class="text-slate-200 mt-1">Gestion du stock et suivi des sorties terrain.</p>
                </div>
                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <?php if (!$isAvailable): ?>
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
                Module non initialise. Lance la migration <code>016_create_pharmacy_module.sql</code>.
            </section>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && (string) $_GET['success'] === 'article_saved'): ?>
            <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
                Article enregistre.
            </section>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <section class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 text-sm">
                Action impossible. Verifie les champs saisis.
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <article class="rounded-2xl bg-white shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Articles actifs</p>
                <p class="text-3xl font-extrabold mt-1"><?= (int) ($stats['total_articles'] ?? 0) ?></p>
            </article>
            <article class="rounded-2xl bg-red-50 border border-red-200 shadow p-4">
                <p class="text-xs uppercase tracking-wide text-red-700">En alerte stock</p>
                <p class="text-3xl font-extrabold mt-1 text-red-700"><?= (int) ($stats['alert_articles'] ?? 0) ?></p>
            </article>
            <article class="rounded-2xl bg-white shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Sorties (7 jours)</p>
                <p class="text-3xl font-extrabold mt-1"><?= (int) ($stats['outputs_last_7_days'] ?? 0) ?></p>
            </article>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-xl font-bold">Ajouter un article</h2>
            <form method="post" action="/index.php?controller=manager_pharmacy&action=article_save" class="mt-3 grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" required placeholder="Nom article" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-4">
                <input type="text" name="unite" required placeholder="Unite (u, boite, ml...)" value="u" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                <input type="number" min="0" step="0.01" name="stock_actuel" required placeholder="Stock" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                <input type="number" min="0" step="0.01" name="seuil_alerte" placeholder="Seuil alerte" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" class="md:col-span-12 rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">Ajouter article</button>
            </form>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-xl font-bold">Articles existants</h2>
                <p class="text-xs text-slate-500"><?= count($articles) ?> article(s)</p>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3">Nom</th>
                            <th class="py-2 pr-3">Unite</th>
                            <th class="py-2 pr-3">Stock</th>
                            <th class="py-2 pr-3">Seuil</th>
                            <th class="py-2 pr-3">Etat</th>
                            <th class="py-2 pr-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <?php
                            $isAlert = $article['seuil_alerte'] !== null && (float) $article['stock_actuel'] <= (float) $article['seuil_alerte'];
                            ?>
                            <tr class="border-b border-slate-100 <?= $isAlert ? 'bg-red-50/60' : '' ?>">
                                <td class="py-2 pr-3" colspan="6">
                                    <form method="post" action="/index.php?controller=manager_pharmacy&action=article_save" class="grid grid-cols-12 gap-2 items-center">
                                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                        <input type="text" name="nom" value="<?= htmlspecialchars((string) $article['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-4">
                                        <input type="text" name="unite" value="<?= htmlspecialchars((string) $article['unite'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-1">
                                        <input type="number" min="0" step="0.01" name="stock_actuel" value="<?= htmlspecialchars((string) $article['stock_actuel'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-2">
                                        <input type="number" min="0" step="0.01" name="seuil_alerte" value="<?= htmlspecialchars((string) ($article['seuil_alerte'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-2">
                                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-1">
                                            <option value="1" <?= (int) ($article['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                                            <option value="0" <?= (int) ($article['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactif</option>
                                        </select>
                                        <button type="submit" class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm font-semibold col-span-2">Enregistrer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-xl font-bold mb-3">Dernieres sorties</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3">Date</th>
                            <th class="py-2 pr-3">Article</th>
                            <th class="py-2 pr-3">Quantite</th>
                            <th class="py-2 pr-3">Declarant</th>
                            <th class="py-2 pr-3">Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($movements === []): ?>
                            <tr>
                                <td class="py-3 text-slate-500" colspan="5">Aucune sortie enregistree pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($movements as $movement): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3 whitespace-nowrap"><?= htmlspecialchars((string) $movement['cree_le'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) $movement['article_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 pr-3">
                                    <?= htmlspecialchars((string) $movement['quantite'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= htmlspecialchars((string) ($movement['article_unite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-2 pr-3"><?= htmlspecialchars((string) ($movement['declarant'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 pr-3"><?= htmlspecialchars((string) ($movement['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
