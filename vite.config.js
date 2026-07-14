import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard-charts.js', 'resources/js/processor-dashboard.js', 'resources/js/super-admin-charts.js', 'resources/js/rica-supply-chain-charts.js', 'resources/js/rica-condemnation-charts.js', 'resources/js/rica-compliance-performance-charts.js', 'resources/js/rica-disease-intelligence-charts.js', 'resources/js/rica-overview-charts.js', 'resources/js/rica-traceability-charts.js', 'resources/js/pwa-install.js'],
            refresh: true,
        }),
    ],
});
