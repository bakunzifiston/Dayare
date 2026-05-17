<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\DiseaseRecord;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use App\Models\Vaccination;
use Carbon\Carbon;

class PermitRequestEligibilityService
{
    /**
     * @return array{passed: bool, issues: list<string>}
     */
    public function evaluate(Animal $animal, ?int $ignorePermitId = null): array
    {
        $issues = [];

        if ($animal->lifecycle_status !== Animal::LIFECYCLE_ACTIVE) {
            $issues[] = __('Animal is not active (:status).', ['status' => $animal->lifecycle_status]);
        }

        if ($animal->health_status === Animal::HEALTH_QUARANTINED) {
            $issues[] = __('Animal is quarantined.');
        }

        if ($this->hasUnresolvedDisease($animal)) {
            $issues[] = __('Animal has unresolved disease alerts.');
        }

        if (! $this->vaccinationsUpToDate($animal)) {
            $issues[] = __('Vaccinations are not up to date.');
        }

        if ($this->isOnActivePermit($animal, $ignorePermitId)) {
            $issues[] = __('Animal is already on an active movement permit.');
        }

        return [
            'passed' => $issues === [],
            'issues' => $issues,
        ];
    }

    private function hasUnresolvedDisease(Animal $animal): bool
    {
        return $animal->diseaseRecords()
            ->where(function ($query): void {
                $query->where('quarantine_required', true)
                    ->orWhereNotIn('recovery_status', [
                        DiseaseRecord::RECOVERY_RECOVERED,
                        DiseaseRecord::RECOVERY_DEAD,
                    ]);
            })
            ->exists();
    }

    private function vaccinationsUpToDate(Animal $animal): bool
    {
        $latest = $animal->vaccinations()
            ->where('status', Vaccination::STATUS_COMPLETED)
            ->orderByDesc('vaccination_date')
            ->first();

        if ($latest === null) {
            return false;
        }

        if ($latest->next_due_date === null) {
            return true;
        }

        return $latest->next_due_date->gte(Carbon::today());
    }

    private function isOnActivePermit(Animal $animal, ?int $ignorePermitId): bool
    {
        return MovementPermitAnimal::query()
            ->where('animal_id', $animal->id)
            ->whereHas('movementPermit', function ($query) use ($ignorePermitId): void {
                $query->whereIn('permit_status', MovementPermit::ACTIVE_STATUSES)
                    ->whereIn('movement_status', [
                        MovementPermit::MOVEMENT_PENDING,
                        MovementPermit::MOVEMENT_IN_TRANSIT,
                    ]);
                if ($ignorePermitId) {
                    $query->whereKeyNot($ignorePermitId);
                }
            })
            ->exists();
    }
}
