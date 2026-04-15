<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsInvoice;
use Illuminate\Support\Collection;

class InvoiceRepository
{
    public function createOrUpdateByTrip(int $tripId, array $attributes): LogisticsInvoice
    {
        return LogisticsInvoice::query()->updateOrCreate(
            ['trip_id' => $tripId],
            $attributes
        );
    }

    /** @return Collection<int, LogisticsInvoice> */
    public function byTripIds(array $tripIds): Collection
    {
        return LogisticsInvoice::query()
            ->whereIn('trip_id', $tripIds)
            ->latest('id')
            ->get();
    }
}

