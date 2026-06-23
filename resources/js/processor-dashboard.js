import { drawProcessorCharts } from './processor-dashboard-charts';

window.processorDashboardCharts = window.processorDashboardCharts || {};

window.procDashSw = function (role) {
    document.querySelectorAll('.proc-dash__role-panel').forEach((panel) => {
        panel.hidden = panel.dataset.role !== role;
    });
    document.querySelectorAll('.proc-dash__tab').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.role === role);
    });
    setTimeout(() => {
        drawProcessorCharts(role, window.processorDashboardCharts[role] || []);
    }, 50);
};

document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.querySelector('.proc-dash__tab.active');
    const activePanel = document.querySelector('.proc-dash__role-panel:not([hidden])');
    const role = activeTab?.dataset.role
        || activePanel?.dataset.role
        || window.processorDashboardActiveRole;

    if (!role) {
        return;
    }

    if (typeof window.procDashSw === 'function' && (activeTab || activePanel)) {
        window.procDashSw(role);
        return;
    }

    setTimeout(() => {
        drawProcessorCharts(role, window.processorDashboardCharts[role] || []);
    }, 50);
});
