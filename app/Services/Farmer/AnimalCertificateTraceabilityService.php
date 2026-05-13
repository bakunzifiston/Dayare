<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\AnimalCertificate;

class AnimalCertificateTraceabilityService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(Animal $animal): array
    {
        $animal->loadMissing([
            'livestock.farm.business',
            'livestock.farm.province',
            'livestock.farm.district',
            'livestock.farm.sector',
            'ownershipTransfers' => fn ($query) => $query->latest('transfer_date')->limit(5),
            'vaccinations' => fn ($query) => $query->latest('vaccination_date')->limit(3),
            'treatments' => fn ($query) => $query->latest('treatment_start_date')->limit(1),
            'feedingRecords' => fn ($query) => $query->latest('feeding_date')->limit(3),
            'certificates' => fn ($query) => $query->where('certificate_status', AnimalCertificate::STATUS_ACTIVE),
        ]);

        $farm = $animal->livestock?->farm;
        $business = $farm?->business;
        $location = collect([
            $farm?->village?->name,
            $farm?->sector?->name,
            $farm?->district?->name,
            $farm?->province?->name,
        ])->filter()->implode(', ');

        $lastFeeding = $animal->feedingRecords->first();
        $lastVaccination = $animal->vaccinations->first();
        $lastTreatment = $animal->treatments->first();
        $currentOwner = trim((string) ($animal->ownershipTransfers->first()?->new_owner ?? ''));
        if ($currentOwner === '') {
            $currentOwner = $business?->ownerIndividualDisplayName() ?? '';
        }

        return [
            'animal' => $animal,
            'farm' => $farm,
            'business' => $business,
            'farm_location' => $location ?: '—',
            'registration_date' => $animal->acquisition_date?->toDateString() ?: $animal->created_at?->toDateString(),
            'ownership_summary' => $animal->ownershipTransfers->isEmpty()
                ? __('Original owner on file.')
                : __(':count ownership updates recorded.', ['count' => $animal->ownershipTransfers->count()]),
            'current_owner' => $currentOwner !== '' ? $currentOwner : '—',
            'health_summary' => ucfirst(str_replace('_', ' ', $animal->health_status)),
            'vaccination_summary' => $lastVaccination
                ? __('Last vaccination: :name on :date', ['name' => $lastVaccination->vaccine_name, 'date' => $lastVaccination->vaccination_date?->toDateString()])
                : __('No vaccinations recorded.'),
            'last_treatment' => $lastTreatment
                ? __('Last treatment: :name', ['name' => $lastTreatment->disease_name ?: $lastTreatment->medicine_name])
                : __('No treatments recorded.'),
            'feeding_summary' => $lastFeeding
                ? __('Last feeding: :qty on :date', ['qty' => number_format((float) $lastFeeding->quantity, 2), 'date' => $lastFeeding->feeding_date?->toDateString()])
                : __('No feeding records.'),
            'active_certificates' => $animal->certificates->count(),
            'traceability_status' => $animal->certificates->where('certificate_type', AnimalCertificate::TYPE_TRACEABILITY)->isNotEmpty()
                ? __('Verified traceability certificate on file.')
                : __('Traceability certificate pending.'),
        ];
    }
}
