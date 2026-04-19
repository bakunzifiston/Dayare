<?php

namespace App\Listeners\Logistics;

use App\Events\Logistics\TripStarted;
use App\Models\LogisticsTrackingLog;
use App\Models\LogisticsTrip;

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
            'location_id' => $trip->origin_location_id,
            'latitude' => null,
            'longitude' => null,
            'event_time' => $trip->actual_departure ?? now(),
            'status' => LogisticsTrackingLog::STATUS_IN_TRANSIT,
            'notes' => __('Trip started.'),
        ]);
    }
}
