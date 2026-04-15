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
            'timestamp' => $trip->actual_arrival ?? now(),
            'location' => __('Destination'),
            'status' => $trip->status,
            'notes' => $trip->status === LogisticsTrip::STATUS_FAILED ? __('Trip failed.') : __('Trip delivered.'),
        ]);
    }
}

