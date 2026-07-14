import { drawProcessorCharts } from './processor-dashboard-charts';

function patchDonutCenterLabels() {
    const specs = window.ricaTraceabilityChartSpecs || [];
    const centerValue = window.ricaTraceabilityDonutCenter;

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
                subLabel: spec.centerLabel ?? 'Overall',
                color: '#111827',
                subColor: '#6b7280',
            };
            chart.update();
        });
}

function initRicaTraceabilityCharts() {
    const specs = window.ricaTraceabilityChartSpecs;
    if (Array.isArray(specs) && specs.length > 0) {
        drawProcessorCharts('rica-traceability', specs);
        setTimeout(patchDonutCenterLabels, 80);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRicaTraceabilityCharts);
} else {
    initRicaTraceabilityCharts();
}
