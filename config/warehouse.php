<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Max storage duration (days)
    |--------------------------------------------------------------------------
    | Alert when a batch remains in warehouse storage longer than this.
    */
    'max_storage_days' => (int) env('WAREHOUSE_MAX_STORAGE_DAYS', 30),
];
