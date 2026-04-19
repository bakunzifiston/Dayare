<?php

namespace App\Listeners\Logistics;

use App\Events\Logistics\TripPlanned;
use App\Models\LogisticsTrackingLog;
use App\Models\LogisticsTrip;

class LogTripPlanned
{
    public function handle(TripPlanned $event): void
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
            'event_time' => now(),
            'status' => LogisticsTrackingLog::STATUS_SCHEDULED,
            'notes' => __('Trip planned and resources reserved.'),
        ]);
    }
}
