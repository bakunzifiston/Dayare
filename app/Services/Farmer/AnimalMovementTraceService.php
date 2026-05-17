<?php

namespace App\Services\Farmer;

use App\Models\MovementHistory;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;

class AnimalMovementTraceService
{
    public function recordForPermit(MovementPermit $permit, string $status, ?int $userId = null, ?string $remarks = null): void
    {
        $permit->loadMissing(['animals.animal', 'sourceFarm', 'permitRequest']);

        foreach ($permit->animals as $line) {
            if (! $line->animal_id) {
                continue;
            }

            MovementHistory::query()->firstOrCreate(
                [
                    'animal_id' => $line->animal_id,
                    'movement_permit_id' => $permit->id,
                    'movement_date' => $permit->departure_date ?? $permit->issue_date ?? now(),
                    'status' => $status,
                ],
                [
                    'source_farm_id' => $permit->source_farm_id,
                    'source_location' => $permit->sourceLocationLabel() ?: $permit->origin_location,
                    'destination_location' => $permit->destinationLocationLabel() ?: $permit->destination_location,
                    'movement_purpose' => $permit->movement_reason ?? $permit->permitRequest?->movement_purpose,
                    'transport_method' => $permit->transport_mode,
                    'vehicle_plate_number' => $permit->vehicle_plate,
                    'recorded_by' => $userId,
                    'remarks' => $remarks,
                ],
            );
        }
    }

    public function markInTransit(MovementPermit $permit, ?int $userId = null): void
    {
        $this->updatePermitHistories($permit, MovementHistory::STATUS_IN_TRANSIT, $userId);
    }

    public function markCompleted(MovementPermit $permit, ?int $userId = null): void
    {
        $this->updatePermitHistories($permit, MovementHistory::STATUS_COMPLETED, $userId);
        if ($permit->permitRequest) {
            app(PermitRequestService::class)->markCompleted($permit->permitRequest);
        }
        $permit->update([
            'permit_status' => MovementPermit::STATUS_USED,
            'movement_status' => MovementPermit::MOVEMENT_ARRIVED,
        ]);
    }

    private function updatePermitHistories(MovementPermit $permit, string $status, ?int $userId): void
    {
        MovementHistory::query()
            ->where('movement_permit_id', $permit->id)
            ->update(['status' => $status, 'recorded_by' => $userId]);

        if ($status === MovementHistory::STATUS_IN_TRANSIT && MovementHistory::query()->where('movement_permit_id', $permit->id)->doesntExist()) {
            $this->recordForPermit($permit, $status, $userId);
        }
    }

    public function recordOnActivation(MovementPermit $permit, ?int $userId = null): void
    {
        if (MovementHistory::query()->where('movement_permit_id', $permit->id)->exists()) {
            return;
        }

        $this->recordForPermit($permit, MovementHistory::STATUS_PLANNED, $userId, __('Permit activated.'));
    }
}
