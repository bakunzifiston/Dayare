<?php

namespace App\Listeners\Logistics;

use App\Events\Logistics\TripStarted;
use App\Models\LogisticsTrip;
use App\Models\LogisticsTrackingLog;

class LogTripStarted
{
    public function handle(TripStarted $event): void
    {
        $trip = LogisticsTrip::query()->find($event->tripId);
        if ($trip === null) {
            return;
        }

        LogisticsTrackingLog::query()->create([
            'trip_id' => $trip->id,
            'timestamp' => $trip->actual_departure ?? now(),
            'location' => __('Departure'),
            'status' => LogisticsTrip::STATUS_IN_TRANSIT,
            'notes' => __('Trip started.'),
        ]);
    }
}

