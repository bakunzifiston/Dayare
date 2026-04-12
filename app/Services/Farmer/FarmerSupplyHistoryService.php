<?php

namespace App\Services\Farmer;

use App\Models\AnimalIntake;
use App\Models\Farm;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class FarmerSupplyHistoryService
{
    /**
     * Unified timeline: intakes tied to farmer farms or farmer supply requests, plus rejected requests (no intake).
     *
     * @return Collection<int, array{date: string, facility: string, animal_type: string, quantity: int, status: string, source: string, meta: array}>
     */
    public function history(User $user, int $limit = 100): Collection
    {
        $farmerBusinessIds = $user->accessibleFarmerBusinessIds();
        if ($farmerBusinessIds->isEmpty()) {
            return collect();
        }

        $farmIds = Farm::query()->whereIn('business_id', $farmerBusinessIds)->pluck('id');

        $intakes = AnimalIntake::query()
            ->with(['facility', 'supplyRequest', 'farm'])
            ->where(function ($q) use ($farmIds, $farmerBusinessIds) {
                $q->whereIn('farm_id', $farmIds)
                    ->orWhereHas('supplyRequest', fn ($q2) => $q2->whereIn('farmer_id', $farmerBusinessIds));
            })
            ->latest('intake_date')
            ->limit($limit)
            ->get();

        $intakeRows = $intakes->map(function (AnimalIntake $intake) {
            $speciesKey = strtolower((string) $intake->species);

            return [
                'date' => $intake->intake_date?->toDateString() ?? $intake->created_at->toDateString(),
                'facility' => $intake->facility?->facility_name ?? '—',
                'animal_type' => $speciesKey,
                'quantity' => (int) $intake->number_of_animals,
                'status' => $intake->supply_request_id
                    ? __('Fulfilled (request)')
                    : __('Recorded intake'),
                'source' => $intake->supply_request_id ? 'supply_request' : 'intake',
                'meta' => [
                    'intake_id' => $intake->id,
                    'supply_request_id' => $intake->supply_request_id,
                ],
            ];
        });

        $rejected = SupplyRequest::query()
            ->with('destinationFacility')
            ->whereIn('farmer_id', $farmerBusinessIds)
            ->where('status', SupplyRequest::STATUS_REJECTED)
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        $rejectedRows = $rejected->map(function (SupplyRequest $sr) {
            return [
                'date' => $sr->updated_at->toDateString(),
                'facility' => $sr->destinationFacility?->facility_name ?? '—',
                'animal_type' => $sr->animal_type,
                'quantity' => (int) $sr->quantity_requested,
                'status' => __('Rejected'),
                'source' => 'rejected_request',
                'meta' => [
                    'supply_request_id' => $sr->id,
                ],
            ];
        });

        return $intakeRows->merge($rejectedRows)
            ->sortByDesc('date')
            ->values()
            ->take($limit);
    }
}
