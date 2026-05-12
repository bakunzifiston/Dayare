<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\DiseaseRecord;
use App\Models\MortalityRecord;
use App\Models\Treatment;
use App\Models\Vaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HealthDashboardService
{
    /**
     * @param  Collection<int, int>  $animalIds
     * @return array<string, mixed>
     */
    public function metrics(Collection $animalIds): array
    {
        $today = Carbon::today();

        $totalVaccinations = Vaccination::query()->whereIn('animal_id', $animalIds)->count();
        $upcomingVaccinations = Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->whereIn('status', [Vaccination::STATUS_SCHEDULED, Vaccination::STATUS_COMPLETED])
            ->whereDate('next_due_date', '>=', $today)
            ->whereDate('next_due_date', '<=', $today->copy()->addDays(30))
            ->count();
        $overdueVaccinations = Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->whereIn('status', [Vaccination::STATUS_SCHEDULED, Vaccination::STATUS_MISSED])
            ->whereDate('next_due_date', '<', $today)
            ->count();

        $sickAnimals = Animal::query()
            ->whereIn('id', $animalIds)
            ->whereIn('health_status', [Animal::HEALTH_SICK, Animal::HEALTH_INJURED, Animal::HEALTH_QUARANTINED])
            ->count();

        $underTreatment = Treatment::query()
            ->whereIn('animal_id', $animalIds)
            ->where('status', Treatment::STATUS_ONGOING)
            ->distinct('animal_id')
            ->count('animal_id');

        $mortalityCount = MortalityRecord::query()->whereIn('animal_id', $animalIds)->count();

        $diseaseCases = DiseaseRecord::query()->whereIn('animal_id', $animalIds)->count();
        $recoveredCases = DiseaseRecord::query()
            ->whereIn('animal_id', $animalIds)
            ->where('recovery_status', DiseaseRecord::RECOVERY_RECOVERED)
            ->count();
        $recoveryRate = $diseaseCases > 0 ? round(($recoveredCases / $diseaseCases) * 100, 1) : 0.0;

        $outbreaks = DiseaseRecord::query()
            ->whereIn('animal_id', $animalIds)
            ->where('contagious_status', DiseaseRecord::CONTAGIOUS_YES)
            ->whereIn('recovery_status', [DiseaseRecord::RECOVERY_RECOVERING, DiseaseRecord::RECOVERY_CHRONIC])
            ->count();

        return [
            'total_vaccinations' => $totalVaccinations,
            'upcoming_vaccinations' => $upcomingVaccinations,
            'overdue_vaccinations' => $overdueVaccinations,
            'sick_animals' => $sickAnimals,
            'under_treatment' => $underTreatment,
            'mortality_count' => $mortalityCount,
            'recovery_rate' => $recoveryRate,
            'disease_outbreaks' => $outbreaks,
        ];
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @return array<string, array<string, mixed>>
     */
    public function charts(Collection $animalIds): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => Carbon::today()->startOfMonth()->subMonths($offset));
        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'))->all();

        $vaccinationTrend = $months->map(function (Carbon $month) use ($animalIds): int {
            return Vaccination::query()
                ->whereIn('animal_id', $animalIds)
                ->whereBetween('vaccination_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->all();

        $diseaseFrequency = DiseaseRecord::query()
            ->whereIn('animal_id', $animalIds)
            ->selectRaw('disease_name, COUNT(*) as total')
            ->groupBy('disease_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $mortalityTrend = $months->map(function (Carbon $month) use ($animalIds): int {
            return MortalityRecord::query()
                ->whereIn('animal_id', $animalIds)
                ->whereBetween('death_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->all();

        $completedTreatments = Treatment::query()
            ->whereIn('animal_id', $animalIds)
            ->where('status', Treatment::STATUS_COMPLETED)
            ->count();
        $failedTreatments = Treatment::query()
            ->whereIn('animal_id', $animalIds)
            ->where('status', Treatment::STATUS_FAILED)
            ->count();

        return [
            'vaccination_trend' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => __('Vaccinations'),
                    'data' => $vaccinationTrend,
                    'backgroundColor' => 'rgba(37, 99, 235, 0.55)',
                ]],
            ],
            'disease_frequency' => [
                'labels' => $diseaseFrequency->pluck('disease_name')->all(),
                'datasets' => [[
                    'label' => __('Cases'),
                    'data' => $diseaseFrequency->pluck('total')->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => 'rgba(220, 38, 38, 0.55)',
                ]],
            ],
            'mortality_trend' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => __('Mortality'),
                    'data' => $mortalityTrend,
                    'backgroundColor' => 'rgba(15, 23, 42, 0.55)',
                ]],
            ],
            'treatment_success' => [
                'labels' => [__('Completed'), __('Failed')],
                'datasets' => [[
                    'label' => __('Treatments'),
                    'data' => [$completedTreatments, $failedTreatments],
                    'backgroundColor' => ['rgba(16, 185, 129, 0.65)', 'rgba(239, 68, 68, 0.65)'],
                ]],
                'type' => 'doughnut',
            ],
        ];
    }
}
