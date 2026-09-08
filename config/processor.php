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

    /*
    |--------------------------------------------------------------------------
    | Destination countries (ISO 3166-1 alpha-2 => label)
    |--------------------------------------------------------------------------
    |
    | Used by transport trip (and related) destination country selectors.
    | Codes are stored on transport_trips.destination_country.
    |
    */
    'destination_countries' => [
        'RW' => 'Rwanda',
        'BI' => 'Burundi',
        'CD' => 'DR Congo',
        'KE' => 'Kenya',
        'TZ' => 'Tanzania',
        'UG' => 'Uganda',
        'SS' => 'South Sudan',
        'ET' => 'Ethiopia',
        'SO' => 'Somalia',
        'ZM' => 'Zambia',
        'MW' => 'Malawi',
        'MZ' => 'Mozambique',
        'AO' => 'Angola',
        'ZA' => 'South Africa',
        'AE' => 'United Arab Emirates',
        'CN' => 'China',
        'BE' => 'Belgium',
        'NL' => 'Netherlands',
        'DE' => 'Germany',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'US' => 'United States',
    ],

];
