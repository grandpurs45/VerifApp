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

<section class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-700">
            Acces rapide au terrain:
            <strong>ouvrir le formulaire de sortie de materiel (QR pharmacie).</strong>
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/index.php?controller=manager_pharmacy&action=outputs" class="inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Voir sorties</a>
            <a href="/index.php?controller=manager_pharmacy&action=inventories" class="inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Module inventaire</a>
            <a
                href="<?= htmlspecialchars((string) ($pharmacyFormUrl ?? '/index.php?controller=pharmacy&action=access'), ENT_QUOTES, 'UTF-8') ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
            >
                Ouvrir formulaire sortie
            </a>
        </div>
    </div>
</section>

<section class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <article class="rounded-2xl bg-white shadow p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Articles actifs</p>
        <p class="text-3xl font-extrabold mt-1"><?= (int) ($stats['total_articles'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl bg-red-50 border border-red-200 shadow p-4">
        <p class="text-xs uppercase tracking-wide text-red-700">En alerte stock</p>
        <p class="text-3xl font-extrabold mt-1 text-red-700"><?= (int) ($stats['alert_articles'] ?? 0) ?></p>
        <p class="mt-2 text-xs font-semibold text-red-700">Priorite de reappro immediate</p>
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
        <input type="text" name="nom" required placeholder="Nom article" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
        <input type="text" name="unite" required placeholder="Unite (u, boite, ml...)" value="u" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
        <input type="number" min="0" step="1" name="stock_actuel" required placeholder="Stock (entier)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
        <input type="number" min="0" step="1" name="seuil_alerte" placeholder="Seuil alerte (entier)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
        <select name="motif_sortie_obligatoire" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
            <option value="0">Sortie libre</option>
            <option value="1">Motif obligatoire</option>
        </select>
        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
        </select>
        <button type="submit" class="md:col-span-12 rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">Ajouter article</button>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold">Articles existants</h2>
        <div class="flex items-center gap-2">
            <button
                type="button"
                id="pharmacyAlertOnlyToggle"
                class="rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-800"
            >
                Voir erreurs uniquement
            </button>
            <input
                id="pharmacyArticleSearch"
                type="search"
                placeholder="Rechercher un article..."
                class="w-64 max-w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
            >
            <p class="text-xs text-slate-500"><span id="pharmacyArticleCount"><?= count($articles) ?></span> article(s)</p>
        </div>
    </div>

    <div class="mt-3 overflow-x-auto">
        <table class="min-w-[980px] w-full table-fixed text-sm">
            <colgroup>
                <col style="width:27%">
                <col style="width:9%">
                <col style="width:13%">
                <col style="width:13%">
                <col style="width:13%">
                <col style="width:12%">
                <col style="width:13%">
            </colgroup>
            <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-2 px-2">Nom</th>
                    <th class="py-2 px-2 text-center">Unite</th>
                    <th class="py-2 px-2 text-center">Quantite</th>
                    <th class="py-2 px-2 text-center">Seuil</th>
                    <th class="py-2 px-2 text-center">Sortie CR</th>
                    <th class="py-2 px-2 text-center">Etat</th>
                    <th class="py-2 px-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <?php
                    $isInactive = (int) ($article['actif'] ?? 0) !== 1;
                    $isAlert = (int) ($article['actif'] ?? 0) === 1
                        && $article['seuil_alerte'] !== null
                        && (float) $article['seuil_alerte'] > 0
                        && (float) $article['stock_actuel'] < (float) $article['seuil_alerte'];
                    $formId = 'pharmacy-article-' . (int) $article['id'];
                    ?>
                    <tr
                        class="align-middle border-b border-slate-100 <?= $isAlert ? 'bg-red-100/70' : '' ?> <?= $isInactive ? 'bg-slate-100/90' : '' ?>"
                        data-article-row
                        data-article-name="<?= htmlspecialchars(mb_strtolower((string) $article['nom']), ENT_QUOTES, 'UTF-8') ?>"
                        data-article-alert="<?= $isAlert ? '1' : '0' ?>"
                    >
                        <td class="px-2 py-2">
                            <form id="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" method="post" action="/index.php?controller=manager_pharmacy&action=article_save">
                                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                            </form>
                            <?php if ($isAlert): ?>
                                <span class="mb-2 inline-flex items-center gap-2 rounded-lg border border-red-300 bg-red-200/80 px-2 py-1 text-xs font-bold uppercase tracking-wide text-red-800">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-600"></span>
                                    Alerte stock
                                </span>
                            <?php elseif ($isInactive): ?>
                                <span class="mb-2 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-200/90 px-2 py-1 text-xs font-bold uppercase tracking-wide text-slate-700">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-slate-500"></span>
                                    Inactif
                                </span>
                            <?php endif; ?>
                            <input form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" type="text" name="nom" value="<?= htmlspecialchars((string) $article['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">
                                    Sorties 6m: <?= (int) ($article['sorties_6m'] ?? 0) ?>
                                </span>
                                <span>
                                    Derniere sortie: <?= htmlspecialchars((string) ($article['derniere_sortie_le'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" type="text" name="unite" value="<?= htmlspecialchars((string) $article['unite'], ENT_QUOTES, 'UTF-8') ?>" required class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" type="number" min="0" step="1" name="stock_actuel" value="<?= htmlspecialchars((string) (int) round((float) $article['stock_actuel']), ENT_QUOTES, 'UTF-8') ?>" required class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" type="number" min="0" step="1" name="seuil_alerte" value="<?= htmlspecialchars((string) ($article['seuil_alerte'] !== null ? (int) round((float) $article['seuil_alerte']) : ''), ENT_QUOTES, 'UTF-8') ?>" class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </td>
                        <td class="px-2 py-2 text-center">
                            <select form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" name="motif_sortie_obligatoire" class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="0" <?= (int) ($article['motif_sortie_obligatoire'] ?? 0) === 0 ? 'selected' : '' ?>>Non</option>
                                <option value="1" <?= (int) ($article['motif_sortie_obligatoire'] ?? 0) === 1 ? 'selected' : '' ?>>Oui</option>
                            </select>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <select form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" name="actif" class="mx-auto w-[92%] rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="1" <?= (int) ($article['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= (int) ($article['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <?php $forceMenuId = 'pharmacy-force-delete-menu-' . (int) $article['id']; ?>
                            <div class="mx-auto flex w-full flex-col gap-1">
                                <button form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" type="submit" class="w-full rounded-xl bg-slate-800 text-white px-2 py-2 text-xs font-semibold">Enregistrer</button>
                                <div class="relative">
                                    <div class="grid grid-cols-[1fr_auto] gap-1">
                                        <form method="post" action="/index.php?controller=manager_pharmacy&action=article_delete" onsubmit="return window.confirm('Supprimer cet article ? Si deja utilise, la suppression sera refusee.');">
                                            <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                            <button type="submit" class="w-full rounded-l-xl rounded-r-md bg-red-600 text-white px-2 py-2 text-xs font-semibold hover:bg-red-700">Supprimer</button>
                                        </form>
                                        <button
                                            type="button"
                                            class="rounded-l-md rounded-r-xl bg-red-700 px-2 py-2 text-xs font-bold text-white hover:bg-red-800"
                                            data-delete-menu-toggle="<?= htmlspecialchars($forceMenuId, ENT_QUOTES, 'UTF-8') ?>"
                                            aria-expanded="false"
                                            aria-controls="<?= htmlspecialchars($forceMenuId, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            ▼
                                        </button>
                                    </div>
                                    <div id="<?= htmlspecialchars($forceMenuId, ENT_QUOTES, 'UTF-8') ?>" class="hidden absolute right-0 z-20 mt-1 min-w-[150px] rounded-xl border border-red-200 bg-white p-2 shadow-lg" data-delete-menu>
                                        <form method="post" action="/index.php?controller=manager_pharmacy&action=article_force_delete" onsubmit="return window.confirm('ATTENTION: suppression forcee irreversible. L article et ses sorties/inventaires lies seront supprimes. Continuer ?');">
                                            <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                            <button type="submit" class="w-full rounded-lg bg-red-50 px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-100">Supprimer tout</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

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

<script>
    (function () {
        const searchInput = document.getElementById('pharmacyArticleSearch');
        const alertOnlyButton = document.getElementById('pharmacyAlertOnlyToggle');
        const rows = Array.from(document.querySelectorAll('[data-article-row]'));
        const count = document.getElementById('pharmacyArticleCount');
        if (!searchInput || rows.length === 0 || !count) {
            return;
        }
        let alertOnly = false;

        const normalize = (value) => (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        const applyFilter = () => {
            const needle = normalize(searchInput.value.trim());
            let visible = 0;
            rows.forEach((row) => {
                const name = normalize(row.getAttribute('data-article-name') || '');
                const isAlert = (row.getAttribute('data-article-alert') || '0') === '1';
                const matchSearch = needle === '' || name.includes(needle);
                const matchAlert = !alertOnly || isAlert;
                const match = matchSearch && matchAlert;
                row.classList.toggle('hidden', !match);
                if (match) {
                    visible += 1;
                }
            });
            count.textContent = String(visible);
        };

        searchInput.addEventListener('input', applyFilter);
        if (alertOnlyButton) {
            alertOnlyButton.addEventListener('click', function () {
                alertOnly = !alertOnly;
                alertOnlyButton.textContent = alertOnly ? 'Afficher tous les articles' : 'Voir erreurs uniquement';
                alertOnlyButton.classList.toggle('bg-red-600', alertOnly);
                alertOnlyButton.classList.toggle('text-white', alertOnly);
                alertOnlyButton.classList.toggle('border-red-600', alertOnly);
                alertOnlyButton.classList.toggle('bg-red-50', !alertOnly);
                alertOnlyButton.classList.toggle('text-red-800', !alertOnly);
                alertOnlyButton.classList.toggle('border-red-300', !alertOnly);
                applyFilter();
            });
        }

        const menuToggles = Array.from(document.querySelectorAll('[data-delete-menu-toggle]'));
        const menus = Array.from(document.querySelectorAll('[data-delete-menu]'));
        const closeMenus = () => {
            menus.forEach((menu) => menu.classList.add('hidden'));
            menuToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
        };

        menuToggles.forEach((toggle) => {
            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                const targetId = toggle.getAttribute('data-delete-menu-toggle');
                if (!targetId) return;
                const menu = document.getElementById(targetId);
                if (!menu) return;

                const isHidden = menu.classList.contains('hidden');
                closeMenus();
                if (isHidden) {
                    menu.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });

        menus.forEach((menu) => {
            menu.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', closeMenus);
    })();
</script>
