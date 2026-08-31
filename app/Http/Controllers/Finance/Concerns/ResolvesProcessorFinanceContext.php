<?php

namespace App\Http\Controllers\Finance\Concerns;

use App\Models\Client;
use App\Models\Facility;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesProcessorFinanceContext
{
    protected function activeBusinessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
    }

    /**
     * @return Collection<int, Facility>
     */
    protected function businessFacilities(int $businessId): Collection
    {
        return Facility::query()
            ->where('business_id', $businessId)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);
    }

    /**
     * @return Collection<int, Client>
     */
    protected function businessClients(int $businessId): Collection
    {
        return Client::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Supplier>
     */
    protected function businessSuppliers(int $businessId): Collection
    {
        return Supplier::query()
            ->where('business_id', $businessId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    protected function assertFacility(int $businessId, ?int $facilityId): ?int
    {
        if (! $facilityId) {
            return null;
        }

        $exists = Facility::query()
            ->where('business_id', $businessId)
            ->whereKey($facilityId)
            ->exists();
        abort_unless($exists, 422, __('Invalid site/location.'));

        return $facilityId;
    }
}
