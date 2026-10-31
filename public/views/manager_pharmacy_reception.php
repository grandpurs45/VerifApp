<?php

declare(strict_types=1);

$pageTitle = 'Reception pharmacie - VerifApp';
$pageHeading = 'Reception de commande';
$pageSubtitle = 'Entrees de stock pharmacie';
$pageBackUrl = '/index.php?controller=manager_pharmacy&action=index';
$pageBackLabel = 'Retour au stock';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (!$isAvailable): ?>
    <section class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">Module pharmacie indisponible.</section>
<?php endif; ?>

<?php if ($success === 'receive_saved'): ?>
    <section role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">
        Reception enregistree. Les stocks ont ete mis a jour.
        <a href="/index.php?controller=manager_pharmacy&action=index" class="ml-2 font-semibold underline">Voir le stock</a>
    </section>
<?php elseif ($error !== ''): ?>
    <section role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <?= $error === 'receive_invalid'
            ? 'Renseigne au moins un article avec une quantite recue superieure a zero.'
            : 'Impossible d enregistrer cette reception. Verifie les articles et les quantites.' ?>
    </section>
<?php endif; ?>

<style>
.rx-layout{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:24px;align-items:start}
.rx-main{min-width:0;background:white;border:1px solid #e2e8f0;border-radius:8px}
.rx-search{padding:20px;position:relative;border-bottom:1px solid #e2e8f0}
.rx-label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#0f172a}
.rx-input{width:100%;min-width:0;border:1px solid #cbd5e1;background:white;border-radius:6px;padding:10px 12px;font-size:14px;color:#0f172a}
.rx-input:focus{outline:2px solid #0284c7;outline-offset:2px}
.rx-options{position:absolute;z-index:20;left:20px;right:20px;top:calc(100% - 12px);max-height:260px;overflow:auto;background:white;border:1px solid #cbd5e1;border-radius:6px;box-shadow:0 12px 24px #0f172a1a}
.rx-option{display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;padding:12px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:14px}
.rx-option:hover,.rx-option:focus-visible{background:#f0f9ff}
.rx-option-name{min-width:0;font-weight:600;overflow-wrap:anywhere}
.rx-option-stock{flex-shrink:0;font-size:12px;color:#64748b}
.rx-columns,.rx-line{display:grid;grid-template-columns:minmax(0,1fr) 120px 120px 36px;gap:12px;align-items:center}
.rx-columns{padding:12px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b}
.rx-line{padding:16px 20px;border-bottom:1px solid #e2e8f0}
.rx-line:last-child{border-bottom:0}
.rx-name{font-size:14px;font-weight:700;overflow-wrap:anywhere}
.rx-stock{margin-top:4px;font-size:12px;color:#64748b}
.rx-mobile-label{display:none;margin-bottom:6px;font-size:12px;color:#64748b}
.rx-after{font-size:14px;font-weight:700;color:#047857;overflow-wrap:anywhere}
.rx-remove{width:36px;height:36px;border-radius:6px;font-size:23px;color:#64748b}
.rx-remove:hover{background:#fef2f2;color:#b91c1c}
.rx-empty{padding:48px 20px;text-align:center;font-size:14px;color:#64748b}
.rx-summary{display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding:16px 0;border-bottom:1px solid #cbd5e1;font-size:14px}
.rx-summary output{text-align:right;font-weight:700;overflow-wrap:anywhere}
.rx-submit{width:100%;padding:12px 16px;background:#0f172a;border-radius:6px;color:white;font-size:14px;font-weight:700}
.rx-submit:disabled{background:#cbd5e1;color:#64748b;cursor:not-allowed}
.rx-layout [hidden]{display:none!important}
@media(max-width:1100px){.rx-layout{grid-template-columns:minmax(0,1fr)}.rx-aside{border-top:1px solid #cbd5e1;padding-top:20px}}
@media(max-width:640px){
.rx-columns{display:none}.rx-search{padding:16px}.rx-options{left:16px;right:16px}
.rx-line{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 36px;padding:16px}
.rx-article{grid-column:1/3}.rx-remove{grid-column:3;grid-row:1}
.rx-quantity{grid-column:1}.rx-projection{grid-column:2/4}.rx-mobile-label{display:block}
.rx-option{align-items:flex-start;flex-direction:column;gap:4px}
}
</style>
<?php if (($receptionArticles ?? []) === []): ?>
    <section class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-600">Aucun article disponible pour r&eacute;ception.</section>
<?php else: ?>
<form method="post" action="/index.php?controller=manager_pharmacy&action=order_receive" id="orderReceptionForm" class="rx-layout">
    <section class="rx-main" aria-label="Articles recus">
        <div class="rx-search">
            <label for="orderReceptionSearch" class="rx-label">Articles re&ccedil;us</label>
            <input id="orderReceptionSearch" type="search" placeholder="Rechercher un article par son nom..." autocomplete="off" class="rx-input" aria-controls="orderReceptionSuggestions" aria-expanded="false">
            <div id="orderReceptionSuggestions" class="rx-options" hidden>
                <?php foreach ($receptionArticles as $article): ?>
                    <?php
                    $name = (string) ($article['nom'] ?? '');
                    $unit = (string) ($article['unite'] ?? 'u');
                    ?>
                    <button type="button" class="rx-option" data-reception-option
                        data-article-id="<?= (int) $article['id'] ?>"
                        data-article-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        data-article-unit="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>"
                        data-article-stock="<?= (float) ($article['stock_actuel'] ?? 0) ?>">
                        <span class="rx-option-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?><?= (int) ($article['actif'] ?? 0) === 1 ? '' : ' (inactif)' ?></span>
                        <span class="rx-option-stock">Stock : <?= htmlspecialchars((string) ($article['stock_actuel'] ?? 0) . ' ' . $unit, ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&nbsp; +</span></span>
                    </button>
                <?php endforeach; ?>
                <p id="orderReceptionNoResults" class="p-4 text-sm text-slate-500" hidden>Aucun article disponible pour cette recherche.</p>
            </div>
        </div>
        <div id="orderReceptionColumns" class="rx-columns" aria-hidden="true" hidden><span>Article / stock actuel</span><span>Quantit&eacute; re&ccedil;ue</span><span>Stock apr&egrave;s</span><span></span></div>
        <div id="orderReceptionLines"></div>
        <div id="orderReceptionEmpty" class="rx-empty"><strong class="block text-slate-700">Aucun article s&eacute;lectionn&eacute;</strong><span class="mt-1 block">R&eacute;ception en pr&eacute;paration</span></div>
    </section>
    <aside class="rx-aside min-w-0" aria-label="Recapitulatif de reception">
        <h2 class="text-base font-bold">R&eacute;capitulatif</h2>
        <div class="rx-summary"><span class="text-slate-600">Articles</span><output id="orderReceptionCount" aria-live="polite">0</output></div>
        <div class="rx-summary"><span class="text-slate-600">Quantit&eacute;s re&ccedil;ues</span><output id="orderReceptionTotal">&mdash;</output></div>
        <button type="submit" id="orderReceptionSubmit" class="rx-submit mt-5" disabled>Enregistrer la r&eacute;ception</button>
    </aside>
</form>
<template id="orderReceptionLineTemplate">
    <div class="rx-line" data-reception-line>
        <input type="hidden" name="article_id_reception[]" data-line-article-id>
        <div class="rx-article"><p class="rx-name" data-line-name></p><p class="rx-stock" data-line-stock></p></div>
        <label class="rx-quantity"><span class="rx-mobile-label">Quantit&eacute; re&ccedil;ue</span><input type="number" min="1" step="1" name="quantite_reception[]" required value="1" inputmode="numeric" class="rx-input" data-line-quantity></label>
        <div class="rx-projection"><span class="rx-mobile-label">Stock apr&egrave;s r&eacute;ception</span><output class="rx-after" data-line-after></output></div>
        <button type="button" class="rx-remove" title="Retirer cet article" data-line-remove aria-label="Retirer cet article">&times;</button>
    </div>
</template>
<script>
(() => {
    const form = document.getElementById('orderReceptionForm');
    const search = document.getElementById('orderReceptionSearch');
    const suggestions = document.getElementById('orderReceptionSuggestions');
    const options = Array.from(suggestions.querySelectorAll('[data-reception-option]'));
    const lines = document.getElementById('orderReceptionLines');
    const template = document.getElementById('orderReceptionLineTemplate');
    const submit = document.getElementById('orderReceptionSubmit');
    const selected = new Map();
    const numbers = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
    const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const quantityLabel = (quantity, unit) => numbers.format(quantity) + ' ' + unit;
    const closeSuggestions = () => {
        suggestions.hidden = true;
        search.setAttribute('aria-expanded', 'false');
    };
    const filterSuggestions = () => {
        const query = normalize(search.value.trim());
        let matches = 0;
        options.forEach((option) => {
            option.hidden = selected.has(option.dataset.articleId) || !normalize(option.dataset.articleName).includes(query);
            if (!option.hidden) matches++;
        });
        document.getElementById('orderReceptionNoResults').hidden = matches > 0;
        suggestions.hidden = false;
        search.setAttribute('aria-expanded', 'true');
    };
    const refresh = () => {
        const totals = new Map();
        let valid = selected.size > 0;
        selected.forEach(({ row, option }) => {
            const input = row.querySelector('[data-line-quantity]');
            const quantity = input.valueAsNumber;
            const lineValid = input.checkValidity() && Number.isFinite(quantity);
            valid = valid && lineValid;
            row.querySelector('[data-line-after]').textContent = lineValid
                ? quantityLabel(Number(option.dataset.articleStock) + quantity, option.dataset.articleUnit) : '-';
            if (lineValid) totals.set(option.dataset.articleUnit, (totals.get(option.dataset.articleUnit) || 0) + quantity);
        });
        document.getElementById('orderReceptionCount').value = String(selected.size);
        document.getElementById('orderReceptionTotal').value = Array.from(totals, ([unit, quantity]) => quantityLabel(quantity, unit)).join(' / ') || '-';
        document.getElementById('orderReceptionColumns').hidden = selected.size === 0;
        document.getElementById('orderReceptionEmpty').hidden = selected.size > 0;
        submit.disabled = !valid;
    };
    const addLine = (option) => {
        const id = option.dataset.articleId;
        if (selected.has(id)) return;
        const row = template.content.firstElementChild.cloneNode(true);
        const input = row.querySelector('[data-line-quantity]');
        row.querySelector('[data-line-article-id]').value = id;
        row.querySelector('[data-line-name]').textContent = option.dataset.articleName;
        row.querySelector('[data-line-stock]').textContent = 'Stock actuel : ' + quantityLabel(Number(option.dataset.articleStock), option.dataset.articleUnit);
        input.setAttribute('aria-label', 'Quantite recue - ' + option.dataset.articleName);
        row.querySelector('[data-line-remove]').setAttribute('aria-label', 'Retirer ' + option.dataset.articleName);
        row.querySelector('[data-line-remove]').addEventListener('click', () => {
            selected.delete(id);
            row.remove();
            refresh();
            search.focus();
        });
        input.addEventListener('input', refresh);
        selected.set(id, { row, option });
        lines.appendChild(row);
        search.value = '';
        closeSuggestions();
        refresh();
        input.focus();
        input.select();
    };
    options.forEach((option) => option.addEventListener('click', () => addLine(option)));
    search.addEventListener('focus', filterSuggestions);
    search.addEventListener('input', filterSuggestions);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeSuggestions();
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            filterSuggestions();
            options.find((option) => !option.hidden)?.focus();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            filterSuggestions();
            const matches = options.filter((option) => !option.hidden);
            if (matches.length === 1) addLine(matches[0]);
        }
    });
    suggestions.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { search.focus(); closeSuggestions(); }
    });
    document.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== search) closeSuggestions();
    });
    form.addEventListener('submit', (event) => {
        if (selected.size === 0) { event.preventDefault(); search.focus(); }
    });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
