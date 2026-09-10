<?php

namespace App\Http\Controllers\Butcher\Concerns;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait InteractsWithAccessibleButcherBusiness
{
    /**
     * @return Collection<int, int>
     */
    protected function accessibleBusinessIds(Request $request): Collection
    {
        return $request->user()->accessibleButcherBusinessIds();
    }

    protected function primaryBusiness(Request $request): ?Business
    {
        return Business::query()
            ->whereIn('id', $this->accessibleBusinessIds($request))
            ->orderBy('id')
            ->first();
    }

    /**
     * Optional outlet filter from query string. Null means all outlets.
     */
    protected function requestedOutletId(Request $request, Business $business): ?int
    {
        if (! $request->filled('outlet_id')) {
            return null;
        }

        $outletId = (int) $request->query('outlet_id');
        $exists = $business->butcherOutlets()->whereKey($outletId)->exists();

        return $exists ? $outletId : null;
    }
}
