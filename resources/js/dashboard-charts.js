import Chart from 'chart.js/auto';

const CHART_FONT = { family: 'Inter, ui-sans-serif, system-ui, sans-serif' };
const GRID_COLOR = 'rgba(113, 113, 122, 0.14)';
const TICK_COLOR = '#71717a';

const centerTextPlugin = {
  id: 'workspaceCenterText',
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

if (!Chart.registry.plugins.get('workspaceCenterText')) {
  Chart.register(centerTextPlugin);
}

/**
 * Render dashboard charts when data is present.
 * Expects window.dashboardCharts = { chartId: { labels, datasets } }.
 */
function initDashboardCharts() {
  const config = window.dashboardCharts;
  if (!config) return;

  const colorPalette = {
    primary: 'rgb(161, 29, 30)',
    primaryBg: 'rgba(161, 29, 30, 0.12)',
    burgundy: 'rgb(60, 60, 59)',
    burgundyBg: 'rgba(60, 60, 59, 0.08)',
    green: 'rgb(56, 161, 105)',
    greenBg: 'rgba(56, 161, 105, 0.15)',
    slate: 'rgb(113, 128, 150)',
    slateBg: 'rgba(113, 128, 150, 0.12)',
    amber: 'rgb(214, 158, 46)',
    amberBg: 'rgba(214, 158, 46, 0.15)',
  };

  const chartIdToCanvasId = (id) => 'chart-' + id.replace(/_/g, '-');

  Object.keys(config).forEach((chartId) => {
    const canvasId = chartIdToCanvasId(chartId);
    const el = document.getElementById(canvasId);
    if (!el || !config[chartId]) return;

    const chartCfg = config[chartId];
    const { labels, datasets: rawDatasets, type = 'bar' } = chartCfg;
    const stacked = !!chartCfg.stacked;
    const yTickPrecision = chartCfg.yTickPrecision ?? 0;
    const indexAxis = chartCfg.indexAxis || 'x';
    const isWorkspace = chartCfg.theme === 'workspace';
    const isCircular = type === 'pie' || type === 'doughnut';
    const colors = [colorPalette.primary, colorPalette.green, colorPalette.slate, colorPalette.burgundy];
    const bgColors = [colorPalette.primaryBg, colorPalette.greenBg, colorPalette.slateBg, colorPalette.burgundyBg];

    const datasets = (rawDatasets || []).map((ds, i) => ({
      label: ds.label,
      data: ds.data,
      backgroundColor: Array.isArray(ds.backgroundColor)
        ? ds.backgroundColor
        : (type === 'bar' ? (ds.backgroundColor ?? bgColors[i % bgColors.length]) : (ds.backgroundColor ?? colors[i % colors.length])),
      borderColor: isWorkspace
        ? (ds.borderColor ?? 'transparent')
        : (isCircular ? '#ffffff' : (ds.borderColor || colors[i % colors.length])),
      borderWidth: isWorkspace
        ? (ds.borderWidth ?? 0)
        : (isCircular ? 2 : (type === 'line' ? 2 : 1)),
      borderRadius: isWorkspace && type === 'bar' ? (ds.borderRadius ?? 6) : undefined,
      borderSkipped: isWorkspace && type === 'bar' ? false : undefined,
      maxBarThickness: isWorkspace && type === 'bar' ? (ds.maxBarThickness ?? 44) : undefined,
      fill: type === 'line' ? (ds.fill !== false) : false,
      tension: type === 'line' ? 0.3 : 0,
      hoverOffset: isCircular ? (isWorkspace ? 4 : 6) : 0,
      spacing: isWorkspace && isCircular ? 2 : undefined,
    }));

    if (isWorkspace && type === 'line') {
      const pointStyles = ['circle', 'triangle', 'rectRounded'];
      const lineDatasets = (rawDatasets || []).map((ds, i) => {
        const color = ds.borderColor || ds.backgroundColor || colors[i % colors.length];

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
          pointStyle: pointStyles[i % pointStyles.length],
          borderWidth: 2.5,
          tension: 0.35,
          fill: false,
        };
      });

      new Chart(el, {
        type: 'line',
        data: { labels, datasets: lineDatasets },
        options: {
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
                precision: yTickPrecision,
              },
            },
          },
        },
      });

      return;
    }

    if (isWorkspace && isCircular) {
      const pieData = datasets[0]?.data || [];
      const total = pieData.reduce((sum, value) => sum + Number(value || 0), 0);

      new Chart(el, {
        type: 'doughnut',
        data: { labels, datasets },
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
            workspaceCenterText: {
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

    const options = {
      responsive: true,
      maintainAspectRatio: false,
      layout: isWorkspace ? { padding: { top: 8, right: 4, bottom: 0, left: 4 } } : undefined,
      plugins: {
        legend: {
          position: 'bottom',
          display: isWorkspace ? false : (isCircular || rawDatasets.length > 1),
        },
        tooltip: isCircular
          ? {
              callbacks: {
                label(context) {
                  const value = context.parsed ?? 0;
                  const total = context.dataset.data.reduce((sum, n) => sum + n, 0);
                  const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                  return `${context.label}: ${value.toLocaleString()} (${pct}%)`;
                },
              },
            }
          : isWorkspace
            ? {
                backgroundColor: '#18181b',
                titleFont: { ...CHART_FONT, size: 12, weight: '600' },
                bodyFont: { ...CHART_FONT, size: 11 },
                padding: 10,
                cornerRadius: 8,
                displayColors: true,
                boxPadding: 4,
              }
            : {},
      },
      ...(isCircular
        ? {}
        : {
            indexAxis,
            scales: {
              x: {
                stacked,
                grid: isWorkspace
                  ? { display: false, drawBorder: false }
                  : undefined,
                border: isWorkspace ? { display: false } : undefined,
                ticks: {
                  maxRotation: isWorkspace ? 45 : 45,
                  minRotation: isWorkspace ? 0 : 0,
                  autoSkip: !isWorkspace,
                  color: isWorkspace ? TICK_COLOR : undefined,
                  font: isWorkspace ? { ...CHART_FONT, size: 11 } : undefined,
                  padding: isWorkspace ? 6 : undefined,
                },
              },
              y: {
                beginAtZero: true,
                stacked,
                grid: isWorkspace
                  ? { color: GRID_COLOR, drawBorder: false }
                  : undefined,
                border: isWorkspace ? { display: false } : undefined,
                ticks: {
                  precision: yTickPrecision,
                  color: isWorkspace ? TICK_COLOR : undefined,
                  font: isWorkspace ? { ...CHART_FONT, size: 10 } : undefined,
                  padding: isWorkspace ? 8 : undefined,
                  maxTicksLimit: isWorkspace ? 5 : undefined,
                },
              },
            },
          }),
    };

    if (isWorkspace && stacked) {
      options.datasets = {
        bar: {
          categoryPercentage: 0.62,
          barPercentage: 0.9,
        },
      };
    }

    new Chart(el, {
      type: isCircular && type === 'doughnut' ? 'doughnut' : type,
      data: { labels, datasets },
      options,
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
  initDashboardCharts();
}
