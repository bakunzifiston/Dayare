<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsComplianceDocument;
use Illuminate\Support\Collection;

class ComplianceRepository
{
    public function create(array $attributes): LogisticsComplianceDocument
    {
        return LogisticsComplianceDocument::query()->create($attributes);
    }

    /** @return Collection<int, LogisticsComplianceDocument> */
    public function validByTrip(int $tripId): Collection
    {
        return LogisticsComplianceDocument::query()
            ->where('trip_id', $tripId)
            ->where('status', LogisticsComplianceDocument::STATUS_VALID)
            ->get();
    }

    /** @return Collection<int, LogisticsComplianceDocument> */
    public function byTripIds(array $tripIds): Collection
    {
        return LogisticsComplianceDocument::query()
            ->whereIn('trip_id', $tripIds)
            ->latest('id')
            ->get();
    }
}

