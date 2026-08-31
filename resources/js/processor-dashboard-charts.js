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

const GRID_COLOR = 'rgba(148, 163, 184, 0.22)';
const TICK_COLOR = '#64748b';
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
        ctx.fillStyle = options.subColor || '#94a3b8';
        ctx.font = `500 10px ${CHART_FONT.family}`;
        ctx.fillText((options.subLabel || 'Total').toUpperCase(), centerX, centerY - 12);
        ctx.fillStyle = options.color || '#0f172a';
        ctx.font = `600 22px ${CHART_FONT.family}`;
        ctx.fillText(String(text), centerX, centerY + 10);
        ctx.restore();
    },
};

if (!Chart.registry.plugins.get('procDashCenterText')) {
    Chart.register(centerTextPlugin);
}

function hexToRgba(input, alpha) {
    if (typeof input !== 'string') {
        return `rgba(161, 29, 30, ${alpha})`;
    }
    if (input.startsWith('rgba')) {
        return input;
    }
    if (input.startsWith('rgb(')) {
        return input.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
    }

    const hex = input.replace('#', '');
    if (hex.length !== 3 && hex.length !== 6) {
        return `rgba(161, 29, 30, ${alpha})`;
    }

    const full = hex.length === 3 ? hex.split('').map((char) => char + char).join('') : hex;
    const n = parseInt(full, 16);

    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
}

function formatCompact(value) {
    const n = Number(value) || 0;
    const abs = Math.abs(n);

    if (abs >= 1_000_000_000) {
        return `${(n / 1_000_000_000).toFixed(abs >= 100_000_000_000 ? 0 : 1)}B`;
    }
    if (abs >= 1_000_000) {
        return `${(n / 1_000_000).toFixed(abs >= 100_000_000 ? 0 : 1)}M`;
    }
    if (abs >= 1_000) {
        return `${(n / 1_000).toFixed(abs >= 100_000 ? 0 : 1)}K`;
    }

    return n.toLocaleString();
}

function formatTick(value, yCallback) {
    if (yCallback === 'percent') {
        return `${value}%`;
    }
    if (yCallback === 'millions') {
        return `${value}M`;
    }
    if (yCallback === 'compact') {
        return formatCompact(value);
    }

    return value;
}

function formatTooltipValue(value, yCallback) {
    if (yCallback === 'percent') {
        return `${value}%`;
    }
    if (yCallback === 'millions') {
        return `${Number(value).toLocaleString()}M RWF`;
    }
    if (yCallback === 'compact') {
        return `${formatCompact(value)} RWF`;
    }

    return Number(value).toLocaleString();
}

function tooltipOptions(yCallback = null) {
    return {
        backgroundColor: '#0f172a',
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        titleFont: { ...CHART_FONT, size: 12, weight: '600' },
        bodyFont: { ...CHART_FONT, size: 11 },
        padding: 12,
        cornerRadius: 10,
        displayColors: true,
        boxPadding: 6,
        boxWidth: 8,
        boxHeight: 8,
        usePointStyle: true,
        caretSize: 6,
        caretPadding: 8,
        callbacks: {
            label(context) {
                const raw = context.parsed?.y ?? context.parsed ?? 0;
                const name = context.dataset?.label ? `${context.dataset.label}: ` : '';

                return `${name}${formatTooltipValue(raw, yCallback)}`;
            },
        },
    };
}

function applyYCallback(options, yCallback) {
    if (! yCallback || ! options.scales?.y?.ticks) {
        return;
    }

    options.scales.y.ticks.callback = (value) => formatTick(value, yCallback);
}

function baseBarOptions(spec = {}) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: { top: 10, right: 6, bottom: 2, left: 4 },
        },
        plugins: {
            legend: { display: false },
            tooltip: tooltipOptions(spec.yCallback),
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
                    padding: 8,
                },
            },
            y: {
                beginAtZero: true,
                grid: { color: GRID_COLOR, drawBorder: false, lineWidth: 1 },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 10 },
                    padding: 10,
                    maxTicksLimit: 5,
                },
            },
        },
    };
}

function formatLineDataset(ds, index) {
    const color = ds.borderColor || ds.backgroundColor || DEFAULT_CHART_COLORS.series[index % DEFAULT_CHART_COLORS.series.length];

    return {
        label: ds.label,
        data: ds.data,
        borderColor: color,
        backgroundColor: hexToRgba(color, 0.14),
        pointBackgroundColor: color,
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointHitRadius: 12,
        pointStyle: 'circle',
        borderWidth: 2.25,
        tension: 0.4,
        fill: ds.fill !== false,
    };
}

function lineChartOptions(spec = {}) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 14, right: 10, bottom: 4, left: 4 } },
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: tooltipOptions(spec.yCallback),
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
                grid: { color: GRID_COLOR, drawBorder: false, lineWidth: 1 },
                border: { display: false },
                ticks: {
                    color: TICK_COLOR,
                    font: { ...CHART_FONT, size: 10 },
                    padding: 10,
                    maxTicksLimit: 5,
                    precision: 0,
                },
            },
        },
    };

    if (spec.yMin !== undefined) {
        options.scales.y.min = spec.yMin;
    }
    if (spec.yMax !== undefined) {
        options.scales.y.max = spec.yMax;
    }

    applyYCallback(options, spec.yCallback);

    return options;
}

function formatBarDataset(ds) {
    return {
        ...ds,
        backgroundColor: ds.backgroundColor,
        borderColor: ds.borderColor ?? 'transparent',
        borderWidth: ds.borderWidth ?? 0,
        borderRadius: ds.borderRadius ?? { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
        borderSkipped: false,
        maxBarThickness: ds.maxBarThickness ?? 36,
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
    const datasets = (spec.datasets || []).map((ds) => formatBarDataset(ds));

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
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6,
                    spacing: 2,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                layout: { padding: 8 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                const value = context.parsed ?? 0;
                                const pct = total > 0 ? Math.round((value / total) * 100) : 0;

                                return `${value.toLocaleString()} · ${pct}%`;
                            },
                        },
                    },
                    procDashCenterText: {
                        text: total.toLocaleString(),
                        subLabel: spec.centerLabel || 'Total',
                        color: '#0f172a',
                        subColor: '#94a3b8',
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
            options: lineChartOptions(spec),
        });

        return;
    }

    const options = baseBarOptions(spec);

    if (spec.yMin !== undefined) {
        options.scales.y.min = spec.yMin;
    }
    if (spec.yMax !== undefined) {
        options.scales.y.max = spec.yMax;
    }

    applyYCallback(options, spec.yCallback);

    if (spec.stacked) {
        options.scales.x.stacked = true;
        options.scales.y.stacked = true;
        options.scales.x.ticks.maxRotation = 45;
        options.scales.x.ticks.minRotation = 0;
        options.scales.x.ticks.autoSkip = true;
        options.datasets = {
            bar: {
                categoryPercentage: 0.68,
                barPercentage: 0.88,
            },
        };
    } else if ((spec.datasets || []).length > 1 && ! spec.legend?.length) {
        options.plugins.legend = {
            display: true,
            position: 'bottom',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8,
                padding: 16,
                color: TICK_COLOR,
                font: { ...CHART_FONT, size: 11 },
            },
        };
    }

    if (spec.indexAxis === 'y') {
        options.indexAxis = 'y';
        options.scales.y.grid = { display: false, drawBorder: false };
        options.scales.y.border = { display: false };
        options.scales.y.ticks = {
            ...options.scales.y.ticks,
            color: TICK_COLOR,
            font: { ...CHART_FONT, size: 10 },
            autoSkip: false,
            callback(value) {
                const label = this.getLabelForValue(value);
                if (typeof label !== 'string') {
                    return label;
                }

                return label.length > 28 ? `${label.slice(0, 26)}…` : label;
            },
        };
        options.scales.x.grid = { color: GRID_COLOR, drawBorder: false };
        options.scales.x.beginAtZero = true;
        options.datasets = {
            bar: {
                borderRadius: { topLeft: 0, topRight: 6, bottomLeft: 0, bottomRight: 6 },
                categoryPercentage: 0.7,
                barPercentage: 0.86,
            },
        };
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

    if (! spec.stacked && (spec.datasets || []).length === 1) {
        options.datasets = {
            bar: {
                categoryPercentage: 0.64,
                barPercentage: 0.82,
                borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
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
