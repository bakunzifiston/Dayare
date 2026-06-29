<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progressive Web App (public marketing site)
    |--------------------------------------------------------------------------
    |
    | Manifest + minimal service worker on public pages only (not the dashboard).
    | Set PWA_ENABLED=true in .env to turn on.
    | Bump PWA_CACHE_VERSION when sw.js precache changes to refresh clients.
    |
    */

    'enabled' => (bool) env('PWA_ENABLED', false),

    'cache_version' => env('PWA_CACHE_VERSION', 'v1'),

    'name' => env('PWA_NAME', env('APP_NAME', 'BuchaPro')),

    'short_name' => env('PWA_SHORT_NAME', 'BuchaPro'),

    'description' => env('PWA_DESCRIPTION', 'End-to-end meat traceability and integrity platform.'),

    'start_url' => env('PWA_START_URL', '/'),

    'theme_color' => env('PWA_THEME_COLOR', '#A11D1E'),

    'background_color' => env('PWA_BACKGROUND_COLOR', '#3C3C3B'),

];
