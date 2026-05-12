<?php

namespace App\Services\Farmer;

use App\Models\DiseaseRecord;
use App\Models\MortalityRecord;
use App\Models\Treatment;
use App\Models\Vaccination;
use App\Models\VeterinaryVisit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HealthRecordCodeService
{
    public function generateVaccinationCode(): string
    {
        return $this->generateUniqueCode('VAC', Vaccination::class, 'vaccination_code');
    }

    public function generateTreatmentCode(): string
    {
        return $this->generateUniqueCode('TRT', Treatment::class, 'treatment_code');
    }

    public function generateDiseaseCode(): string
    {
        return $this->generateUniqueCode('DIS', DiseaseRecord::class, 'disease_code');
    }

    public function generateVisitCode(): string
    {
        return $this->generateUniqueCode('VST', VeterinaryVisit::class, 'visit_code');
    }

    public function generateMortalityCode(): string
    {
        return $this->generateUniqueCode('MRT', MortalityRecord::class, 'mortality_code');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function generateUniqueCode(string $prefix, string $modelClass, string $column): string
    {
        do {
            $code = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (
            $modelClass::withTrashed()
                ->where($column, $code)
                ->exists()
        );

        return $code;
    }
}
