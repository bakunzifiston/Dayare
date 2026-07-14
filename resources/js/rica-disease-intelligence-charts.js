import Chart from 'chart.js/auto';
import { drawProcessorCharts } from './processor-dashboard-charts';

function patchDonutCenterLabels() {
    const specs = window.ricaDiseaseChartSpecs || [];
    const centerValue = window.ricaDiseaseDonutCenter;

    specs
        .filter((spec) => spec.type === 'donut' && spec.id)
        .forEach((spec) => {
            const canvas = document.getElementById(spec.id);
            const chart = canvas?._chartInstance;
            if (!chart) {
                return;
            }

            chart.options.plugins.procDashCenterText = {
                text: centerValue ?? chart.options.plugins.procDashCenterText?.text,
                subLabel: spec.centerLabel ?? 'cases',
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
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#9ca3af', font: { size: 11 }, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(229, 231, 235, 0.9)' },
                    ticks: { color: '#9ca3af', font: { size: 10 } },
                },
            },
        },
    });
}

function drawMultiLineChart(canvas, spec) {
    const ctx = canvas.getContext('2d');
    const labels = spec.labels || [];
    const datasets = (spec.datasets || []).map((dataset) => ({
        label: dataset.label,
        data: dataset.data || [],
        borderColor: dataset.borderColor,
        backgroundColor: dataset.backgroundColor || dataset.borderColor,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: dataset.borderColor,
        pointBorderWidth: 2,
        pointRadius: 3,
        borderWidth: 2.5,
        tension: 0.35,
        fill: false,
    }));

    canvas._chartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        color: '#6b7280',
                        font: { size: 11 },
                    },
                },
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

function initRicaDiseaseCharts() {
    const specs = window.ricaDiseaseChartSpecs;
    if (!Array.isArray(specs)) {
        return;
    }

    const standardSpecs = specs.filter((spec) => !['area-line', 'multi-line'].includes(spec.type));
    const areaSpecs = specs.filter((spec) => spec.type === 'area-line');
    const multiLineSpecs = specs.filter((spec) => spec.type === 'multi-line');

    if (standardSpecs.length > 0) {
        drawProcessorCharts('rica-disease-intelligence', standardSpecs);
        setTimeout(patchDonutCenterLabels, 80);
    }

    areaSpecs.forEach((spec) => {
        const canvas = document.getElementById(spec.id);
        if (canvas) {
            drawAreaLineChart(canvas, spec);
        }
    });

    multiLineSpecs.forEach((spec) => {
        const canvas = document.getElementById(spec.id);
        if (canvas) {
            drawMultiLineChart(canvas, spec);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRicaDiseaseCharts);
} else {
    initRicaDiseaseCharts();
}
