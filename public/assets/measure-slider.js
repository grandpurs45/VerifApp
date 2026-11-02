(() => {
    'use strict';
    const sliders = Array.from(document.querySelectorAll('[data-measure-slider]'));
    const sync = (widget) => {
        const number = widget.querySelector('[data-slider-number]');
        const range = widget.querySelector('[data-slider-range]');
        const output = widget.querySelector('[data-slider-output]');
        const status = widget.querySelector('[data-slider-status]');
        const value = Number(number.value);
        const min = Number(range.min);
        const max = Number(range.max);
        const step = Number(range.step);
        const answered = number.value.trim() !== '' && Number.isInteger(value)
            && value >= min && value <= max && (value - min) % step === 0;
        widget.dataset.answered = String(answered);
        range.setCustomValidity(answered ? '' : 'Renseigne la valeur avec le curseur.');
        if (!answered) {
            output.textContent = number.value === '' ? 'Non renseigne' : 'Valeur invalide';
            range.setAttribute('aria-valuetext', output.textContent);
            status.hidden = true;
            if (number.value === '') range.value = range.min;
            return;
        }
        range.value = number.value;
        output.textContent = new Intl.NumberFormat('fr-FR').format(value) + ' ' + widget.dataset.unit;
        range.setAttribute('aria-valuetext', output.textContent);
        const below = widget.dataset.thresholdMin !== '' && (value < Number(widget.dataset.thresholdMin)
            || (widget.dataset.exclusiveMinimum === '1' && value === Number(widget.dataset.thresholdMin)));
        const above = widget.dataset.thresholdMax !== '' && value > Number(widget.dataset.thresholdMax);
        status.hidden = false;
        status.dataset.result = below || above ? 'nok' : 'ok';
        status.textContent = below || above ? 'Non conforme' : 'Conforme';
    };
    sliders.forEach((widget) => {
        const number = widget.querySelector('[data-slider-number]');
        const range = widget.querySelector('[data-slider-range]');
        const commit = () => {
            number.value = range.value;
            number.dispatchEvent(new Event('input', { bubbles: true }));
        };
        range.addEventListener('input', commit);
        range.addEventListener('pointerup', commit);
        number.addEventListener('input', () => sync(widget), true);
        sync(widget);
    });
    document.addEventListener('measure-sliders:sync', () => sliders.forEach(sync));
})();
