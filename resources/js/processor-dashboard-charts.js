const CHART_COLORS = {
    teal: '#1D9E75',
    red: '#E24B4A',
    blue: '#378ADD',
    amber: '#EF9F27',
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
    if (!canvas || canvas._done || typeof Chart === 'undefined') {
        return;
    }
    canvas._done = true;

    const ctx = canvas.getContext('2d');
    const type = spec.type || 'bar';
    const labels = spec.labels || [];
    const datasets = (spec.datasets || []).map((ds) => ({
        ...ds,
        borderWidth: ds.borderWidth ?? (type === 'line' ? 2 : 0),
        pointRadius: ds.pointRadius ?? (type === 'line' ? 3 : undefined),
    }));

    const options = baseOptions(spec.height);
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

    if (type === 'donut') {
        config.options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '68%',
        };
        config.data.datasets = [{
            data: spec.data || [],
            backgroundColor: spec.colors || [CHART_COLORS.teal, CHART_COLORS.amber, CHART_COLORS.red],
            borderWidth: 0,
            hoverOffset: 4,
        }];
    }

    new Chart(ctx, config);
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
