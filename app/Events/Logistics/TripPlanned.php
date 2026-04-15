<?php

namespace App\Events\Logistics;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripPlanned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $tripId) {}
}

