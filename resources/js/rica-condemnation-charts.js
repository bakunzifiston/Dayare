import Chart from 'chart.js/auto';
import { drawProcessorCharts } from './processor-dashboard-charts';

function patchDonutCenterLabels() {
    const specs = window.ricaCondemnationChartSpecs || [];
    const centerValue = window.ricaCondemnationDonutCenter;

    specs
        .filter((spec) => spec.type === 'donut' && spec.id)
        .forEach((spec) => {
            const canvas = document.getElementById(spec.id);
            const chart = canvas?._chartInstance;
            if (!chart) {
                return;
            }

            const sliceTotal = (chart.data.datasets?.[0]?.data || [])
                .reduce((sum, value) => sum + Number(value || 0), 0);

            chart.options.plugins.procDashCenterText = {
                text: centerValue || Math.round(sliceTotal).toLocaleString(),
                subLabel: spec.centerLabel ?? 'kg',
                color: '#111827',
                subColor: '#6b7280',
            };
            chart.update();
        });
}

function drawAreaLineChart(canvas, spec) {
    const ctx = canvas.getContext('2d');
    const labels = spec.labels || [];
    const dataset = (spec.datasets || [])[0] || {};
    const color = dataset.borderColor || '#A11D1E';

    canvas._chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: dataset.label || '',
                data: dataset.data || [],
                borderColor: color,
                backgroundColor: dataset.backgroundColor || 'rgba(161, 29, 30, 0.12)',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: color,
                pointBorderWidth: 2,
                pointRadius: 3,
                borderWidth: 2.5,
                tension: 0.35,
                fill: dataset.fill ?? true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#9ca3af', font: { size: 11 }, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.9)' },
                    ticks: { color: '#9ca3af', font: { size: 10 } },
                },
            },
        },
    });
}

function initRicaCondemnationCharts() {
    const specs = window.ricaCondemnationChartSpecs;
    if (!Array.isArray(specs)) {
        return;
    }

    const standardSpecs = specs.filter((spec) => spec.type !== 'area-line');
    const areaSpecs = specs.filter((spec) => spec.type === 'area-line');

    if (standardSpecs.length > 0) {
        drawProcessorCharts('rica-condemnation', standardSpecs);
        setTimeout(patchDonutCenterLabels, 80);
    }

    areaSpecs.forEach((spec) => {
        const canvas = document.getElementById(spec.id);
        if (canvas) {
            drawAreaLineChart(canvas, spec);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRicaCondemnationCharts);
} else {
    initRicaCondemnationCharts();
}
