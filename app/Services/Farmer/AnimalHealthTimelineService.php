<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\DiseaseRecord;
use App\Models\Treatment;
use App\Models\Vaccination;
use App\Models\VeterinaryVisit;
use Illuminate\Support\Collection;

class AnimalHealthTimelineService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forAnimal(Animal $animal, ?int $limit = null): Collection
    {
        $events = collect();

        $animal->vaccinations()->get()->each(function (Vaccination $record) use ($events): void {
            $events->push([
                'type' => 'vaccination',
                'label' => $record->vaccine_name,
                'date' => $record->vaccination_date,
                'status' => $record->status,
                'veterinarian' => $record->veterinarian_name,
                'notes' => $record->notes,
                'code' => $record->vaccination_code,
                'route' => route('farmer.health.vaccinations.show', $record),
            ]);
        });

        $animal->treatments()->get()->each(function (Treatment $record) use ($events): void {
            $events->push([
                'type' => 'treatment',
                'label' => $record->disease_name ?: $record->medicine_name ?: __('Treatment'),
                'date' => $record->treatment_start_date,
                'status' => $record->status,
                'veterinarian' => $record->veterinarian_name,
                'notes' => $record->notes,
                'code' => $record->treatment_code,
                'route' => route('farmer.health.treatments.show', $record),
            ]);
        });

        $animal->diseaseRecords()->get()->each(function (DiseaseRecord $record) use ($events): void {
            $events->push([
                'type' => 'disease',
                'label' => $record->disease_name,
                'date' => $record->diagnosis_date,
                'status' => $record->recovery_status,
                'veterinarian' => $record->veterinarian_name,
                'notes' => $record->notes,
                'code' => $record->disease_code,
                'route' => route('farmer.health.diseases.show', $record),
            ]);
        });

        $animal->veterinaryVisits()->get()->each(function (VeterinaryVisit $record) use ($events): void {
            $events->push([
                'type' => 'vet_visit',
                'label' => $record->purpose_of_visit ?: __('Veterinary visit'),
                'date' => $record->visit_date,
                'status' => $record->follow_up_required ? 'follow_up_required' : 'completed',
                'veterinarian' => $record->veterinarian_name,
                'notes' => $record->notes,
                'code' => $record->visit_code,
                'route' => route('farmer.health.vet-visits.show', $record),
            ]);
        });

        $animal->loadMissing('mortalityRecord');
        if ($animal->mortalityRecord) {
            $record = $animal->mortalityRecord;
            $events->push([
                'type' => 'mortality',
                'label' => $record->cause_of_death,
                'date' => $record->death_date,
                'status' => 'recorded',
                'veterinarian' => $record->veterinarian_name,
                'notes' => $record->notes,
                'code' => $record->mortality_code,
                'route' => route('farmer.health.mortalities.show', $record),
            ]);
        }

        $events = $events
            ->filter(fn (array $event) => $event['date'] !== null)
            ->sortByDesc(fn (array $event) => $event['date']->timestamp)
            ->values();

        if ($limit !== null) {
            return $events->take($limit);
        }

        return $events;
    }
}
