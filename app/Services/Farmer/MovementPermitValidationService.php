<?php

namespace App\Services\Farmer;

use App\Models\Livestock;
use App\Models\MovementPermit;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class MovementPermitValidationService
{
    /**
     * @throws ValidationException
     */
    public function assertValidForMovement(MovementPermit $permit, int $farmerBusinessId, int $sourceFarmId, int $quantity, ?Livestock $livestock = null): void
    {
        if ((int) $permit->farmer_id !== $farmerBusinessId) {
            throw ValidationException::withMessages([
                'movement_permit_id' => [__('Selected permit does not belong to this farmer.')],
            ]);
        }

        if ((int) $permit->source_farm_id !== $sourceFarmId) {
            throw ValidationException::withMessages([
                'movement_permit_id' => [__('Source farm must match the permit origin.')],
            ]);
        }

        $today = Carbon::today();
        if (! $permit->isValidOn($today)) {
            throw ValidationException::withMessages([
                'movement_permit_id' => [__('Movement permit is not valid (expired or not yet active).')],
            ]);
        }

        $permittedQty = $this->permittedQuantity($permit, $livestock);
        if ($permittedQty < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => [__('Quantity moved cannot exceed permit quantity.')],
            ]);
        }
    }

    private function permittedQuantity(MovementPermit $permit, ?Livestock $livestock): int
    {
        $rows = $permit->animals()->get();

        if ($livestock !== null && $rows->where('livestock_id', $livestock->id)->isNotEmpty()) {
            return (int) $rows
                ->where('livestock_id', $livestock->id)
                ->sum(fn ($row) => (int) ($row->quantity ?? 1));
        }

        return (int) $rows->sum(fn ($row) => (int) ($row->quantity ?? 1));
    }
}

