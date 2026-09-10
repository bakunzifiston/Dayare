<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hygiene log advisory cutoff (local time, HH:MM)
    |--------------------------------------------------------------------------
    |
    | After this time, Receiving and POS show a non-blocking banner when the
    | selected outlet has no hygiene log for today. Never blocks transactions.
    |
    */
    'hygiene_log_cutoff' => env('BUTCHER_HYGIENE_LOG_CUTOFF', '10:00'),
];
