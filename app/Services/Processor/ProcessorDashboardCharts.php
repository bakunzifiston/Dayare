<?php

namespace App\Services\Processor;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProcessorDashboardCharts
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forRole(string $roleKey, ProcessorDashboardContext $ctx, int $businessId, ?array $filters = null, ?User $user = null): array
    {
        return match ($roleKey) {
            'operations_manager' => $this->opsManager($ctx),
            'compliance_officer' => $this->complianceOfficer($ctx),
            'inspector' => $this->inspector($ctx),
            'transport_manager' => $this->transportManager($ctx),
            'accountant' => $this->accountant($businessId),
            default => $this->orgAdmin($ctx, $filters, $user),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orgAdmin(ProcessorDashboardContext $ctx, ?array $filters = null, ?User $user = null): array
    {
        $filters = $filters ?? ['is_filtered' => false];
        $facilityIds = $this->orgAdminFacilityIds($ctx, $user);
        $planIds = $this->orgAdminPlanIds($ctx, $user);
        $executed = $this->executedBySpeciesTotals($planIds, $filters);
        $intakeEndDate = $this->intakeTrendEndDate($facilityIds);
        $days = $this->weekdayLabelsEndingAt($intakeEndDate);
        $speciesReceivedDatasets = [
            $this->coloredBarDataset(__('Cattle'), $this->intakeBySpeciesForFacilities($facilityIds, 'cattle', $intakeEndDate), $this->speciesColor('cattle')),
            $this->coloredBarDataset(__('Goat'), $this->intakeBySpeciesForFacilities($facilityIds, 'goat', $intakeEndDate), $this->speciesColor('goat')),
            $this->coloredBarDataset(__('Sheep'), $this->intakeBySpeciesForFacilities($facilityIds, 'sheep', $intakeEndDate), $this->speciesColor('sheep')),
        ];
        $onTimeData = [91, 88, 93, 85, 82, 87];

        return [
            $this->barChart(
                'org_admin-species-received',
                __('Trend species received'),
                160,
                __('Daily animals received by cattle, goat, and sheep over the past week'),
                $days,
                $speciesReceivedDatasets,
                legend: $this->speciesLegend(),
            ),
            array_merge(
                $this->barChart(
                    'org_admin-ontime',
                    __('On-time delivery rate'),
                    180,
                    __('Monthly on-time delivery rate January through June'),
                    ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    [$this->multiColorBarDataset(__('On-time delivery rate'), $onTimeData, $this->chartSeriesColors())],
                    'percent',
                ),
                ['yMin' => 70, 'yMax' => 100],
            ),
            $this->pieChart(
                'org_admin-pieces-executed',
                __('Pieces executed'),
                160,
                __('Animals executed by cattle, goat, and sheep'),
                [__('Cattle'), __('Goat'), __('Sheep')],
                [$executed['cattle'], $executed['goat'], $executed['sheep']],
                $this->speciesColors(),
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function opsManager(ProcessorDashboardContext $ctx): array
    {
        $days = $this->lastWeekdayLabels();
        $intake = $this->dailyIntakeCounts($ctx, $days);

        return [
            $this->barChart('ops-intake', __('Daily animal intake'), 170, __('Daily animal intake Monday through Sunday'), $days, [
                ['label' => __('Head'), 'data' => $intake, 'backgroundColor' => $this->brandColor('primary')],
            ]),
            $this->lineWithTarget('ops-cert-rate', __('Batch certification rate'), 150, __('Weekly batch certification rate with 90% target'), ['W1', 'W2', 'W3', 'W4'], [88, 92, 85, 75], 90, 60, 100),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function complianceOfficer(ProcessorDashboardContext $ctx): array
    {
        $tempCount = 4;
        $inspectionCount = 3;
        $licenseCount = 2;
        $transportCount = 2;
        $docsCount = 1;

        return [
            $this->lineWithTarget('compliance-score', __('Compliance score'), 160, __('Eight-week compliance score trend with 95% target'), ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'], [84, 87, 91, 88, 85, 90, 84, 78], 95, 60, 100),
            $this->horizontalBarChart('compliance-issues', __('Issues by category'), 150, __('Open compliance issues grouped by category'), [__('Temperature'), __('Inspection'), __('License'), __('Transport'), __('Docs')], [$tempCount, $inspectionCount, $licenseCount, $transportCount, $docsCount], [$this->brandColor('primary'), $this->brandColor('warning'), $this->brandColor('warning'), $this->brandColor('warning'), $this->brandColor('muted')]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function inspector(ProcessorDashboardContext $ctx): array
    {
        $days = $this->lastWeekdayLabels();

        return [
            $this->barChart('inspector-workload', __('Daily workload'), 160, __('Daily ante-mortem, post-mortem, and certificate workload'), $days, [
                ['label' => __('AM'), 'data' => $this->dailyAmCounts($ctx, $days), 'backgroundColor' => $this->speciesColor('cattle')],
                ['label' => __('PM'), 'data' => $this->dailyPmCounts($ctx, $days), 'backgroundColor' => $this->speciesColor('goat')],
                ['label' => __('Certs'), 'data' => $this->dailyCertCounts($ctx, $days), 'backgroundColor' => $this->speciesColor('sheep')],
            ]),
            $this->donutChart('inspector-outcomes', __('Inspection outcomes'), 130, __('Inspection outcomes this month'), [__('Pass'), __('Conditional'), __('Rejected')], [84, 8, 8], [$this->brandColor('success'), $this->brandColor('warning'), $this->brandColor('primary')]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transportManager(ProcessorDashboardContext $ctx): array
    {
        return [
            $this->stackedBarChart('transport-deliveries', __('Weekly deliveries'), 160, __('Domestic and export deliveries over six weeks'), ['W1', 'W2', 'W3', 'W4', 'W5', 'W6'], [
                ['label' => __('Domestic'), 'data' => [8, 9, 7, 10, 9, 11], 'backgroundColor' => $this->brandColor('primary')],
                ['label' => __('Export'), 'data' => [2, 1, 3, 2, 1, 2], 'backgroundColor' => $this->brandColor('warning')],
            ]),
            $this->lineChart('transport-ontime', __('On-time delivery rate'), 150, __('Monthly on-time delivery rate January through June'), ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], [91, 88, 93, 85, 82, 87], $this->brandColor('primary'), 70, 100, 'percent'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function accountant(int $businessId): array
    {
        return [
            $this->barChart('finance-revenue-cost', __('Monthly revenue vs cost'), 170, __('Monthly revenue and cost in RWF millions'), ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], [
                ['label' => __('Revenue'), 'data' => [22, 24, 26, 25, 27, 28], 'backgroundColor' => $this->brandColor('success')],
                ['label' => __('Cost'), 'data' => [17, 18, 19, 20, 21, 22], 'backgroundColor' => $this->brandColor('primary')],
            ], 'millions'),
            $this->donutChart('finance-ar', __('AR invoice status'), 150, __('Accounts receivable invoice status'), [__('Paid'), __('Pending'), __('Overdue')], [78, 14, 8], [$this->brandColor('success'), $this->brandColor('warning'), $this->brandColor('primary')]),
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, array<string, mixed>>  $datasets
     * @param  array<int, array{color: string, label: string}>|null  $legend
     * @return array<string, mixed>
     */
    private function barChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $datasets, ?string $yCallback = null, ?array $legend = null): array
    {
        $chart = [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => $datasets,
            'legend' => $legend ?? $this->legendFromDatasets($datasets),
        ];
        if ($yCallback) {
            $chart['yCallback'] = $yCallback;
        }

        return $chart;
    }

    /**
     * @param  array<int, int|float|null>  $data
     * @return array<string, mixed>
     */
    private function coloredBarDataset(string $label, array $data, string $color): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'borderWidth' => 1,
            'borderRadius' => 4,
        ];
    }

    /**
     * @param  array<int, int|float>  $data
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function multiColorBarDataset(string $label, array $data, array $colors): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $colors,
            'borderColor' => $colors,
            'borderWidth' => 1,
            'borderRadius' => 4,
        ];
    }

    /**
     * @return array<int, array{color: string, label: string}>
     */
    private function speciesLegend(): array
    {
        return [
            ['color' => $this->speciesColor('cattle'), 'label' => __('Cattle')],
            ['color' => $this->speciesColor('goat'), 'label' => __('Goat')],
            ['color' => $this->speciesColor('sheep'), 'label' => __('Sheep')],
        ];
    }

    private function brandColor(string $key): string
    {
        return (string) config("bucha.colors.{$key}");
    }

    private function speciesColor(string $species): string
    {
        return (string) config("bucha.chart.species.{$species}");
    }

    /**
     * @return array<int, string>
     */
    private function speciesColors(): array
    {
        return array_values(config('bucha.chart.species'));
    }

    /**
     * @return array<int, string>
     */
    private function chartSeriesColors(): array
    {
        return config('bucha.chart.series');
    }

    /**
     * @param  array<int, array<string, mixed>>  $datasets
     * @return array<int, array{color: string, label: string}>
     */
    private function legendFromDatasets(array $datasets): array
    {
        return collect($datasets)
            ->map(function (array $dataset): ?array {
                $color = $dataset['backgroundColor'] ?? null;
                if (! is_string($color)) {
                    return null;
                }

                return [
                    'color' => $color,
                    'label' => (string) ($dataset['label'] ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, float|int>  $data
     * @return array<string, mixed>
     */
    private function lineChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $data, string $color, ?int $yMin, ?int $yMax, ?string $yCallback = null): array
    {
        $chart = [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                ['label' => $title, 'data' => $data, 'borderColor' => $color, 'backgroundColor' => $color],
            ],
            'legend' => [['color' => $color, 'label' => $title]],
            'yMin' => $yMin,
            'yMax' => $yMax,
        ];
        if ($yCallback) {
            $chart['yCallback'] = $yCallback;
        }

        return $chart;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, float|int>  $data
     * @return array<string, mixed>
     */
    private function lineWithTarget(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $data, int $target, int $yMin, int $yMax): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                ['label' => $title, 'data' => $data, 'borderColor' => $this->brandColor('primary'), 'backgroundColor' => $this->brandColor('primary')],
            ],
            'legend' => [
                ['color' => $this->brandColor('primary'), 'label' => $title],
                ['color' => $this->brandColor('burgundy'), 'label' => __('Target')],
            ],
            'referenceLine' => $target,
            'yMin' => $yMin,
            'yMax' => $yMax,
            'yCallback' => 'percent',
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>  $data
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function horizontalBarChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $data, array $colors): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'bar',
            'indexAxis' => 'y',
            'labels' => $labels,
            'datasets' => [
                ['label' => $title, 'data' => $data, 'backgroundColor' => $colors],
            ],
            'legend' => [],
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, array<string, mixed>>  $datasets
     * @return array<string, mixed>
     */
    private function stackedBarChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $datasets): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'bar',
            'stacked' => true,
            'labels' => $labels,
            'datasets' => $datasets,
            'legend' => collect($datasets)->map(fn (array $ds) => [
                'color' => $ds['backgroundColor'],
                'label' => $ds['label'],
            ])->all(),
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>  $data
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function pieChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $data, array $colors): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'pie',
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
            'legend' => collect($labels)->map(fn (string $label, int $i) => [
                'color' => $colors[$i] ?? $this->brandColor('primary'),
                'label' => $label,
            ])->all(),
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>  $data
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function donutChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $data, array $colors): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'donut',
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
            'legend' => collect($labels)->map(fn (string $label, int $i) => [
                'color' => $colors[$i] ?? $this->brandColor('primary'),
                'label' => $label.' '.($data[$i] ?? 0).'%',
            ])->all(),
        ];
    }

    /**
     * @return Collection<int, int|string>
     */
    private function orgAdminFacilityIds(ProcessorDashboardContext $ctx, ?User $user): Collection
    {
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);

        return Facility::query()->whereIn('business_id', $businessIds)->pluck('id');
    }

    /**
     * @return Collection<int, int|string>
     */
    private function orgAdminPlanIds(ProcessorDashboardContext $ctx, ?User $user): Collection
    {
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);
        $facilityIds = Facility::query()->whereIn('business_id', $businessIds)->pluck('id');

        return SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
    }

    /**
     * @param  Collection<int, int|string>  $planIds
     * @return array{cattle: int, goat: int, sheep: int}
     */
    private function executedBySpeciesTotals(Collection $planIds, array $filters): array
    {
        $query = SlaughterExecution::query()
            ->join('slaughter_plans', 'slaughter_plans.id', '=', 'slaughter_executions.slaughter_plan_id')
            ->whereIn('slaughter_executions.slaughter_plan_id', $planIds)
            ->where('slaughter_executions.status', SlaughterExecution::STATUS_COMPLETED);

        if ($filters['is_filtered'] ?? false) {
            $query->whereDate('slaughter_executions.slaughter_time', '>=', $filters['start']->toDateString())
                ->whereDate('slaughter_executions.slaughter_time', '<=', $filters['end']->toDateString());
        }

        $counts = $query
            ->groupBy('slaughter_plans.species')
            ->selectRaw('slaughter_plans.species as species, SUM(slaughter_executions.actual_animals_slaughtered) as total')
            ->pluck('total', 'species');

        return [
            'cattle' => (int) ($counts[SlaughterPlan::SPECIES_CATTLE] ?? 0),
            'goat' => (int) ($counts[SlaughterPlan::SPECIES_GOAT] ?? 0),
            'sheep' => (int) ($counts[SlaughterPlan::SPECIES_SHEEP] ?? 0),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $facilityIds
     */
    private function intakeTrendEndDate(Collection $facilityIds): CarbonInterface
    {
        $latest = AnimalIntakeItem::query()
            ->whereHas('intake', fn ($q) => $q
                ->whereIn('facility_id', $facilityIds)
                ->where('is_draft', false))
            ->max('created_at');

        if ($latest === null) {
            return now()->startOfDay();
        }

        $latestDay = Carbon::parse($latest)->startOfDay();
        $today = now()->startOfDay();

        return $latestDay->lte($today) ? $latestDay : $today;
    }

    /**
     * @return array<int, string>
     */
    private function weekdayLabelsEndingAt(CarbonInterface $endDate): array
    {
        return collect(range(6, 0))
            ->map(fn (int $i) => $endDate->copy()->subDays($i)->format('D'))
            ->reverse()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function lastWeekdayLabels(): array
    {
        return $this->weekdayLabelsEndingAt(now()->startOfDay());
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    private function dailyIntakeCounts(ProcessorDashboardContext $ctx, array $days): array
    {
        return collect(range(6, 0))->map(fn (int $i) => (int) AnimalIntake::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->whereDate('created_at', now()->subDays($i))
            ->sum('number_of_animals'))->reverse()->values()->all();
    }

    /**
     * @param  Collection<int, int|string>  $facilityIds
     * @return array<int, int>
     */
    private function intakeBySpeciesForFacilities(Collection $facilityIds, string $species, CarbonInterface $endDate): array
    {
        $speciesConstant = match (strtolower($species)) {
            'cattle' => AnimalIntake::SPECIES_CATTLE,
            'goat' => AnimalIntake::SPECIES_GOAT,
            'sheep' => AnimalIntake::SPECIES_SHEEP,
            'pig' => AnimalIntake::SPECIES_PIG,
            default => $species,
        };

        return collect(range(6, 0))->map(function (int $i) use ($facilityIds, $speciesConstant, $endDate): int {
            return (int) AnimalIntakeItem::query()
                ->bySpecies($speciesConstant)
                ->whereHas('intake', fn ($q) => $q
                    ->whereIn('facility_id', $facilityIds)
                    ->where('is_draft', false)
                )
                ->whereDate('animal_intake_items.created_at', $endDate->copy()->subDays($i)->toDateString())
                ->count();
        })->reverse()->values()->all();
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    private function intakeBySpeciesForDays(ProcessorDashboardContext $ctx, string $species, array $days): array
    {
        return $this->intakeBySpeciesForFacilities($ctx->facilityIds, $species, now()->startOfDay());
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    private function dailyAmCounts(ProcessorDashboardContext $ctx, array $days): array
    {
        return collect(range(6, 0))->map(fn (int $i) => (int) AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->whereDate('inspection_date', now()->subDays($i))
            ->count())->reverse()->values()->all();
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    private function dailyPmCounts(ProcessorDashboardContext $ctx, array $days): array
    {
        return collect(range(6, 0))->map(fn (int $i) => (int) PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->whereDate('inspection_date', now()->subDays($i))
            ->count())->reverse()->values()->all();
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    private function dailyCertCounts(ProcessorDashboardContext $ctx, array $days): array
    {
        return collect(range(6, 0))->map(fn (int $i) => (int) Certificate::query()
            ->whereIn('id', $ctx->certificateIds)
            ->whereDate('issued_at', now()->subDays($i))
            ->count())->reverse()->values()->all();
    }

    /**
     * Returns stacked bar chart data showing intake head counts by species over time.
     * Groups by ISO week when $days > 30, by calendar day otherwise.
     * Sources from animal_intake_items joined to animal_intakes.
     * Scoped to facility IDs, non-draft intakes only.
     *
     * @return array{
     *   labels: string[],
     *   datasets: array<array{species: string, data: int[]}>
     * }
     */
    public function intakeTrendBySpecies(ProcessorDashboardContext $ctx, int $days = 90): array
    {
        $start = today()->subDays($days)->startOfDay();
        $end = today()->endOfDay();
        $groupByWeek = $days > 30;

        $items = AnimalIntakeItem::query()
            ->select(['animal_intake_items.species', 'animal_intake_items.created_at'])
            ->whereHas('intake', fn ($q) => $q
                ->whereIn('facility_id', $ctx->facilityIds)
                ->where('is_draft', false)
            )
            ->whereBetween('animal_intake_items.created_at', [$start, $end])
            ->get();

        if ($items->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $periodKeys = [];
        $labels = [];

        if ($groupByWeek) {
            $cursor = $start->copy()->startOfWeek();
            while ($cursor->lte($end)) {
                $key = sprintf('%d-W%02d', $cursor->isoWeekYear(), $cursor->isoWeek());
                if (! in_array($key, $periodKeys, true)) {
                    $periodKeys[] = $key;
                    $labels[] = $key;
                }
                $cursor->addWeek();
            }
        } else {
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $periodKeys[] = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('d M');
                $cursor->addDay();
            }
        }

        $counts = [];
        foreach ($items as $item) {
            $periodKey = $groupByWeek
                ? sprintf('%d-W%02d', $item->created_at->isoWeekYear(), $item->created_at->isoWeek())
                : $item->created_at->format('Y-m-d');

            if (! isset($counts[$item->species])) {
                $counts[$item->species] = [];
            }

            $counts[$item->species][$periodKey] = ($counts[$item->species][$periodKey] ?? 0) + 1;
        }

        $datasets = [];
        foreach ($counts as $species => $periodCounts) {
            $data = [];
            foreach ($periodKeys as $periodKey) {
                $data[] = (int) ($periodCounts[$periodKey] ?? 0);
            }

            $datasets[] = [
                'species' => $species,
                'data' => $data,
            ];
        }

        usort($datasets, fn (array $a, array $b) => array_sum($b['data']) <=> array_sum($a['data']));

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
}
