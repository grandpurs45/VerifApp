<?php

declare(strict_types=1);

$pageTitle = 'Detail inventaire pharmacie - VerifApp';
$pageHeading = 'Detail inventaire';
$pageSubtitle = 'Controle des ecarts et application au stock.';
$pageBackUrl = '/index.php?controller=manager_pharmacy&action=inventories';
$pageBackLabel = 'Retour inventaires';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($success === 'inventory_applied'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Inventaire applique au stock avec succes.
    </section>
<?php elseif ($error === 'inventory_already_applied'): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Cet inventaire a deja ete applique.
    </section>
<?php elseif ($error === 'inventory_apply_failed'): ?>
    <section class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 text-sm">
        Application impossible.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-5">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Date</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($inventory['cree_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Declarant</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($inventory['cree_par'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Lignes en ecart</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= (int) ($inventory['lignes_ecart'] ?? 0) ?> / <?= (int) ($inventory['total_lignes'] ?? 0) ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Etat</p>
            <?php $isApplied = !empty($inventory['applique_le']); ?>
            <p class="mt-1 text-sm font-semibold <?= $isApplied ? 'text-emerald-700' : 'text-amber-700' ?>">
                <?= $isApplied ? 'Applique' : 'Non applique' ?>
            </p>
            <?php if ($isApplied): ?>
                <p class="mt-1 text-xs text-slate-500">
                    <?= htmlspecialchars((string) ($inventory['applique_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($inventory['applique_par'])): ?>
                        par <?= htmlspecialchars((string) ($inventory['applique_par'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </article>
    </div>

    <?php if (!empty($inventory['note'])): ?>
        <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
            Note: <?= htmlspecialchars((string) ($inventory['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <a href="/index.php?controller=manager_pharmacy&action=inventories" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            Retour liste
        </a>
        <?php if (!$isApplied): ?>
            <form method="post" action="/index.php?controller=manager_pharmacy&action=inventory_apply">
                <input type="hidden" name="inventory_id" value="<?= (int) ($inventory['id'] ?? 0) ?>">
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800" onclick="return window.confirm('Appliquer cet inventaire au stock ? Cette action mettra a jour les quantites des articles references.');">
                    Appliquer au stock
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-xl font-bold">Lignes inventaire</h2>
    <?php if ($lines === []): ?>
        <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucune ligne.
        </p>
    <?php else: ?>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3">Article</th>
                        <th class="py-2 pr-3">Unite</th>
                        <th class="py-2 pr-3">Theorique</th>
                        <th class="py-2 pr-3">Compte</th>
                        <th class="py-2 pr-3">Ecart</th>
                        <th class="py-2 pr-3">Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $line): ?>
                    <?php $diff = (int) round((float) ($line['ecart'] ?? 0)); ?>
                    <tr class="border-b border-slate-100">
                        <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) ($line['article_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2 pr-3"><?= htmlspecialchars((string) ($line['article_unite'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2 pr-3"><?= (int) round((float) ($line['stock_theorique'] ?? 0)) ?></td>
                        <td class="py-2 pr-3"><?= (int) round((float) ($line['stock_compte'] ?? 0)) ?></td>
                        <td class="py-2 pr-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $diff === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $diff > 0 ? '+' : '' ?><?= $diff ?>
                            </span>
                        </td>
                        <td class="py-2 pr-3"><?= htmlspecialchars((string) ($line['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
