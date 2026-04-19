<?php

namespace App\Repositories\Logistics;

use App\Models\LogisticsInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository
{
    public function createOrUpdateWithItems(int $tripId, array $headerAttributes, array $itemRows): LogisticsInvoice
    {
        return DB::transaction(function () use ($tripId, $headerAttributes, $itemRows) {
            $invoice = LogisticsInvoice::query()->updateOrCreate(
                ['trip_id' => $tripId],
                $headerAttributes
            );

            $invoice->items()->delete();

            foreach ($itemRows as $row) {
                $invoice->items()->create([
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total' => $row['total'],
                ]);
            }

            return $invoice->refresh()->load(['items', 'trip', 'order', 'client']);
        });
    }

    /** @return Collection<int, LogisticsInvoice> */
    public function byTripIds(array $tripIds): Collection
    {
        return LogisticsInvoice::query()
            ->with(['items', 'trip', 'order', 'client'])
            ->whereIn('trip_id', $tripIds)
            ->latest('issued_at')
            ->get();
    }
}
