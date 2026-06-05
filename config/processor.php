<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-link delivery to demand
    |--------------------------------------------------------------------------
    |
    | When true, creating a delivery confirmation may automatically set
    | fulfilled_by_delivery_id on a matching open demand (same client or facility).
    |
    */
    'auto_link_demand' => (bool) env('PROCESSOR_AUTO_LINK_DEMAND', false),

    /*
    |--------------------------------------------------------------------------
    | Domestic country (ISO-style code)
    |--------------------------------------------------------------------------
    |
    | Deliveries with receiver_country different from this value are treated
    | as international exports and require export compliance documents.
    |
    */
    'domestic_country' => env('PROCESSOR_DOMESTIC_COUNTRY', 'RW'),

];
