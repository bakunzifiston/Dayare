import Chart from 'chart.js/auto';

const DEFAULT_CHART_COLORS = window.buchaChartColors ?? {
    species: { cattle: '#A11D1E', goat: '#7A1516', sheep: '#D69E2E' },
    series: ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169'],
    semantic: { positive: '#38A169', warning: '#D69E2E', negative: '#A11D1E', neutral: '#718096' },
};

const CHART_COLORS = {
    primary: DEFAULT_CHART_COLORS.series[0],
    burgundy: DEFAULT_CHART_COLORS.series[1],
    charcoal: DEFAULT_CHART_COLORS.series[2],
    muted: DEFAULT_CHART_COLORS.series[3],
    warning: DEFAULT_CHART_COLORS.series[4],
    success: DEFAULT_CHART_COLORS.series[5],
    red: DEFAULT_CHART_COLORS.semantic.negative,
    teal: DEFAULT_CHART_COLORS.semantic.positive,
    blue: DEFAULT_CHART_COLORS.series[0],
    amber: DEFAULT_CHART_COLORS.semantic.warning,
};

const GRID_COLOR = 'rgba(113, 113, 122, 0.14)';
const TICK_COLOR = '#71717a';
const CHART_FONT = { family: 'Inter, ui-sans-serif, system-ui, sans-serif' };

const centerTextPlugin = {
    id: 'procDashCenterText',
    afterDraw(chart, _args, options) {
        const text = options?.text;
        if (!text) {
            return;
        }

        const { ctx, chartArea } = chart;
        if (!chartArea) {
            return;
        }

        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = options.subColor || '#71717a';
        ctx.font = `500 11px ${CHART_FONT.family}`;
        ctx.fillText(options.subLabel || 'Total', centerX, centerY - 10);
        ctx.fillStyle = options.color || '#18181b';
        ctx.font = `600 20px ${CHART_FONT.family}`;
        ctx.fillText(String(text), centerX, centerY + 12);
        ctx.restore();
    },
};

if (!Chart.registry.plugins.get('procDashCenterText')) {
    Chart.register(centerTextPlugin);
}

function baseBarOptions(height) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: { top: 8, right: 4, bottom: 0, left: 4 },
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#18181b',
                titleFont: { ...CHART_FONT, size: 12, weight: '600' },
                bodyFont: { ...CHART_FONT, size: 11 },
                padding: 10,
                cornerRadius: 8,
                displayColors: true,
                boxPadding: 4,
            },
        },
        scales: {
            x: {
                grid: { display: false, drawBorder: false },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 11 },
                    maxRotation: 0,
                    autoSkip: true,
                    padding: 6,
                },
            },
            y: {
                beginAtZero: true,
                grid: { color: GRID_COLOR, drawBorder: false },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 10 },
                    padding: 8,
                    maxTicksLimit: 5,
                },
            },
        },
    };
}

function formatLineDataset(ds, index) {
    const color = ds.borderColor || ds.backgroundColor || DEFAULT_CHART_COLORS.series[index % DEFAULT_CHART_COLORS.series.length];
    const pointStyles = ['circle', 'triangle', 'rectRounded'];

    return {
        label: ds.label,
        data: ds.data,
        borderColor: color,
        backgroundColor: color,
        pointBackgroundColor: color,
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointStyle: pointStyles[index % pointStyles.length],
        borderWidth: 2.5,
        tension: 0.35,
        fill: false,
    };
}

function lineChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 12, right: 8, bottom: 4, left: 4 } },
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#18181b',
                titleFont: { ...CHART_FONT, size: 12, weight: '600' },
                bodyFont: { ...CHART_FONT, size: 11 },
                padding: 10,
                cornerRadius: 8,
                displayColors: true,
                boxPadding: 4,
            },
        },
        scales: {
            x: {
                grid: { display: false, drawBorder: false },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 11 },
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 8,
                    padding: 8,
                },
            },
            y: {
                beginAtZero: true,
                grid: { color: GRID_COLOR, drawBorder: false },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 10 },
                    padding: 8,
                    maxTicksLimit: 5,
                    precision: 0,
                },
            },
        },
    };
}

function formatBarDataset(ds, type) {
    const backgroundColor = ds.backgroundColor;
    const borderColor = ds.borderColor ?? 'transparent';

    return {
        ...ds,
        backgroundColor,
        borderColor,
        borderWidth: ds.borderWidth ?? 0,
        borderRadius: ds.borderRadius ?? 6,
        borderSkipped: false,
        maxBarThickness: ds.maxBarThickness ?? 44,
        pointRadius: ds.pointRadius ?? (type === 'line' ? 3 : undefined),
        skipNull: ds.skipNull ?? true,
    };
}

function drawChart(canvas, spec) {
    if (!canvas) {
        return;
    }

    if (canvas._chartInstance) {
        canvas._chartInstance.destroy();
        canvas._chartInstance = null;
    }

    const ctx = canvas.getContext('2d');
    const type = spec.type || 'bar';
    const labels = spec.labels || [];
    const datasets = (spec.datasets || []).map((ds) => formatBarDataset(ds, type));

    if (type === 'pie' || type === 'donut') {
        const pieLabels = spec.labels || [];
        const pieData = spec.data || [];
        const pieColors = spec.colors || DEFAULT_CHART_COLORS.series;
        const slices = pieLabels
            .map((label, index) => ({
                label,
                value: Number(pieData[index] ?? 0),
                color: pieColors[index] ?? DEFAULT_CHART_COLORS.series[index % DEFAULT_CHART_COLORS.series.length],
            }))
            .filter((slice) => slice.value > 0);
        const total = slices.reduce((sum, slice) => sum + slice.value, 0);

        canvas._chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: slices.map((slice) => slice.label),
                datasets: [{
                    data: slices.map((slice) => slice.value),
                    backgroundColor: slices.map((slice) => slice.color),
                    borderWidth: 0,
                    hoverOffset: 4,
                    spacing: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                layout: { padding: 4 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#18181b',
                        titleFont: { ...CHART_FONT, size: 12, weight: '600' },
                        bodyFont: { ...CHART_FONT, size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label(context) {
                                const value = context.parsed ?? 0;
                                const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${value.toLocaleString()} (${pct}%)`;
                            },
                        },
                    },
                    procDashCenterText: {
                        text: total.toLocaleString(),
                        subLabel: 'Total',
                        color: '#18181b',
                        subColor: '#71717a',
                    },
                },
            },
        });

        return;
    }

    if (type === 'line') {
        const lineDatasets = (spec.datasets || []).map((ds, index) => formatLineDataset(ds, index));

        canvas._chartInstance = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: lineDatasets },
            options: lineChartOptions(),
        });

        return;
    }

    const options = baseBarOptions(spec.height);

    if (spec.yMin !== undefined) {
        options.scales.y.min = spec.yMin;
    }
    if (spec.yMax !== undefined) {
        options.scales.y.max = spec.yMax;
    }
    if (spec.yCallback === 'percent') {
        options.scales.y.ticks.callback = (v) => `${v}%`;
    } else if (spec.yCallback === 'millions') {
        options.scales.y.ticks.callback = (v) => `${v}M`;
    }

    if (spec.stacked) {
        options.scales.x.stacked = true;
        options.scales.y.stacked = true;
        options.plugins.legend = {
            display: false,
        };
        options.scales.x.ticks.maxRotation = 45;
        options.scales.x.ticks.minRotation = 0;
        options.scales.x.ticks.autoSkip = true;
        options.datasets = {
            bar: {
                categoryPercentage: 0.62,
                barPercentage: 0.9,
            },
        };
    } else if ((spec.datasets || []).length > 1) {
        options.plugins.legend = {
            display: true,
            position: 'bottom',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8,
                padding: 14,
                color: TICK_COLOR,
                font: { ...CHART_FONT, size: 11 },
            },
        };
    }

    if (spec.indexAxis === 'y') {
        options.indexAxis = 'y';
    }

    if (spec.referenceLine !== undefined) {
        datasets.push({
            type: 'line',
            label: 'target',
            data: labels.map(() => spec.referenceLine),
            borderColor: CHART_COLORS.red,
            borderDash: [4, 4],
            pointRadius: 0,
            borderWidth: 1.5,
            fill: false,
        });
    }

    if (!spec.stacked && (spec.datasets || []).length === 1) {
        options.datasets = {
            bar: {
                categoryPercentage: 0.62,
                barPercentage: 0.9,
            },
        };
    }

    canvas._chartInstance = new Chart(ctx, {
        type: type === 'donut' ? 'doughnut' : type,
        data: { labels, datasets },
        options,
    });
}

export function drawProcessorCharts(role, charts) {
    if (!Array.isArray(charts)) {
        return;
    }
    charts.forEach((spec) => {
        const canvas = document.getElementById(spec.id);
        if (canvas) {
            drawChart(canvas, spec);
        }
    });
}

export function initProcessorDashboard(role, charts) {
    setTimeout(() => drawProcessorCharts(role, charts), 50);
}
