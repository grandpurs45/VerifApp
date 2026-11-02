<?php

declare(strict_types=1);

$pageTitle = 'Pharmacie - VerifApp';
$pageHeading = 'Pharmacie';
$pageSubtitle = 'Gestion du stock et suivi des sorties terrain.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (!$isAvailable): ?>
    <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm">
        Module non initialise. Lance la migration <code>016_create_pharmacy_module.sql</code>.
    </section>
<?php endif; ?>

<?php if (isset($_GET['success']) && (string) $_GET['success'] === 'article_saved'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Article enregistre.
    </section>
<?php elseif (isset($_GET['success']) && (string) $_GET['success'] === 'article_status_saved'): ?>
    <section role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">Statut de l'article mis a jour.</section>
<?php elseif (isset($_GET['success']) && (string) $_GET['success'] === 'article_deleted'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Article supprime.
    </section>
<?php elseif (isset($_GET['success']) && (string) $_GET['success'] === 'article_force_deleted'): ?>
    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-800 text-sm">
        Article supprime avec son historique lie.
    </section>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <section class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 text-sm">
        <?php $errorCode = (string) $_GET['error']; ?>
        <?php if ($errorCode === 'article_delete_has_movements'): ?>
            Suppression simple impossible: article deja utilise dans des sorties. Passe-le en inactif puis utilise le menu `Supprimer tout`.
        <?php elseif ($errorCode === 'article_delete_has_inventories'): ?>
            Suppression simple impossible: article present dans l historique des inventaires. Passe-le en inactif puis utilise le menu `Supprimer tout`.
        <?php elseif ($errorCode === 'article_delete_not_found' || $errorCode === 'article_delete_invalid'): ?>
            Article introuvable ou invalide.
        <?php elseif ($errorCode === 'article_duplicate'): ?>
            Article deja existant dans cette caserne (nom en doublon).
        <?php elseif ($errorCode === 'article_delete_failed'): ?>
            Suppression impossible.
        <?php elseif ($errorCode === 'article_force_delete_must_inactive'): ?>
            Suppression forcee refusee: passe d abord l article en inactif.
        <?php elseif ($errorCode === 'article_force_delete_failed'): ?>
            Suppression forcee impossible.
        <?php else: ?>
            Action impossible. Verifie les champs saisis.
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
$pharmacyEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pharmacyQuantity = static fn (mixed $value): string => rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
$pendingArticleForm = null;
if (in_array((string) ($_GET['error'] ?? ''), ['invalid_article', 'article_duplicate', 'article_save_failed'], true)
    && is_array($articleFormState ?? null)
    && (int) ($articleFormState['caserne_id'] ?? 0) === (int) ($_SESSION['manager_user']['caserne_id'] ?? 0)) {
    $pendingArticleForm = $articleFormState;
}
?>
<link rel="stylesheet" href="/assets/pharmacy-stock.css">

<div class="ps-quick-actions">
    <a href="/index.php?controller=manager_pharmacy&action=reception" class="ps-button ps-primary">R&eacute;ceptionner une commande</a>
    <a href="<?= $pharmacyEscape($pharmacyFormUrl ?? '/index.php?controller=pharmacy&action=access') ?>" target="_blank" rel="noopener noreferrer" class="ps-button">Formulaire de sortie terrain</a>
</div>

<section class="ps-metrics" aria-label="Indicateurs du stock pharmacie">
    <button type="button" class="ps-metric" data-stock-filter="active" aria-pressed="false">
        <span class="ps-metric-label">Articles actifs</span><strong><?= (int) ($stats['total_articles'] ?? 0) ?></strong><span>En service</span>
    </button>
    <button type="button" class="ps-metric ps-metric-alert" data-stock-filter="alert" aria-pressed="false">
        <span class="ps-metric-label">Sous le seuil</span><strong><?= (int) ($stats['alert_articles'] ?? 0) ?></strong><span>&Agrave; r&eacute;approvisionner</span>
    </button>
    <button type="button" class="ps-metric ps-metric-warning" data-stock-filter="warning" aria-pressed="false">
        <span class="ps-metric-label">Au seuil</span><strong><?= (int) ($stats['warning_articles'] ?? 0) ?></strong><span>&Agrave; surveiller</span>
    </button>
    <a href="/index.php?controller=manager_pharmacy&action=outputs" class="ps-metric">
        <span class="ps-metric-label">Sorties sur 7 jours</span><strong><?= (int) ($stats['outputs_last_7_days'] ?? 0) ?></strong><span>Voir l'historique</span>
    </a>
</section>

<section class="ps-stock" aria-labelledby="pharmacyStockTitle">
    <div class="ps-toolbar">
        <h2 id="pharmacyStockTitle">Stock pharmacie</h2>
        <div class="ps-toolbar-controls">
            <input id="pharmacyArticleSearch" type="search" placeholder="Rechercher un article..." aria-label="Rechercher un article" class="ps-input">
            <select id="pharmacyStockFilter" class="ps-input" aria-label="Filtrer le stock">
                <option value="all">Tous les articles</option>
                <option value="active">Articles actifs</option>
                <option value="alert">Sous le seuil</option>
                <option value="warning">Au seuil</option>
                <option value="attention">Sous le seuil + au seuil</option>
                <option value="inactive">Articles inactifs</option>
            </select>
            <button type="button" id="pharmacyAddArticle" class="ps-button ps-primary" aria-haspopup="dialog" aria-controls="pharmacyArticleDialog"><img src="/assets/icons/plus.svg" alt="" class="ps-icon ps-icon-light">Ajouter un article</button>
        </div>
    </div>
    <p class="ps-result-count" id="pharmacyArticleCount" role="status"><?= count($articles) ?> article(s)</p>
    <div class="ps-table-scroll">
        <table class="ps-table">
            <caption class="ps-sr-only">Stock des articles de la caserne active</caption>
            <thead><tr>
                <?php foreach (['name' => 'Article', 'quantity' => 'Stock disponible', 'threshold' => 'Seuil', 'state' => 'Statut'] as $key => $label): ?>
                    <th scope="col" aria-sort="none"><button type="button" data-sort-key="<?= $key ?>" class="ps-sort"><?= $label ?><span data-sort-indicator aria-hidden="true">&varr;</span></button></th>
                <?php endforeach; ?>
                <th scope="col" class="ps-actions-heading">Actions</th>
            </tr></thead>
            <tbody id="pharmacyArticleRows">
            <?php foreach ($articles as $article): ?>
                <?php
                $active = (int) ($article['actif'] ?? 0) === 1;
                $stock = (float) ($article['stock_actuel'] ?? 0);
                $threshold = (float) ($article['seuil_alerte'] ?? 0);
                $alert = $active && $threshold > 0 && $stock < $threshold;
                $warning = $active && !$alert && $threshold > 0 && (int) ($article['surveiller_seuil'] ?? 0) === 1 && $stock === $threshold;
                $status = !$active ? 'inactive' : ($alert ? 'alert' : ($warning ? 'warning' : 'normal'));
                $statusLabel = !$active ? 'Inactif' : ($alert ? 'Sous le seuil' : ($warning ? 'Au seuil' : ($threshold > 0 ? 'Stock suffisant' : 'Sans seuil')));
                $lastOutput = trim((string) ($article['derniere_sortie_le'] ?? ''));
                $lastOutputLabel = '';
                if ($lastOutput !== '') {
                    try { $lastOutputLabel = (new DateTimeImmutable($lastOutput))->format('d/m/Y'); }
                    catch (Throwable) { $lastOutputLabel = $lastOutput; }
                }
                $articleData = [
                    'id' => (int) $article['id'],
                    'nom' => (string) $article['nom'],
                    'unite' => (string) $article['unite'],
                    'stock_actuel' => (string) $stock,
                    'seuil_alerte' => $threshold > 0 ? (string) $threshold : '',
                    'surveiller_seuil' => (int) ($article['surveiller_seuil'] ?? 0),
                    'motif_sortie_obligatoire' => (int) ($article['motif_sortie_obligatoire'] ?? 0),
                    'actif' => $active ? 1 : 0,
                ];
                $encodedArticle = $pharmacyEscape(json_encode($articleData, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
                ?>
                <tr data-article-row data-name="<?= $pharmacyEscape($article['nom']) ?>" data-quantity="<?= $stock ?>" data-threshold="<?= $threshold > 0 ? $threshold : -1 ?>" data-state="<?= $status ?>" data-active="<?= $active ? '1' : '0' ?>" class="<?= $active ? '' : 'ps-row-inactive' ?>">
                    <td class="ps-article-cell">
                        <p class="ps-article-name"><?= $pharmacyEscape($article['nom']) ?></p>
                        <p class="ps-activity"><span><?= (int) ($article['sorties_6m'] ?? 0) ?> sorties sur 6 mois</span><?php if ($lastOutputLabel !== ''): ?> <span aria-hidden="true">&middot;</span> <time title="<?= $pharmacyEscape($lastOutput) ?>" datetime="<?= $pharmacyEscape(str_replace(' ', 'T', $lastOutput)) ?>">Derni&egrave;re : <?= $pharmacyEscape($lastOutputLabel) ?></time><?php endif; ?></p>
                    </td>
                    <td data-label="Stock disponible" class="ps-stock-cell"><strong class="<?= $alert ? 'ps-text-alert' : ($warning ? 'ps-text-warning' : '') ?>"><?= $pharmacyQuantity($stock) ?></strong> <span class="ps-unit"><?= $pharmacyEscape($article['unite']) ?></span></td>
                    <td data-label="Seuil" class="ps-threshold-cell"><?= $threshold > 0 ? $pharmacyQuantity($threshold) . ' ' . $pharmacyEscape($article['unite']) : '&mdash;' ?></td>
                    <td class="ps-status-cell"><span class="ps-badge ps-badge-<?= $status ?>"><?= $statusLabel ?></span></td>
                    <td class="ps-actions-cell">
                        <button type="button" data-edit-article data-article="<?= $encodedArticle ?>" class="ps-icon-button" title="Modifier cet article" aria-label="Modifier <?= $pharmacyEscape($article['nom']) ?>" aria-haspopup="dialog" aria-controls="pharmacyArticleDialog"><img src="/assets/icons/pencil.svg" class="ps-icon" alt=""></button>
                        <button type="button" data-article-actions data-article="<?= $encodedArticle ?>" class="ps-icon-button" title="Autres actions" aria-label="Actions pour <?= $pharmacyEscape($article['nom']) ?>" aria-controls="pharmacyArticleActions" aria-expanded="false"><img src="/assets/icons/ellipsis-vertical.svg" class="ps-icon" alt=""></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div id="pharmacyStockEmpty" class="ps-empty" <?= count($articles) > 0 ? 'hidden' : '' ?>>
            <p>Aucun article<?= count($articles) > 0 ? ' ne correspond aux filtres.' : ' enregistre.' ?></p>
            <?php if (count($articles) > 0): ?><button type="button" id="pharmacyResetFilters" class="ps-button">R&eacute;initialiser les filtres</button><?php endif; ?>
        </div>
    </div>
</section>

<dialog id="pharmacyArticleDialog" aria-labelledby="pharmacyArticleDialogTitle" class="ps-dialog">
    <div class="ps-dialog-heading"><h2 id="pharmacyArticleDialogTitle">Ajouter un article</h2><button type="button" data-close-article-dialog class="ps-icon-button" aria-label="Fermer le formulaire" title="Fermer"><img src="/assets/icons/x.svg" class="ps-icon" alt=""></button></div>
    <form id="pharmacyArticleForm" method="post" action="/index.php?controller=manager_pharmacy&action=article_save">
        <input type="hidden" name="id" value="0">
        <p id="pharmacyArticleFormError" role="alert" class="ps-form-error" hidden></p>
        <div class="ps-form-grid">
            <label class="ps-form-wide">Nom de l'article<input type="text" name="nom" required class="ps-input" autofocus></label>
            <label>Unit&eacute;<input type="text" name="unite" value="u" required class="ps-input"></label>
            <label><span id="pharmacyStockFieldLabel">Stock initial</span><input type="number" name="stock_actuel" min="0" step="1" required class="ps-input"></label>
            <label>Seuil d'alerte<input type="number" name="seuil_alerte" min="0" step="1" class="ps-input"></label>
            <label class="ps-check"><input type="checkbox" name="surveiller_seuil" value="1">Surveiller le seuil</label>
            <label class="ps-check"><input type="checkbox" name="motif_sortie_obligatoire" value="1">Motif de sortie obligatoire</label>
            <label class="ps-check"><input type="checkbox" name="actif" value="1" checked>Article actif</label>
        </div>
        <div class="ps-dialog-footer"><button type="button" data-close-article-dialog class="ps-button">Annuler</button><button type="submit" id="pharmacyArticleSubmit" class="ps-button ps-primary">Ajouter l'article</button></div>
    </form>
</dialog>

<div id="pharmacyArticleActions" popover="auto" class="ps-action-menu">
    <p id="pharmacyActionName" class="ps-menu-title"></p>
    <form id="pharmacyArticleStateForm" method="post" action="/index.php?controller=manager_pharmacy&action=article_status">
        <input type="hidden" name="id"><input type="hidden" name="actif">
        <button type="submit" id="pharmacyArticleStateButton" class="ps-menu-button">D&eacute;sactiver</button>
    </form>
    <form id="pharmacyArticleDeleteForm" method="post" action="/index.php?controller=manager_pharmacy&action=article_delete">
        <input type="hidden" name="id"><button type="submit" class="ps-menu-button ps-text-alert">Supprimer l'article</button>
    </form>
    <form id="pharmacyArticleForceDeleteForm" method="post" action="/index.php?controller=manager_pharmacy&action=article_force_delete">
        <input type="hidden" name="id"><button type="submit" id="pharmacyArticleForceDeleteButton" class="ps-menu-button ps-text-alert">Supprimer avec l'historique</button>
    </form>
</div>

<script type="application/json" id="pharmacyPendingArticle"><?= json_encode(['article' => $pendingArticleForm, 'error' => (string) ($_GET['error'] ?? '')], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/assets/pharmacy-stock.js" defer></script>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-xl font-bold">Dernieres sorties (10)</h2>
        <a href="/index.php?controller=manager_pharmacy&action=outputs" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Voir tout + filtres</a>
    </div>

    <?php if ($movementGroups === []): ?>
        <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            Aucune sortie enregistree pour le moment.
        </p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($movementGroups as $group): ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-bold text-slate-900">
                            Sortie du <?= htmlspecialchars((string) ($group['cree_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            <?= (int) ($group['lignes'] ?? 0) ?> ligne(s)
                        </span>
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
