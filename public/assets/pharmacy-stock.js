(() => {
    'use strict';
    const dialog = document.getElementById('pharmacyArticleDialog');
    if (!dialog) return;
    const form = document.getElementById('pharmacyArticleForm');
    const fieldNames = ['id', 'nom', 'unite', 'stock_actuel', 'seuil_alerte', 'surveiller_seuil', 'motif_sortie_obligatoire', 'actif'];
    const defaults = { id: 0, nom: '', unite: 'u', stock_actuel: '', seuil_alerte: '', surveiller_seuil: 0, motif_sortie_obligatoire: 0, actif: 1 };
    const fillForm = (target, article) => {
        fieldNames.forEach((name) => {
            const input = target.elements.namedItem(name);
            if (input.type === 'checkbox') input.checked = String(article[name]) === '1';
            else input.value = article[name] ?? defaults[name];
        });
    };
    const openArticle = (article = defaults, error = '') => {
        const editing = Number(article.id) > 0;
        form.reset();
        fillForm(form, article);
        document.getElementById('pharmacyArticleDialogTitle').textContent = editing ? "Modifier l'article" : 'Ajouter un article';
        document.getElementById('pharmacyStockFieldLabel').textContent = editing ? 'Stock disponible' : 'Stock initial';
        document.getElementById('pharmacyArticleSubmit').textContent = editing ? 'Enregistrer' : "Ajouter l'article";
        const errorBox = document.getElementById('pharmacyArticleFormError');
        errorBox.hidden = error === '';
        errorBox.textContent = error === 'article_duplicate' ? 'Ce nom existe deja dans cette caserne.' : 'Enregistrement impossible. Verifie les champs saisis.';
        dialog.showModal();
    };
    document.getElementById('pharmacyAddArticle').addEventListener('click', () => openArticle());
    document.querySelectorAll('[data-edit-article]').forEach((button) => {
        button.addEventListener('click', () => openArticle(JSON.parse(button.dataset.article)));
    });
    dialog.querySelectorAll('[data-close-article-dialog]').forEach((button) => button.addEventListener('click', () => dialog.close()));

    const menu = document.getElementById('pharmacyArticleActions');
    const stateForm = document.getElementById('pharmacyArticleStateForm');
    const deleteForm = document.getElementById('pharmacyArticleDeleteForm');
    const forceDeleteForm = document.getElementById('pharmacyArticleForceDeleteForm');
    let menuTrigger = null;
    const closeMenu = () => {
        if (menu.matches(':popover-open')) {
            menu.hidePopover();
            menuTrigger?.setAttribute('aria-expanded', 'false');
        }
    };
    document.querySelectorAll('[data-article-actions]').forEach((button) => {
        button.addEventListener('click', () => {
            const wasOpen = menu.matches(':popover-open') && menuTrigger === button;
            closeMenu();
            if (wasOpen) return;
            const article = JSON.parse(button.dataset.article);
            stateForm.elements.namedItem('id').value = article.id;
            stateForm.elements.namedItem('actif').value = Number(article.actif) === 1 ? '0' : '1';
            deleteForm.elements.namedItem('id').value = article.id;
            forceDeleteForm.elements.namedItem('id').value = article.id;
            document.getElementById('pharmacyArticleStateButton').textContent = Number(article.actif) === 1 ? "Desactiver l'article" : "Reactiver l'article";
            document.getElementById('pharmacyActionName').textContent = article.nom;
            const forceButton = document.getElementById('pharmacyArticleForceDeleteButton');
            forceButton.disabled = Number(article.actif) === 1;
            forceButton.title = forceButton.disabled ? "Reserve aux articles inactifs" : "Supprimer l'article et son historique";
            menuTrigger = button;
            menu.showPopover();
            button.setAttribute('aria-expanded', 'true');
            const rect = button.getBoundingClientRect();
            menu.style.left = Math.max(8, Math.min(rect.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - 8)) + 'px';
            const top = rect.bottom + 6;
            menu.style.top = (top + menu.offsetHeight <= window.innerHeight - 8 ? top : Math.max(8, rect.top - menu.offsetHeight - 6)) + 'px';
            stateForm.querySelector('button').focus();
        });
    });
    menu.addEventListener('toggle', () => {
        if (!menu.matches(':popover-open') && menuTrigger) menuTrigger.setAttribute('aria-expanded', 'false');
    });
    window.addEventListener('resize', closeMenu);
    document.querySelector('.ps-table-scroll').addEventListener('scroll', closeMenu);
    deleteForm.addEventListener('submit', (event) => {
        if (!window.confirm('Supprimer cet article ? La suppression sera refusee si un historique existe.')) event.preventDefault();
    });
    forceDeleteForm.addEventListener('submit', (event) => {
        if (!window.confirm('Suppression irreversible : l article et ses sorties et inventaires lies seront supprimes. Continuer ?')) event.preventDefault();
    });

    const search = document.getElementById('pharmacyArticleSearch');
    const filter = document.getElementById('pharmacyStockFilter');
    const body = document.getElementById('pharmacyArticleRows');
    const rows = Array.from(body.querySelectorAll('[data-article-row]'));
    const metrics = Array.from(document.querySelectorAll('[data-stock-filter]'));
    const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const matchesMode = (row) => {
        const mode = filter.value;
        if (mode === 'all') return true;
        if (mode === 'active') return row.dataset.active === '1';
        if (mode === 'inactive') return row.dataset.active === '0';
        if (mode === 'attention') return ['alert', 'warning'].includes(row.dataset.state);
        return row.dataset.state === mode;
    };
    const applyFilter = () => {
        closeMenu();
        const query = normalize(search.value.trim());
        let count = 0;
        rows.forEach((row) => {
            row.hidden = !normalize(row.dataset.name).includes(query) || !matchesMode(row);
            if (!row.hidden) count++;
        });
        document.getElementById('pharmacyArticleCount').textContent = count + ' sur ' + rows.length + ' article(s)';
        document.getElementById('pharmacyStockEmpty').hidden = count > 0;
        metrics.forEach((button) => button.setAttribute('aria-pressed', String(filter.value === button.dataset.stockFilter)));
    };
    search.addEventListener('input', applyFilter);
    filter.addEventListener('change', applyFilter);
    metrics.forEach((button) => button.addEventListener('click', () => {
        filter.value = filter.value === button.dataset.stockFilter ? 'all' : button.dataset.stockFilter;
        applyFilter();
        document.getElementById('pharmacyStockTitle').scrollIntoView({ block: 'nearest' });
    }));
    document.getElementById('pharmacyResetFilters')?.addEventListener('click', () => { search.value = ''; filter.value = 'all'; applyFilter(); });
    let sort = { key: '', direction: 1 };
    document.querySelectorAll('[data-sort-key]').forEach((button) => {
        button.addEventListener('click', () => {
            closeMenu();
            const key = button.dataset.sortKey;
            sort = { key, direction: sort.key === key ? -sort.direction : 1 };
            const statusRanks = { alert: 0, warning: 1, normal: 2, inactive: 3 };
            rows.sort((a, b) => {
                let delta;
                if (key === 'name') delta = normalize(a.dataset.name).localeCompare(normalize(b.dataset.name), 'fr', { numeric: true });
                else if (key === 'state') delta = statusRanks[a.dataset.state] - statusRanks[b.dataset.state];
                else delta = Number(a.dataset[key]) - Number(b.dataset[key]);
                return delta * sort.direction;
            }).forEach((row) => body.appendChild(row));
            document.querySelectorAll('[data-sort-key]').forEach((item) => {
                const active = item === button;
                item.closest('th').setAttribute('aria-sort', active ? (sort.direction === 1 ? 'ascending' : 'descending') : 'none');
                item.querySelector('[data-sort-indicator]').textContent = active ? (sort.direction === 1 ? '\u2191' : '\u2193') : '\u2195';
            });
        });
    });
    applyFilter();
    const pending = JSON.parse(document.getElementById('pharmacyPendingArticle').textContent);
    if (pending.article) openArticle(pending.article, pending.error);
})();
