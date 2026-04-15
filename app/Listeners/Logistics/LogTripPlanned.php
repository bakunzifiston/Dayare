<?php

namespace App\Listeners\Logistics;

use App\Events\Logistics\TripPlanned;
use App\Models\LogisticsTrip;
use App\Models\LogisticsTrackingLog;

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
            'timestamp' => now(),
            'location' => __('Planning desk'),
            'status' => LogisticsTrip::STATUS_SCHEDULED,
            'notes' => __('Trip planned and resources reserved.'),
        ]);
    }
}

