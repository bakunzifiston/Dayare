import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard-charts.js', 'resources/js/processor-dashboard.js', 'resources/js/pwa-install.js'],
            refresh: true,
        }),
    ],
});
