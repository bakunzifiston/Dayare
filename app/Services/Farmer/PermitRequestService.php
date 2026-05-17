<?php

namespace App\Services\Farmer;

use App\Models\Farm;
use App\Models\PermitRequest;
use App\Models\PermitRequestAnimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermitRequestService
{
    public function __construct(
        private readonly PermitRequestCodeService $codes,
        private readonly PermitRequestEligibilityService $eligibility,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, int $userId): PermitRequest
    {
        return DB::transaction(function () use ($data, $userId): PermitRequest {
            $farm = Farm::query()->findOrFail((int) $data['farm_id']);
            $animalIds = $data['animal_ids'] ?? [];

            $request = PermitRequest::query()->create([
                'request_number' => $this->codes->generate(),
                'request_date' => $data['request_date'] ?? now()->toDateString(),
                'applicant_id' => $userId,
                'farm_id' => $farm->id,
                'farmer_id' => $farm->business_id,
                'movement_purpose' => $data['movement_purpose'],
                'destination_type' => $data['destination_type'],
                'destination_name' => $data['destination_name'] ?? null,
                'destination_district' => $data['destination_district'] ?? null,
                'destination_sector' => $data['destination_sector'] ?? null,
                'destination_cell' => $data['destination_cell'] ?? null,
                'destination_village' => $data['destination_village'] ?? null,
                'transport_method' => $data['transport_method'] ?? null,
                'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
                'proposed_departure_date' => $data['proposed_departure_date'],
                'expected_arrival_date' => $data['expected_arrival_date'],
                'remarks' => $data['remarks'] ?? null,
                'status' => PermitRequest::STATUS_DRAFT,
            ]);

            $this->syncAnimals($request, $animalIds);

            return $request->fresh(['animals.animal']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(PermitRequest $request, array $data): PermitRequest
    {
        if (! $request->isEditable()) {
            throw ValidationException::withMessages(['status' => __('This request can no longer be edited.')]);
        }

        return DB::transaction(function () use ($request, $data): PermitRequest {
            $request->update(collect($data)->only([
                'movement_purpose', 'destination_type', 'destination_name',
                'destination_district', 'destination_sector', 'destination_cell', 'destination_village',
                'transport_method', 'vehicle_plate_number', 'proposed_departure_date', 'expected_arrival_date', 'remarks',
            ])->all());

            if (isset($data['animal_ids'])) {
                $request->animals()->delete();
                $this->syncAnimals($request, $data['animal_ids']);
            }

            return $request->fresh(['animals.animal']);
        });
    }

    public function submit(PermitRequest $request): PermitRequest
    {
        $request->load('animals.animal');
        if ($request->animals->isEmpty()) {
            throw ValidationException::withMessages(['animals' => __('Select at least one animal.')]);
        }

        $failed = $request->animals->filter(fn ($line) => ! $line->eligibility_passed);
        if ($failed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'animals' => __('One or more animals are not eligible for movement.'),
            ]);
        }

        $request->update(['status' => PermitRequest::STATUS_SUBMITTED]);

        return $request->fresh();
    }

    public function startReview(PermitRequest $request, int $reviewerId): PermitRequest
    {
        $request->update([
            'status' => PermitRequest::STATUS_UNDER_REVIEW,
            'reviewed_by' => $reviewerId,
            'review_date' => now(),
        ]);

        return $request->fresh();
    }

    public function approve(PermitRequest $request, int $reviewerId): PermitRequest
    {
        $request->update([
            'status' => PermitRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'review_date' => now(),
            'rejection_reason' => null,
        ]);

        return $request->fresh();
    }

    public function reject(PermitRequest $request, int $reviewerId, string $reason): PermitRequest
    {
        $request->update([
            'status' => PermitRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'review_date' => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->fresh();
    }

    public function markPermitIssued(PermitRequest $request): void
    {
        $request->update(['status' => PermitRequest::STATUS_PERMIT_ISSUED]);
    }

    public function markCompleted(PermitRequest $request): void
    {
        $request->update(['status' => PermitRequest::STATUS_COMPLETED]);
    }

    /** @param  list<int>  $animalIds */
    private function syncAnimals(PermitRequest $request, array $animalIds): void
    {
        $animalIds = array_values(array_unique(array_filter(array_map('intval', $animalIds))));

        if ($animalIds === []) {
            return;
        }

        foreach ($animalIds as $animalId) {
            $animal = \App\Models\Animal::query()->with('livestock')->find($animalId);
            if (! $animal) {
                continue;
            }

            $check = $this->eligibility->evaluate($animal);
            PermitRequestAnimal::query()->create([
                'permit_request_id' => $request->id,
                'animal_id' => $animal->id,
                'livestock_id' => $animal->livestock_id,
                'animal_identifier' => $animal->displayIdentifier(),
                'quantity' => 1,
                'eligibility_passed' => $check['passed'],
                'eligibility_issues' => $check['issues'],
            ]);
        }
    }
}
