<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnteMortemObservation;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\PostMortemObservation;
use App\Support\RicaDiseaseLabelResolver;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaDiseaseIntelligenceDashboardService
{
    public function __construct(
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $filters = $this->slaughterDashboard->resolveHubFilters($request);
        $selectedDistrictId = $this->normalizeDistrictId($request->query('district_id'));
        $selectedSpecies = $this->normalizeSpeciesFilter($request->query('species'));
        $rows = $this->diseaseCaseRows($filters, $selectedDistrictId, $selectedSpecies);
        $previousRows = $this->diseaseCaseRows(
            $this->previousPeriodFilters($filters),
            $selectedDistrictId,
            $selectedSpecies,
        );

        return [
            'filters' => $filters,
            'districtOptions' => $this->districtOptions(),
            'speciesOptions' => $this->speciesOptions(),
            'selectedDistrictId' => $selectedDistrictId,
            'selectedSpecies' => $selectedSpecies,
            'kpis' => $this->kpis($rows, $previousRows),
            'chartSpecs' => $this->chartSpecs($rows, $filters),
            'districtMap' => $this->districtMapData($rows),
        ];
    }

    /**
     * @return list<array{
     *     disease: string,
     *     species: string,
     *     animal_intake_item_id: int|null,
     *     district_id: int|null,
     *     event_date: Carbon
     * }>
     */
    private function diseaseCaseRows(array $filters, ?int $districtId, ?string $species): array
    {
        $rows = collect();

        AnteMortemInspectionItem::query()
            ->whereIn('outcome', [
                AnteMortemInspectionItem::OUTCOME_REJECTED,
                AnteMortemInspectionItem::OUTCOME_DEFERRED,
            ])
            ->with(['intakeItem', 'inspection.slaughterPlan.facility'])
            ->whereHas('inspection', fn (Builder $query) => $this->applyAnteMortemScope($query, $filters, $districtId))
            ->get()
            ->each(function (AnteMortemInspectionItem $item) use ($rows): void {
                $facility = $item->inspection?->slaughterPlan?->facility;
                $rows->push([
                    'disease' => RicaDiseaseLabelResolver::fromText($item->conditions),
                    'species' => $this->normalizeSpecies($item->intakeItem?->species ?? $item->inspection?->species),
                    'animal_intake_item_id' => $item->animal_intake_item_id ? (int) $item->animal_intake_item_id : null,
                    'district_id' => $facility?->district_id ? (int) $facility->district_id : null,
                    'event_date' => $item->inspection?->inspection_date ?? now(),
                ]);
            });

        AnteMortemObservation::query()
            ->whereIn('value', ['abnormal', 'yes'])
            ->with(['intakeItem', 'inspection.slaughterPlan.facility'])
            ->whereHas('inspection', fn (Builder $query) => $this->applyAnteMortemScope($query, $filters, $districtId))
            ->get()
            ->each(function (AnteMortemObservation $observation) use ($rows): void {
                $facility = $observation->inspection?->slaughterPlan?->facility;
                $rows->push([
                    'disease' => RicaDiseaseLabelResolver::fromChecklistItem($observation->item),
                    'species' => $this->normalizeSpecies($observation->intakeItem?->species ?? $observation->inspection?->species),
                    'animal_intake_item_id' => $observation->animal_intake_item_id ? (int) $observation->animal_intake_item_id : null,
                    'district_id' => $facility?->district_id ? (int) $facility->district_id : null,
                    'event_date' => $observation->inspection?->inspection_date ?? now(),
                ]);
            });

        PostMortemInspectionItem::query()
            ->condemned()
            ->with(['intakeItem', 'inspection.batch.slaughterExecution.slaughterPlan.facility'])
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->get()
            ->each(function (PostMortemInspectionItem $item) use ($rows): void {
                $facility = $item->inspection?->batch?->slaughterExecution?->slaughterPlan?->facility;
                $rows->push([
                    'disease' => RicaDiseaseLabelResolver::fromText($item->reason ?: $item->seized_part),
                    'species' => $this->normalizeSpecies($item->intakeItem?->species ?? $item->inspection?->species),
                    'animal_intake_item_id' => $item->animal_intake_item_id ? (int) $item->animal_intake_item_id : null,
                    'district_id' => $facility?->district_id ? (int) $facility->district_id : null,
                    'event_date' => $item->inspection?->inspection_date ?? now(),
                ]);
            });

        PostMortemObservation::query()
            ->whereIn('value', ['abnormal', 'yes'])
            ->with(['intakeItem', 'inspection.batch.slaughterExecution.slaughterPlan.facility'])
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->get()
            ->each(function (PostMortemObservation $observation) use ($rows): void {
                $facility = $observation->inspection?->batch?->slaughterExecution?->slaughterPlan?->facility;
                $rows->push([
                    'disease' => RicaDiseaseLabelResolver::fromChecklistItem($observation->item),
                    'species' => $this->normalizeSpecies($observation->intakeItem?->species ?? $observation->inspection?->species),
                    'animal_intake_item_id' => $observation->animal_intake_item_id ? (int) $observation->animal_intake_item_id : null,
                    'district_id' => $facility?->district_id ? (int) $facility->district_id : null,
                    'event_date' => $observation->inspection?->inspection_date ?? now(),
                ]);
            });

        PostMortemInspection::query()
            ->where('condemned_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->with(['batch.slaughterExecution.slaughterPlan.facility', 'observations'])
            ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->get()
            ->each(function (PostMortemInspection $inspection) use ($rows): void {
                $facility = $inspection->batch?->slaughterExecution?->slaughterPlan?->facility;
                $reason = $inspection->observations->pluck('notes')->filter()->first() ?: $inspection->notes;
                $rows->push([
                    'disease' => RicaDiseaseLabelResolver::fromText($reason),
                    'species' => $this->normalizeSpecies($inspection->species),
                    'animal_intake_item_id' => null,
                    'district_id' => $facility?->district_id ? (int) $facility->district_id : null,
                    'event_date' => $inspection->inspection_date ?? now(),
                ]);
            });

        return $rows
            ->when($species !== null, fn (Collection $collection) => $collection->filter(
                fn (array $row): bool => Str::lower($row['species']) === Str::lower($species),
            ))
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $previousRows
     * @return array<string, mixed>
     */
    private function kpis(array $rows, array $previousRows): array
    {
        $cases = count($rows);
        $previousCases = count($previousRows);
        $unhealthyAnimals = collect($rows)->pluck('animal_intake_item_id')->filter()->unique()->count();
        $previousUnhealthyAnimals = collect($previousRows)->pluck('animal_intake_item_id')->filter()->unique()->count();
        $diseasesDetected = collect($rows)->pluck('disease')->unique()->count();
        $previousDiseasesDetected = collect($previousRows)->pluck('disease')->unique()->count();
        $districtsAffected = collect($rows)->pluck('district_id')->filter()->unique()->count();
        $previousDistrictsAffected = collect($previousRows)->pluck('district_id')->filter()->unique()->count();

        return [
            'unhealthy_animals' => [
                'value' => $unhealthyAnimals,
                'trend' => $this->trendDelta($unhealthyAnimals, $previousUnhealthyAnimals),
            ],
            'disease_cases' => [
                'value' => $cases,
                'trend' => $this->trendDelta($cases, $previousCases),
            ],
            'diseases_detected' => [
                'value' => $diseasesDetected,
                'trend' => $this->trendCountDelta($diseasesDetected, $previousDiseasesDetected),
            ],
            'districts_affected' => [
                'value' => $districtsAffected,
                'trend' => $this->trendCountDelta($districtsAffected, $previousDistrictsAffected),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function chartSpecs(array $rows, array $filters): array
    {
        $colors = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);
        $topDiseases = $this->groupTotals($rows, 'disease', 7);
        $speciesTotals = $this->groupTotals($rows, 'species', 5);
        $trend = $this->diseaseTrendChart($rows, $filters);
        $seasonal = $this->seasonalRiskChart($rows, $filters);

        return [
            [
                'id' => 'rica-di-top-diseases-bar',
                'title' => __('Top diseases'),
                'type' => 'bar',
                'indexAxis' => 'y',
                'height' => 240,
                'labels' => $topDiseases['labels'],
                'datasets' => [[
                    'label' => __('Cases'),
                    'data' => $topDiseases['data'],
                    'backgroundColor' => $this->chartColors(count($topDiseases['labels']), $colors),
                ]],
                'emptyMessage' => __('No disease cases for this period.'),
            ],
            [
                'id' => 'rica-di-species-donut',
                'title' => __('Diseases by species'),
                'type' => 'donut',
                'height' => 220,
                'labels' => $speciesTotals['labels'],
                'data' => $speciesTotals['data'],
                'colors' => ($speciesColors = $this->chartColors(count($speciesTotals['labels']), $colors)),
                'centerLabel' => __('cases'),
                'legend' => collect($speciesTotals['labels'])->values()->map(fn (string $label, int $index) => [
                    'color' => $speciesColors[$index] ?? '#718096',
                    'label' => $label,
                ])->all(),
                'emptyMessage' => __('No species breakdown for this period.'),
            ],
            array_merge($seasonal, [
                'id' => 'rica-di-seasonal-risk',
                'title' => __('Seasonal risk'),
                'type' => 'area-line',
                'height' => 240,
                'emptyMessage' => __('No seasonal risk data for this period.'),
            ]),
            array_merge($trend, [
                'id' => 'rica-di-trend-line',
                'title' => __('Disease trend'),
                'type' => 'multi-line',
                'height' => 240,
                'fullWidth' => true,
                'emptyMessage' => __('No disease trend for this period.'),
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: int, name: string, count: int, intensity: float}>
     */
    private function districtMapData(array $rows): array
    {
        $districts = AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = collect($rows)
            ->filter(fn (array $row): bool => $row['district_id'] !== null)
            ->groupBy('district_id')
            ->map(fn (Collection $group) => $group->count());

        $maxCount = max(1, (int) $counts->max());

        return $districts->map(function (AdministrativeDivision $district) use ($counts, $maxCount): array {
            $count = (int) ($counts[$district->id] ?? 0);

            return [
                'id' => (int) $district->id,
                'name' => $district->name,
                'count' => $count,
                'intensity' => round($count / $maxCount, 3),
            ];
        })->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function diseaseTrendChart(array $rows, array $filters): array
    {
        $topDiseases = collect($rows)
            ->groupBy('disease')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(4)
            ->keys()
            ->all();

        if ($topDiseases === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $start = $filters['start'] ?? collect($rows)->min('event_date');
        $end = $filters['end'] ?? collect($rows)->max('event_date');
        $start = $start instanceof Carbon ? $start->copy()->startOfMonth() : Carbon::parse($start)->startOfMonth();
        $end = $end instanceof Carbon ? $end->copy()->endOfMonth() : Carbon::parse($end)->endOfMonth();

        $labels = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('M');
            $cursor->addMonth();
        }

        if ($labels === []) {
            $labels = [now()->format('M')];
        }

        $colors = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096']);
        $datasets = [];

        foreach ($topDiseases as $index => $disease) {
            $data = [];
            $monthCursor = $start->copy();
            foreach ($labels as $_label) {
                $monthEnd = $monthCursor->copy()->endOfMonth();
                $data[] = collect($rows)
                    ->filter(fn (array $row) => $row['disease'] === $disease
                        && Carbon::parse($row['event_date'])->between($monthCursor, $monthEnd))
                    ->count();
                $monthCursor->addMonth();
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $disease,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'fill' => false,
            ];
        }

        return compact('labels', 'datasets');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function seasonalRiskChart(array $rows, array $filters): array
    {
        $year = ($filters['end'] ?? now())->year;
        $labels = collect(range(1, 12))->map(fn (int $month) => Carbon::create($year, $month, 1)->format('M'))->all();
        $monthlyCounts = [];

        foreach (range(1, 12) as $month) {
            $monthlyCounts[] = collect($rows)
                ->filter(fn (array $row) => (int) Carbon::parse($row['event_date'])->month === $month)
                ->count();
        }

        $max = max(1, ...$monthlyCounts);
        $riskIndex = array_map(fn (int $count): int => (int) round($count / $max * 100), $monthlyCounts);
        $primary = config('bucha.chart.series.0', '#A11D1E');

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __('Risk index'),
                'data' => $riskIndex,
                'borderColor' => $primary,
                'backgroundColor' => 'rgba(161, 29, 30, 0.12)',
                'fill' => true,
            ]],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, data: list<int>}
     */
    private function groupTotals(array $rows, string $key, int $limit): array
    {
        $all = collect($rows)
            ->groupBy($key)
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();

        $top = $all->take($limit);
        $otherCount = (int) $all->slice($limit)->sum();

        $labels = $top->keys()->values()->all();
        $data = $top->values()->all();

        if ($otherCount > 0) {
            $labels[] = __('Others');
            $data[] = $otherCount;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @param  Builder<AnteMortemInspection>  $query
     */
    private function applyAnteMortemScope(Builder $query, array $filters, ?int $districtId): void
    {
        $this->applyDateFilter($query, 'inspection_date', $filters);
        $query->whereHas('slaughterPlan', function (Builder $planQuery) use ($districtId): void {
            TenantEnvironmentScope::applyToSlaughterPlans($planQuery);
            if ($districtId !== null) {
                $planQuery->whereHas('facility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId));
            }
        });
    }

    /**
     * @param  Builder<PostMortemInspection>  $query
     */
    private function applyPostMortemScope(Builder $query, array $filters, ?int $districtId): void
    {
        $this->applyDateFilter($query, 'inspection_date', $filters);
        $query->whereHas(
            'batch.slaughterExecution.slaughterPlan.facility',
            function (Builder $facilityQuery) use ($districtId): void {
                TenantEnvironmentScope::applyToFacilities($facilityQuery);
                if ($districtId !== null) {
                    $facilityQuery->where('district_id', $districtId);
                }
            },
        );
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyDateFilter(Builder $query, string $column, array $filters): void
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween($column, [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function districtOptions(): array
    {
        return AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function speciesOptions(): array
    {
        return [
            'all' => __('All species'),
            'Cattle' => __('Cattle'),
            'Goat' => __('Goat'),
            'Sheep' => __('Sheep'),
            'Pig' => __('Pig'),
            'Poultry' => __('Poultry'),
            'Other' => __('Other'),
        ];
    }

    private function normalizeSpecies(?string $species): string
    {
        $species = trim((string) $species);

        return $species !== '' ? ucfirst($species) : __('Unknown');
    }

    private function normalizeDistrictId(mixed $districtId): ?int
    {
        if ($districtId === null || $districtId === '' || $districtId === 'all') {
            return null;
        }

        return is_numeric($districtId) ? (int) $districtId : null;
    }

    private function normalizeSpeciesFilter(mixed $species): ?string
    {
        if ($species === null || $species === '' || $species === 'all') {
            return null;
        }

        return is_string($species) ? $species : null;
    }

    /**
     * @param  array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}  $filters
     * @return array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}
     */
    private function previousPeriodFilters(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            return [
                'is_filtered' => true,
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
                'period' => 'month',
            ];
        }

        $days = max(1, $filters['start']->diffInDays($filters['end']) + 1);
        $previousEnd = $filters['start']->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            'is_filtered' => true,
            'start' => $previousStart,
            'end' => $previousEnd,
            'period' => $filters['period'],
        ];
    }

    /**
     * @return array{direction: string, percent: float, label: string}
     */
    private function trendDelta(float|int $current, float|int $previous): array
    {
        if ($previous <= 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'neutral',
                'percent' => 0.0,
                'label' => __('vs last month'),
            ];
        }

        $delta = (($current - $previous) / $previous) * 100;

        return [
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'percent' => round(abs($delta), 1),
            'label' => __('vs last month'),
        ];
    }

    /**
     * @return array{direction: string, percent: float, label: string}
     */
    private function trendCountDelta(int $current, int $previous): array
    {
        $delta = $current - $previous;

        return [
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'percent' => (float) abs($delta),
            'label' => __('vs last month'),
        ];
    }

    /**
     * @param  array<int, string>  $palette
     * @return array<int, string>
     */
    private function chartColors(int $count, array $palette): array
    {
        return collect(range(0, max(0, $count - 1)))
            ->map(fn (int $index) => $palette[$index % count($palette)])
            ->all();
    }
}
