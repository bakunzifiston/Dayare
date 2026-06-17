<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public shop storefront
    |--------------------------------------------------------------------------
    |
    | When disabled, /shop routes redirect home and product sections are hidden
    | from the marketing site. Set FEATURE_SHOP_ENABLED=true to re-enable.
    |
    */

    'shop' => (bool) env('FEATURE_SHOP_ENABLED', false),

];
