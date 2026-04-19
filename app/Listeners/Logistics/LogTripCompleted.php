<?php

namespace App\Listeners\Logistics;

use App\Events\Logistics\TripCompleted;
use App\Models\LogisticsTrackingLog;
use App\Models\LogisticsTrip;

class LogTripCompleted
{
    public function handle(TripCompleted $event): void
    {
        $trip = LogisticsTrip::query()->find($event->tripId);
        if ($trip === null) {
            return;
        }

        LogisticsTrackingLog::query()->create([
            'trip_id' => $trip->id,
            'location_id' => $trip->destination_location_id,
            'latitude' => null,
            'longitude' => null,
            'event_time' => $trip->actual_arrival ?? now(),
            'status' => LogisticsTrackingLog::STATUS_COMPLETED,
            'notes' => $trip->status === LogisticsTrip::STATUS_CANCELLED
                ? __('Trip cancelled.')
                : __('Trip completed.'),
        ]);
    }
}
