<?php

namespace App\Services\SuperAdmin;

use App\Models\AnimalIntake;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SuperAdminSlaughterDashboardService
{
    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     range_label: string,
     *     slaughter_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    public function resolveHubFilters(Request $request): array
    {
        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            return $this->hubFiltersForPreset('month');
        }

        $period = (string) $request->query('period', 'month');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $rawFrom = trim((string) $request->query('date_from', ''));
        $rawTo = trim((string) $request->query('date_to', ''));

        if ($period === 'all' && $rawFrom === '' && $rawTo === '') {
            return $this->hubFiltersAllTime();
        }

        if ($rawFrom !== '' && $rawTo !== '') {
            $start = Carbon::parse($rawFrom)->startOfDay();
            $end = Carbon::parse($rawTo)->endOfDay();
            if ($start->gt($end)) {
                $start = Carbon::parse($rawTo)->startOfDay();
                $end = Carbon::parse($rawFrom)->endOfDay();
                [$rawFrom, $rawTo] = [$start->toDateString(), $end->toDateString()];
            }

            return [
                'period' => $period,
                'date_from' => $rawFrom,
                'date_to' => $rawTo,
                'start' => $start,
                'end' => $end,
                'range_label' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
                'slaughter_label' => __('Slaughtered in range'),
                'has_custom_range' => true,
                'is_filtered' => true,
            ];
        }

        if (in_array($period, ['day', 'month', 'year'], true)) {
            $preset = $this->presetRangeForPeriod($period);

            return [
                'period' => $period,
                'date_from' => $preset['date_from'],
                'date_to' => $preset['date_to'],
                'start' => $preset['start'],
                'end' => $preset['end'],
                'range_label' => $preset['range_label'],
                'slaughter_label' => $preset['slaughter_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array{cattle_slaughtered: int, goat_slaughtered: int, sheep_slaughtered: int}
     */
    public function speciesSlaughteredCounts(array $filters): array
    {
        return [
            'cattle_slaughtered' => $this->speciesSlaughteredCount(SlaughterPlan::SPECIES_CATTLE, $filters),
            'goat_slaughtered' => $this->speciesSlaughteredCount(SlaughterPlan::SPECIES_GOAT, $filters),
            'sheep_slaughtered' => $this->speciesSlaughteredCount(SlaughterPlan::SPECIES_SHEEP, $filters),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return Collection<int, array{
     *     id: int,
     *     facility_name: string,
     *     business_name: string,
     *     animals_slaughtered: int
     * }>
     */
    public function facilitySlaughterRows(array $filters, ?string $facilityType = null): Collection
    {
        $executionQuery = TenantEnvironmentScope::applyToSlaughterExecutions(
            SlaughterExecution::query()
                ->with(['slaughterPlan:id,facility_id'])
        )
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time')
            ->where('actual_animals_slaughtered', '>', 0);

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $executionQuery->whereBetween('slaughter_time', [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        /** @var array<int, int> $countsByFacility */
        $countsByFacility = [];
        foreach ($executionQuery->get(['id', 'slaughter_plan_id', 'actual_animals_slaughtered']) as $execution) {
            $facilityId = (int) ($execution->slaughterPlan?->facility_id ?? 0);
            if ($facilityId <= 0) {
                continue;
            }

            $countsByFacility[$facilityId] = ($countsByFacility[$facilityId] ?? 0)
                + (int) $execution->actual_animals_slaughtered;
        }

        $facilityQuery = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->with('business:id,business_name')
        );

        if ($facilityType !== null) {
            $facilityQuery->where('facility_type', $facilityType);
        }

        return $facilityQuery
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'business_id'])
            ->map(fn (Facility $facility) => [
                'id' => (int) $facility->id,
                'facility_name' => (string) ($facility->facility_name ?: __('Facility #:id', ['id' => $facility->id])),
                'business_name' => (string) ($facility->business?->business_name ?: '—'),
                'animals_slaughtered' => (int) ($countsByFacility[$facility->id] ?? 0),
            ])
            ->sortByDesc('animals_slaughtered')
            ->values();
    }

    /**
     * @param  array{cattle_slaughtered: int, goat_slaughtered: int, sheep_slaughtered: int}  $speciesSlaughtered
     * @return array{labels: list<string>, datasets: list<array{data: list<int>, backgroundColor: list<string>}>, type: string}
     */
    public function chartSpeciesSlaughterPie(array $speciesSlaughtered): array
    {
        $slices = [
            [
                'label' => __('Cattle'),
                'value' => (int) $speciesSlaughtered['cattle_slaughtered'],
                'color' => (string) config('bucha.chart.species.cattle'),
            ],
            [
                'label' => __('Goat'),
                'value' => (int) $speciesSlaughtered['goat_slaughtered'],
                'color' => (string) config('bucha.chart.species.goat'),
            ],
            [
                'label' => __('Sheep'),
                'value' => (int) $speciesSlaughtered['sheep_slaughtered'],
                'color' => (string) config('bucha.chart.species.sheep'),
            ],
        ];

        $slices = array_values(array_filter($slices, fn (array $slice) => $slice['value'] > 0));

        if ($slices === []) {
            return [
                'labels' => [],
                'datasets' => [],
                'type' => 'pie',
            ];
        }

        return [
            'labels' => array_column($slices, 'label'),
            'datasets' => [[
                'data' => array_column($slices, 'value'),
                'backgroundColor' => array_column($slices, 'color'),
            ]],
            'type' => 'pie',
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>, type: string, stacked: bool}
     */
    public function chartSpeciesAnimalIntakeTrend(array $filters): array
    {
        [$start, $end, $groupByMonth] = $this->trendChartRange($filters);
        [$periodKeys, $labels] = $this->buildTrendPeriods($start, $end, $groupByMonth);

        $counts = [
            SlaughterPlan::SPECIES_CATTLE => array_fill_keys($periodKeys, 0),
            SlaughterPlan::SPECIES_GOAT => array_fill_keys($periodKeys, 0),
            SlaughterPlan::SPECIES_SHEEP => array_fill_keys($periodKeys, 0),
        ];

        $intakes = TenantEnvironmentScope::applyToAnimalIntakes(
            AnimalIntake::query()->with(['items:id,animal_intake_id,species'])
        )
            ->where('is_draft', false)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereNotNull('intake_date')
            ->whereBetween('intake_date', [$start, $end])
            ->get(['id', 'species', 'number_of_animals', 'intake_date']);

        foreach ($intakes as $intake) {
            $intakeDate = $intake->intake_date;
            if ($intakeDate === null) {
                continue;
            }

            $periodKey = $groupByMonth
                ? Carbon::parse($intakeDate)->format('Y-m')
                : Carbon::parse($intakeDate)->format('Y-m-d');

            foreach ($this->headCountsBySpeciesFromIntake($intake) as $species => $headCount) {
                if (! isset($counts[$species][$periodKey])) {
                    continue;
                }

                $counts[$species][$periodKey] += $headCount;
            }
        }

        $speciesMeta = [
            SlaughterPlan::SPECIES_CATTLE => [
                'label' => __('Cattle'),
                'color' => (string) config('bucha.chart.species.cattle'),
            ],
            SlaughterPlan::SPECIES_GOAT => [
                'label' => __('Goat'),
                'color' => (string) config('bucha.chart.species.goat'),
            ],
            SlaughterPlan::SPECIES_SHEEP => [
                'label' => __('Sheep'),
                'color' => (string) config('bucha.chart.species.sheep'),
            ],
        ];

        $datasets = [];
        foreach ($speciesMeta as $species => $meta) {
            $datasets[] = [
                'label' => $meta['label'],
                'data' => array_map(fn (string $key) => (int) $counts[$species][$key], $periodKeys),
                'backgroundColor' => $meta['color'],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'type' => 'bar',
            'stacked' => true,
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    public function speciesSlaughteredCount(string $species, array $filters): int
    {
        $query = TenantEnvironmentScope::applyToSlaughterExecutions(
            SlaughterExecution::query()
                ->whereHas('slaughterPlan', fn ($planQuery) => $planQuery->where('species', $species))
        )
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time');

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('slaughter_time', [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        return (int) $query->sum('actual_animals_slaughtered');
    }

    /**
     * @return array<string, int>
     */
    private function headCountsBySpeciesFromIntake(AnimalIntake $intake): array
    {
        if ($intake->relationLoaded('items') && $intake->items->isNotEmpty()) {
            return $intake->items
                ->groupBy(fn ($item) => (string) $item->species)
                ->map(fn ($group) => $group->count())
                ->all();
        }

        $species = (string) ($intake->species ?? '');
        $count = (int) $intake->number_of_animals;

        if ($species === '' || $count <= 0) {
            return [];
        }

        return [$species => $count];
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function buildTrendPeriods(Carbon $start, Carbon $end, bool $groupByMonth): array
    {
        $periodKeys = [];
        $labels = [];

        if ($groupByMonth) {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $periodKeys[] = $cursor->format('Y-m');
                $labels[] = $cursor->translatedFormat('M Y');
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $periodKeys[] = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('M j');
                $cursor->addDay();
            }
        }

        return [$periodKeys, $labels];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array{0: Carbon, 1: Carbon, 2: bool}
     */
    private function trendChartRange(array $filters): array
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();
        } else {
            $end = now()->endOfDay();
            $start = now()->subMonths(5)->startOfMonth()->startOfDay();
        }

        $groupByMonth = $start->diffInDays($end) > 60;

        return [$start, $end, $groupByMonth];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: Carbon,
     *     end: Carbon,
     *     range_label: string,
     *     slaughter_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function hubFiltersForPreset(string $period): array
    {
        $preset = $this->presetRangeForPeriod($period);

        return [
            'period' => $period,
            'date_from' => $preset['date_from'],
            'date_to' => $preset['date_to'],
            'start' => $preset['start'],
            'end' => $preset['end'],
            'range_label' => $preset['range_label'],
            'slaughter_label' => $preset['slaughter_label'],
            'has_custom_range' => false,
            'is_filtered' => true,
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     range_label: string,
     *     slaughter_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function hubFiltersAllTime(): array
    {
        return [
            'period' => 'all',
            'date_from' => '',
            'date_to' => '',
            'start' => null,
            'end' => null,
            'range_label' => __('All time'),
            'slaughter_label' => __('Slaughtered (all time)'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, slaughter_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $slaughterLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Slaughtered today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Slaughtered this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Slaughtered this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'slaughter_label' => $slaughterLabel,
        ];
    }
}
