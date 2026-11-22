(() => {
    'use strict';
    document.querySelector('[data-statistics-share-actions]')?.addEventListener('submit', event => {
        const message = event.submitter?.dataset.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
    });
    document.querySelector('[data-copy-statistics-link]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        const field = document.getElementById('statistics-share-url');
        try {
            await navigator.clipboard.writeText(field.value);
            button.textContent = 'Lien copie';
        } catch {
            field.focus(); field.select();
        }
    });
})();
