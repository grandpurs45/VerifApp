<?php

declare(strict_types=1);

$pageTitle = 'Sorties pharmacie - VerifApp';
$pageHeading = 'Sorties pharmacie';
$pageSubtitle = 'Historique detaille des sorties de stock avec filtres.';
$pageBackUrl = '/index.php?controller=manager_pharmacy&action=index';
$pageBackLabel = 'Retour pharmacie';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (!$isAvailable): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Module non initialise. Lance la migration <code>016_create_pharmacy_module.sql</code>.
    </section>
<?php endif; ?>

<?php if ($success === 'ack_saved'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Sortie acquittee.
    </section>
<?php elseif ($success === 'order_saved'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Commande marquee avec succes.
    </section>
<?php elseif ($error !== ''): ?>
    <section class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 text-sm">
        <?php if ($error === 'ack_invalid'): ?>
            Sortie invalide pour acquittement.
        <?php elseif ($error === 'ack_failed'): ?>
            Impossible d acquitter cette sortie.
        <?php elseif ($error === 'order_failed'): ?>
            Impossible d enregistrer la commande.
        <?php else: ?>
            Action impossible.
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-xl font-bold">Synthese depuis derniere commande</h2>
            <p class="text-sm text-slate-600 mt-1">
                <?php if ($lastOrder !== null): ?>
                    Derniere commande: <strong><?= htmlspecialchars((string) ($lastOrder['commande_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (trim((string) ($lastOrder['cree_par'] ?? '')) !== ''): ?>
                        par <?= htmlspecialchars((string) ($lastOrder['cree_par'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                <?php else: ?>
                    Aucune commande enregistree pour cette caserne.
                <?php endif; ?>
            </p>
        </div>
        <form method="post" action="/index.php?controller=manager_pharmacy&action=order_mark" class="flex flex-wrap items-center gap-2">
            <input type="text" name="note" placeholder="Note commande (optionnel)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Commande passee</button>
        </form>
    </div>

    <?php if ($summarySinceLastOrder === []): ?>
        <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucune sortie depuis la derniere commande.
        </p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3">Article</th>
                        <th class="py-2 pr-3">Quantite a recommander</th>
                        <th class="py-2 pr-3">Lignes sorties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summarySinceLastOrder as $line): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) ($line['article_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-2 pr-3">
                                <?= htmlspecialchars((string) (int) round((float) ($line['quantite_totale'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) ($line['article_unite'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-2 pr-3"><?= (int) ($line['lignes'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-6 gap-2">
        <input type="hidden" name="controller" value="manager_pharmacy">
        <input type="hidden" name="action" value="outputs">

        <input type="date" name="date_from" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Date debut">
        <input type="date" name="date_to" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Date fin">
        <input type="text" name="article" value="<?= htmlspecialchars((string) ($filters['article'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Nom article">
        <input type="text" name="declarant" value="<?= htmlspecialchars((string) ($filters['declarant'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Declarant">
        <select name="ack_status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="pending" <?= (string) ($filters['ack_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>A acquitter</option>
            <option value="ack" <?= (string) ($filters['ack_status'] ?? '') === 'ack' ? 'selected' : '' ?>>Deja acquittees</option>
            <option value="all" <?= (string) ($filters['ack_status'] ?? '') === 'all' ? 'selected' : '' ?>>Toutes</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Filtrer</button>
            <a href="/index.php?controller=manager_pharmacy&action=outputs" class="flex-1 rounded-xl border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700">Reset</a>
        </div>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-xl font-bold">Resultats</h2>
        <p class="text-xs text-slate-500"><?= count($movementGroups) ?> sortie(s)</p>
    </div>

    <?php if ($movementGroups === []): ?>
        <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucune sortie trouvee avec ces filtres.
        </p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($movementGroups as $group): ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-slate-900">
                                Sortie du <?= htmlspecialchars((string) ($group['cree_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if ((int) ($group['acquitte'] ?? 0) === 1): ?>
                                <p class="text-xs text-emerald-700 font-semibold">
                                    Acquittee le <?= htmlspecialchars((string) ($group['acquitte_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (trim((string) ($group['acquitte_par'] ?? '')) !== ''): ?>
                                        par <?= htmlspecialchars((string) ($group['acquitte_par'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                <?= (int) ($group['lignes'] ?? 0) ?> ligne(s)
                            </span>
                            <?php if ((int) ($group['acquitte'] ?? 0) === 1): ?>
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Prise en compte
                                </span>
                            <?php else: ?>
                                <form method="post" action="/index.php?controller=manager_pharmacy&action=output_acknowledge">
                                    <input type="hidden" name="sortie_key" value="<?= htmlspecialchars((string) ($group['sortie_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                                        Acquitter
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">
                        Declarant: <strong><?= htmlspecialchars((string) ($group['declarant'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </p>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500 border-b border-slate-200">
                                    <th class="py-2 pr-3">Article</th>
                                    <th class="py-2 pr-3">Quantite</th>
                                    <th class="py-2 pr-3">Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($group['items'] ?? []) as $item): ?>
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) ($item['article_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="py-2 pr-3">
                                            <?= htmlspecialchars((string) ($item['quantite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            <?= htmlspecialchars((string) ($item['article_unite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="py-2 pr-3"><?= htmlspecialchars((string) ($item['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
