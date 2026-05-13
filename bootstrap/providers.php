<?php

return [
    App\Providers\AppServiceProvider::class,
    /*
     * Registered explicitly so Swagger UI (/api/documentation) works on hosts where
     * package auto-discovery does not run (e.g. some cPanel / optimized deploys).
     */
    L5Swagger\L5SwaggerServiceProvider::class,
    /*
     * Registered explicitly so PDF generation works on hosts where package
     * auto-discovery does not run (e.g. some cPanel / optimized deploys).
     */
    Barryvdh\DomPDF\ServiceProvider::class,
];
