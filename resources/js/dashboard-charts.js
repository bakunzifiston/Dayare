import Chart from 'chart.js/auto';

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
    const colors = [colorPalette.primary, colorPalette.green, colorPalette.slate, colorPalette.burgundy];
    const bgColors = [colorPalette.primaryBg, colorPalette.greenBg, colorPalette.slateBg, colorPalette.burgundyBg];

    const datasets = (rawDatasets || []).map((ds, i) => ({
      label: ds.label,
      data: ds.data,
      backgroundColor: type === 'bar' ? (ds.backgroundColor || bgColors[i % bgColors.length]) : (ds.backgroundColor || colors[i % colors.length]),
      borderColor: ds.borderColor || colors[i % colors.length],
      borderWidth: type === 'line' ? 2 : 1,
      fill: type === 'line' ? (ds.fill !== false) : false,
      tension: type === 'line' ? 0.3 : 0,
    }));

    const options = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
      },
      scales: {
        x: {
          stacked,
        },
        y: {
          beginAtZero: true,
          stacked,
          ticks: {
            precision: yTickPrecision,
          },
        },
      },
    };

    new Chart(el, {
      type,
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
