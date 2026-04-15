<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsTrackingLog;
use Illuminate\Support\Collection;

class TrackingRepository
{
    public function create(array $attributes): LogisticsTrackingLog
    {
        return LogisticsTrackingLog::query()->create($attributes);
    }

    /** @return Collection<int, LogisticsTrackingLog> */
    public function byTripIds(array $tripIds): Collection
    {
        return LogisticsTrackingLog::query()
            ->whereIn('trip_id', $tripIds)
            ->latest('timestamp')
            ->get();
    }
}

