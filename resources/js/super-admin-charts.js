import { drawProcessorCharts } from './processor-dashboard-charts';

function initSuperAdminCharts() {
    const charts = window.superAdminChartSpecs;
    if (!Array.isArray(charts) || charts.length === 0) {
        return;
    }

    setTimeout(() => drawProcessorCharts('super-admin', charts), 50);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSuperAdminCharts);
} else {
    initSuperAdminCharts();
}
