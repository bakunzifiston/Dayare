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

const GRID_COLOR = 'rgba(128,128,128,0.12)';
const TICK_COLOR = 'rgba(128,128,128,0.7)';

function baseOptions(height) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                grid: { color: GRID_COLOR },
                ticks: { color: TICK_COLOR, font: { size: 10 } },
            },
            y: {
                grid: { color: GRID_COLOR },
                ticks: { color: TICK_COLOR, font: { size: 10 } },
            },
        },
    };
}

function drawChart(canvas, spec) {
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (canvas._chartInstance) {
        canvas._chartInstance.destroy();
        canvas._chartInstance = null;
    }

    const ctx = canvas.getContext('2d');
    const type = spec.type || 'bar';
    const labels = spec.labels || [];
    const datasets = (spec.datasets || []).map((ds) => {
        const backgroundColor = ds.backgroundColor;
        const borderColor = ds.borderColor ?? backgroundColor;

        return {
            ...ds,
            backgroundColor,
            borderColor,
            borderWidth: ds.borderWidth ?? (type === 'bar' ? 1 : type === 'line' ? 2 : 0),
            borderRadius: ds.borderRadius ?? (type === 'bar' ? 4 : undefined),
            pointRadius: ds.pointRadius ?? (type === 'line' ? 3 : undefined),
            skipNull: ds.skipNull ?? true,
        };
    });

    const options = baseOptions(spec.height);
    if (type === 'bar') {
        options.scales.y.beginAtZero = true;
        options.plugins.legend = {
            display: (spec.datasets || []).length > 1,
            position: 'bottom',
        };
    }
    if (spec.yMin !== undefined) options.scales.y.min = spec.yMin;
    if (spec.yMax !== undefined) options.scales.y.max = spec.yMax;
    if (spec.yCallback === 'percent') {
        options.scales.y.ticks.callback = (v) => v + '%';
    } else if (spec.yCallback === 'millions') {
        options.scales.y.ticks.callback = (v) => v + 'M';
    }
    if (spec.stacked) {
        options.scales.x.stacked = true;
        options.scales.y.stacked = true;
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

    const config = {
        type: type === 'donut' ? 'doughnut' : type,
        data: { labels, datasets },
        options,
    };

    if (type === 'donut' || type === 'pie') {
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

        config.options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value = context.parsed ?? 0;
                            const total = (context.dataset.data || []).reduce((sum, n) => sum + n, 0);
                            const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${context.label}: ${value} (${pct}%)`;
                        },
                    },
                },
            },
            ...(type === 'donut' ? { cutout: '68%' } : {}),
        };
        config.data = {
            labels: slices.map((slice) => slice.label),
            datasets: [{
                data: slices.map((slice) => slice.value),
                backgroundColor: slices.map((slice) => slice.color),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }],
        };
    }

    canvas._chartInstance = new Chart(ctx, config);
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
