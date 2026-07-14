import Chart from 'chart.js/auto';
import { drawProcessorCharts } from './processor-dashboard-charts';

const centerTextPlugin = Chart.registry.plugins.get('procDashCenterText');

function patchDonutCenterLabels() {
    const specs = window.ricaSupplyChainChartSpecs || [];
    const centerValue = window.ricaSupplyChainDonutCenter;

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
                subLabel: spec.centerLabel ?? 'Total (kg)',
                color: '#111827',
                subColor: '#6b7280',
            };
            chart.update();
        });
}

function drawComboChart(canvas, spec) {
    const ctx = canvas.getContext('2d');
    const labels = spec.labels || [];
    const datasets = (spec.datasets || []).map((dataset) => {
        const color = dataset.borderColor || dataset.backgroundColor || '#A11D1E';

        if (dataset.type === 'line') {
            return {
                type: 'line',
                label: dataset.label,
                data: dataset.data,
                borderColor: color,
                backgroundColor: color,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: color,
                pointBorderWidth: 2,
                pointRadius: 4,
                borderWidth: 2.5,
                tension: 0.3,
                yAxisID: 'y',
            };
        }

        return {
            type: 'bar',
            label: dataset.label,
            data: dataset.data,
            backgroundColor: dataset.backgroundColor || color,
            borderRadius: 6,
            maxBarThickness: 40,
            yAxisID: 'y',
        };
    });

    canvas._chartInstance = new Chart(ctx, {
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        color: '#6b7280',
                        font: { size: 11 },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, color: '#9ca3af', font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.9)' },
                    ticks: { color: '#9ca3af', font: { size: 10 } },
                },
            },
        },
    });
}

function formatKg(value) {
    const amount = Number(value) || 0;
    if (amount >= 1000) {
        return `${Math.round(amount).toLocaleString()}`;
    }

    return amount.toLocaleString(undefined, { maximumFractionDigits: 0 });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function renderFlowNode(node) {
    return `
        <div class="rica-sc-flow-node" data-flow-label="${escapeHtml(node.label)}" title="${escapeHtml(node.label)} · ${formatKg(node.value)} kg">
            <span class="rica-sc-flow-node__name">${escapeHtml(node.label)}</span>
            <span class="rica-sc-flow-node__value">${formatKg(node.value)}</span>
        </div>
    `;
}

function drawFlowPaths(board, originsCol, destinationsCol, links) {
    const boardRect = board.getBoundingClientRect();
    const originNodes = [...originsCol.querySelectorAll('.rica-sc-flow-node')];
    const destinationNodes = [...destinationsCol.querySelectorAll('.rica-sc-flow-node')];
    const originPositions = new Map(
        originNodes.map((node) => {
            const rect = node.getBoundingClientRect();
            return [node.dataset.flowLabel, {
                x: rect.right - boardRect.left,
                y: rect.top - boardRect.top + rect.height / 2,
            }];
        }),
    );
    const destinationPositions = new Map(
        destinationNodes.map((node) => {
            const rect = node.getBoundingClientRect();
            return [node.dataset.flowLabel, {
                x: rect.left - boardRect.left,
                y: rect.top - boardRect.top + rect.height / 2,
            }];
        }),
    );

    const maxValue = Math.max(...links.map((link) => link.value), 1);
    const width = boardRect.width;
    const height = boardRect.height;

    const paths = links.map((link) => {
        const source = originPositions.get(link.source);
        const target = destinationPositions.get(link.target);
        if (!source || !target) {
            return '';
        }

        const strokeWidth = Math.max(1.5, (link.value / maxValue) * 12);
        const midX = width / 2;
        const opacity = Math.max(0.14, link.value / maxValue);

        return `<path d="M ${source.x} ${source.y} C ${midX} ${source.y}, ${midX} ${target.y}, ${target.x} ${target.y}" fill="none" stroke="rgba(161,29,30,${opacity})" stroke-width="${strokeWidth}" stroke-linecap="round" />`;
    }).join('');

    let overlay = board.querySelector('.rica-sc-flow-board__overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'rica-sc-flow-board__overlay';
        overlay.setAttribute('aria-hidden', 'true');
        board.prepend(overlay);
    }

    overlay.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="none">
            ${paths}
        </svg>
    `;
}

function drawFlowChart(container, flowData) {
    if (!container || !flowData?.links?.length) {
        return;
    }

    const origins = flowData.nodes?.origins || [];
    const destinations = flowData.nodes?.destinations || [];
    const links = flowData.links || [];
    const slaughterhousesLabel = window.ricaSupplyChainFlowLabels?.origins ?? 'Slaughterhouses';
    const destinationsLabel = window.ricaSupplyChainFlowLabels?.destinations ?? 'Destinations';

    container.innerHTML = `
        <div class="rica-sc-flow-board">
            <div class="rica-sc-flow-board__origins">
                <p class="rica-sc-flow-board__heading">${escapeHtml(slaughterhousesLabel)}</p>
                <div class="rica-sc-flow-board__nodes">
                    ${origins.map(renderFlowNode).join('')}
                </div>
            </div>
            <div class="rica-sc-flow-board__canvas" aria-hidden="true"></div>
            <div class="rica-sc-flow-board__destinations">
                <p class="rica-sc-flow-board__heading">${escapeHtml(destinationsLabel)}</p>
                <div class="rica-sc-flow-board__nodes">
                    ${destinations.map(renderFlowNode).join('')}
                </div>
            </div>
        </div>
    `;

    const board = container.querySelector('.rica-sc-flow-board');
    const originsCol = container.querySelector('.rica-sc-flow-board__origins .rica-sc-flow-board__nodes');
    const destinationsCol = container.querySelector('.rica-sc-flow-board__destinations .rica-sc-flow-board__nodes');

    requestAnimationFrame(() => {
        drawFlowPaths(board, originsCol, destinationsCol, links);
    });
}

function initRicaSupplyChainCharts() {
    const specs = window.ricaSupplyChainChartSpecs;
    if (Array.isArray(specs)) {
        const standardSpecs = specs.filter((spec) => spec.type !== 'combo');
        const comboSpecs = specs.filter((spec) => spec.type === 'combo');

        if (standardSpecs.length > 0) {
            drawProcessorCharts('rica-supply-chain', standardSpecs);
            setTimeout(patchDonutCenterLabels, 80);
        }

        comboSpecs.forEach((spec) => {
            const canvas = document.getElementById(spec.id);
            if (canvas) {
                drawComboChart(canvas, spec);
            }
        });
    }

    const flowContainer = document.getElementById('rica-sc-flow-chart');
    if (flowContainer) {
        const renderFlow = () => drawFlowChart(flowContainer, window.ricaSupplyChainFlowData);
        renderFlow();
        window.addEventListener('resize', renderFlow);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRicaSupplyChainCharts);
} else {
    initRicaSupplyChainCharts();
}

export { centerTextPlugin };
