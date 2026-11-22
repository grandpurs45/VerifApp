(() => {
    'use strict';
    const canvas = document.getElementById('coverage-year-chart');
    if (!canvas) return;
    if (typeof Chart === 'undefined') {
        document.getElementById('coverage-chart-error').classList.remove('hidden');
        return;
    }
    const data = JSON.parse(document.getElementById('coverage-year-data').textContent);
    const colors = ['#0284c7', '#d97706'];
    data.datasets.forEach((dataset, index) => Object.assign(dataset, {
        borderColor: colors[index], backgroundColor: colors[index],
        borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, pointHitRadius: 12,
        borderDash: index === 1 ? [6, 4] : [], pointStyle: index === 1 ? 'rectRot' : 'circle',
        tension: 0, spanGaps: false,
    }));
    new Chart(canvas, {
        type: 'line', data,
        options: {
            responsive: true, maintainAspectRatio: false,
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 200 },
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { min: 0, max: 100, ticks: { stepSize: 20, callback: value => value + '%' }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
            },
            plugins: {
                legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 10, padding: 18 } },
                tooltip: { callbacks: {
                    label: context => context.dataset.label + ' : ' + context.parsed.y + '%',
                    afterLabel: context => {
                        const month = context.dataset.months[context.dataIndex];
                        return [month.covered + ' / ' + month.expected + ' creneaux', month.verifications + ' verifications enregistrees'];
                    },
                } },
            },
            onClick: (event, elements, chart) => {
                elements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
                if (!elements.length) return;
                const point = elements[0];
                const dataset = chart.data.datasets[point.datasetIndex];
                if (dataset.data[point.index] !== null) window.location.href = dataset.links[point.index];
            },
        },
    });
})();
