import Chart from 'chart.js/auto';

/**
 * Render dashboard charts when data is present.
 * Expects window.dashboardCharts = { chartId: { labels, datasets } }.
 */
function initDashboardCharts() {
  const config = window.dashboardCharts;
  if (!config) return;

  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 },
      },
    },
  };

  const colorPalette = {
    blue: 'rgb(59, 130, 246)',
    blueBg: 'rgba(59, 130, 246, 0.15)',
    green: 'rgb(16, 185, 129)',
    greenBg: 'rgba(16, 185, 129, 0.15)',
    slate: 'rgb(100, 116, 139)',
    slateBg: 'rgba(100, 116, 139, 0.12)',
    amber: 'rgb(245, 158, 11)',
    amberBg: 'rgba(245, 158, 11, 0.15)',
  };

  const chartIdToCanvasId = (id) => 'chart-' + id.replace(/_/g, '-');

  Object.keys(config).forEach((chartId) => {
    const canvasId = chartIdToCanvasId(chartId);
    const el = document.getElementById(canvasId);
    if (!el || !config[chartId]) return;

    const { labels, datasets: rawDatasets, type = 'bar' } = config[chartId];
    const colors = [colorPalette.blue, colorPalette.green, colorPalette.slate, colorPalette.amber];
    const bgColors = [colorPalette.blueBg, colorPalette.greenBg, colorPalette.slateBg, colorPalette.amberBg];

    const datasets = (rawDatasets || []).map((ds, i) => ({
      label: ds.label,
      data: ds.data,
      backgroundColor: type === 'bar' ? (ds.backgroundColor || bgColors[i % bgColors.length]) : (ds.backgroundColor || colors[i % colors.length]),
      borderColor: ds.borderColor || colors[i % colors.length],
      borderWidth: type === 'line' ? 2 : 1,
      fill: type === 'line' ? (ds.fill !== false) : false,
      tension: type === 'line' ? 0.3 : 0,
    }));

    new Chart(el, {
      type,
      data: { labels, datasets },
      options: defaultOptions,
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
  initDashboardCharts();
}
