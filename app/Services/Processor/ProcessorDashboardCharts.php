<?php

namespace App\Services\Processor;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\ColdRoom;
use App\Models\ColdRoomViolation;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\FinanceCostAllocation;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
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
            'operations_manager' => $this->opsManager($ctx, $filters),
            'compliance_officer' => $this->complianceOfficer($ctx, $filters),
            'inspector' => $this->inspector($ctx, $filters, $user),
            'transport_manager' => $this->transportManager($ctx, $filters),
            'accountant' => $this->accountant($businessId, $filters),
            default => $this->orgAdmin($ctx, $filters, $user),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orgAdmin(ProcessorDashboardContext $ctx, ?array $filters = null, ?User $user = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];
        $facilityIds = $this->orgAdminFacilityIds($ctx, $user);
        $planIds = $this->orgAdminPlanIds($ctx, $user);
        $batchIds = $this->orgAdminBatchIds($ctx, $user);

        $intakeTotal = $this->intakeHeadCountForFacilities($facilityIds, $filters);
        $executionsTotal = $this->executionAnimalsForPlans($planIds, $filters);
        $batchesTotal = $this->filteredQueryCount(
            Batch::query()->whereIn('id', $batchIds),
            'created_at',
            $filters,
        );
        $certifiedTotal = (int) Batch::query()
            ->whereIn('id', $batchIds)
            ->whereHas('certificate')
            ->when($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null, function ($query) use ($filters): void {
                $query->whereBetween('created_at', [
                    $filters['start']->copy()->startOfDay(),
                    $filters['end']->copy()->endOfDay(),
                ]);
            })
            ->count();

        $pipelineLabels = [__('Intake'), __('Executions'), __('Batches'), __('Certified')];
        $pipelineData = [$intakeTotal, $executionsTotal, $batchesTotal, $certifiedTotal];
        $pipelineColors = [
            $this->speciesColor('cattle'),
            $this->speciesColor('goat'),
            $this->speciesColor('sheep'),
            $this->brandColor('primary'),
        ];

        $executed = $this->executedBySpeciesTotals($planIds, $filters);
        $speciesLabels = [__('Cattle'), __('Goat'), __('Sheep')];
        $speciesData = [$executed['cattle'], $executed['goat'], $executed['sheep']];

        return [
            $this->barChart(
                'org-admin-pipeline',
                __('Organization pipeline'),
                220,
                __('Animals received, executions, batches, and certifications for the selected period'),
                $pipelineLabels,
                [[
                    'label' => __('Volume'),
                    'data' => $pipelineData,
                    'backgroundColor' => $pipelineColors,
                ]],
                null,
                collect($pipelineLabels)->map(fn (string $label, int $index) => [
                    'color' => $pipelineColors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            array_merge(
                $this->facilitySpeciesIntakeTrend($facilityIds, $filters, 'org-admin-species-trend'),
                ['emptyMessage' => __('No animal intake for this period.')],
            ),
            array_merge(
                $this->pieChart(
                    'org-admin-species-executed',
                    __('Species executed'),
                    220,
                    __('Animals executed by cattle, goat, and sheep'),
                    $speciesLabels,
                    $speciesData,
                    $this->speciesColors(),
                ),
                ['emptyMessage' => __('No executions for this period.')],
            ),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }|null  $filters
     * @return array<int, array<string, mixed>>
     */
    private function opsManager(ProcessorDashboardContext $ctx, ?array $filters = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];

        $intakeTotal = $this->opsIntakeHeadCount($ctx, $filters);
        $plansTotal = $this->filteredQueryCount(
            SlaughterPlan::query()->whereIn('id', $ctx->planIds),
            'slaughter_date',
            $filters,
        );
        $executionsTotal = $this->opsExecutionAnimals($ctx, $filters);
        $batchesTotal = $this->filteredQueryCount(
            Batch::query()->whereIn('id', $ctx->batchIds),
            'created_at',
            $filters,
        );

        $labels = [__('Intake'), __('Plans'), __('Executions'), __('Batches')];
        $data = [$intakeTotal, $plansTotal, $executionsTotal, $batchesTotal];
        $colors = [
            $this->speciesColor('cattle'),
            $this->speciesColor('goat'),
            $this->speciesColor('sheep'),
            $this->brandColor('primary'),
        ];

        return [
            $this->barChart(
                'ops-pipeline',
                __('Operational pipeline'),
                220,
                __('Animals received, plans, executions, and batches for the selected period'),
                $labels,
                [[
                    'label' => __('Volume'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]],
                null,
                collect($labels)->map(fn (string $label, int $index) => [
                    'color' => $colors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            array_merge(
                $this->pieChart(
                    'ops-pipeline-pie',
                    __('Pipeline share'),
                    220,
                    __('Share of intake, plans, executions, and batches'),
                    $labels,
                    $data,
                    $colors,
                ),
                ['emptyMessage' => __('No pipeline activity for this period.')],
            ),
            array_merge(
                $this->facilitySpeciesIntakeTrend($ctx->facilityIds, $filters, 'ops-species-trend'),
                ['emptyMessage' => __('No animal intake for this period.')],
            ),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }|null  $filters
     * @return array<int, array<string, mixed>>
     */
    private function complianceOfficer(ProcessorDashboardContext $ctx, ?array $filters = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];

        $amTotal = $this->complianceAmCount($ctx, $filters);
        $pmTotal = $this->compliancePmCount($ctx, $filters);
        $violationsTotal = $this->complianceViolationsInPeriod($ctx, $filters);
        $pendingTotal = $this->compliancePendingChecklists($ctx);

        $pipelineLabels = [__('AM'), __('PM'), __('Violations'), __('Pending')];
        $pipelineData = [$amTotal, $pmTotal, $violationsTotal, $pendingTotal];
        $pipelineColors = [
            $this->brandColor('success'),
            $this->brandColor('primary'),
            $this->brandColor('warning'),
            $this->brandColor('muted'),
        ];

        $issueLabels = [__('Temperature'), __('AM pending'), __('PM pending'), __('Transport')];
        $issueData = $this->complianceIssueCategoryCounts($ctx);
        $issueColors = [
            $this->brandColor('primary'),
            $this->brandColor('warning'),
            $this->brandColor('warning'),
            $this->brandColor('muted'),
        ];

        return [
            $this->barChart(
                'compliance-pipeline',
                __('Compliance pipeline'),
                220,
                __('Inspections completed, temperature violations, and pending checklists for the selected period'),
                $pipelineLabels,
                [[
                    'label' => __('Volume'),
                    'data' => $pipelineData,
                    'backgroundColor' => $pipelineColors,
                ]],
                null,
                collect($pipelineLabels)->map(fn (string $label, int $index) => [
                    'color' => $pipelineColors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            array_merge(
                $this->pieChart(
                    'compliance-issues-pie',
                    __('Open issue mix'),
                    220,
                    __('Temperature, checklist, and transport compliance gaps'),
                    $issueLabels,
                    array_values($issueData),
                    $issueColors,
                ),
                ['emptyMessage' => __('No open compliance issues.')],
            ),
            array_merge(
                $this->complianceInspectionTrend($ctx, $filters),
                [
                    'fullWidth' => true,
                    'emptyMessage' => __('No inspections in this period.'),
                ],
            ),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array<string, mixed>
     */
    private function complianceInspectionTrend(ProcessorDashboardContext $ctx, array $filters): array
    {
        [$start, $end, $groupByMonth] = $this->intakeTrendRange($filters);
        [$periodKeys, $labels] = $this->buildTrendPeriods($start, $end, $groupByMonth);

        $amCounts = array_fill_keys($periodKeys, 0);
        $pmCounts = array_fill_keys($periodKeys, 0);

        $amInspections = AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->whereNotNull('inspection_date')
            ->whereBetween('inspection_date', [$start, $end])
            ->get(['inspection_date']);

        foreach ($amInspections as $inspection) {
            $periodKey = $groupByMonth
                ? Carbon::parse($inspection->inspection_date)->format('Y-m')
                : Carbon::parse($inspection->inspection_date)->format('Y-m-d');

            if (isset($amCounts[$periodKey])) {
                $amCounts[$periodKey]++;
            }
        }

        $pmInspections = PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->whereNotNull('inspection_date')
            ->whereBetween('inspection_date', [$start, $end])
            ->get(['inspection_date']);

        foreach ($pmInspections as $inspection) {
            $periodKey = $groupByMonth
                ? Carbon::parse($inspection->inspection_date)->format('Y-m')
                : Carbon::parse($inspection->inspection_date)->format('Y-m-d');

            if (isset($pmCounts[$periodKey])) {
                $pmCounts[$periodKey]++;
            }
        }

        return $this->stackedBarChart(
            'compliance-inspection-trend',
            __('Inspection trend'),
            220,
            __('Ante-mortem and post-mortem inspections over the selected period'),
            $labels,
            [
                $this->coloredBarDataset(
                    __('AM'),
                    array_map(fn (string $key) => (int) $amCounts[$key], $periodKeys),
                    $this->brandColor('success'),
                ),
                $this->coloredBarDataset(
                    __('PM'),
                    array_map(fn (string $key) => (int) $pmCounts[$key], $periodKeys),
                    $this->brandColor('primary'),
                ),
            ],
        );
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function complianceAmCount(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->whereNotNull('inspection_date');
        $this->applyTripDateFilter($query, 'inspection_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function compliancePmCount(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->whereNotNull('inspection_date');
        $this->applyTripDateFilter($query, 'inspection_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function complianceViolationsInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');
        $query = ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->whereNotNull('start_time');
        $this->applyTripDateFilter($query, 'start_time', $filters);

        return (int) $query->count();
    }

    private function compliancePendingChecklists(ProcessorDashboardContext $ctx): int
    {
        $missingAm = (int) SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereDoesntHave('anteMortemInspections')
            ->count();
        $missingPm = (int) Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->whereDoesntHave('postMortemInspection')
            ->count();

        return $missingAm + $missingPm;
    }

    /**
     * @return array{temperature: int, am_pending: int, pm_pending: int, transport: int}
     */
    private function complianceIssueCategoryCounts(ProcessorDashboardContext $ctx): array
    {
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');

        return [
            'temperature' => (int) ColdRoomViolation::query()
                ->whereIn('cold_room_id', $coldRoomIds)
                ->where('status', ColdRoomViolation::STATUS_OPEN)
                ->count(),
            'am_pending' => (int) SlaughterPlan::query()
                ->whereIn('id', $ctx->planIds)
                ->whereDoesntHave('anteMortemInspections')
                ->count(),
            'pm_pending' => (int) Batch::query()
                ->whereIn('id', $ctx->batchIds)
                ->whereDoesntHave('postMortemInspection')
                ->count(),
            'transport' => (int) TransportTrip::query()
                ->whereIn('id', $ctx->tripIds)
                ->whereDoesntHave('deliveryConfirmation')
                ->count(),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }|null  $filters
     * @return array<int, array<string, mixed>>
     */
    private function inspector(ProcessorDashboardContext $ctx, ?array $filters = null, ?User $user = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];
        $inspectorId = $this->resolveInspectorId($ctx, $user);

        $amTotal = $this->filteredQueryCount(
            AnteMortemInspection::query()
                ->whereIn('slaughter_plan_id', $ctx->planIds)
                ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId)),
            'inspection_date',
            $filters,
        );
        $pmTotal = $this->filteredQueryCount(
            PostMortemInspection::query()
                ->whereIn('batch_id', $ctx->batchIds)
                ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId)),
            'inspection_date',
            $filters,
        );
        $certTotal = $this->filteredQueryCount(
            Certificate::query()
                ->whereIn('id', $ctx->certificateIds)
                ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId)),
            'issued_at',
            $filters,
        );

        $labels = [__('AM'), __('PM'), __('Certs')];
        $data = [$amTotal, $pmTotal, $certTotal];
        $colors = [
            $this->speciesColor('cattle'),
            $this->speciesColor('goat'),
            $this->speciesColor('sheep'),
        ];

        return [
            $this->barChart(
                'inspector-workload',
                __('Inspection workload'),
                220,
                __('Ante-mortem, post-mortem, and certificates for the selected period'),
                $labels,
                [[
                    'label' => __('Activities'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]],
                null,
                collect($labels)->map(fn (string $label, int $index) => [
                    'color' => $colors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            $this->pieChart(
                'inspector-workload-pie',
                __('Workload share'),
                220,
                __('Share of ante-mortem, post-mortem, and certificates'),
                $labels,
                $data,
                $colors,
            ),
            array_merge(
                $this->facilitySpeciesIntakeTrend($ctx->facilityIds, $filters, 'inspector-species-trend'),
                ['emptyMessage' => __('No animal intake for this period.')],
            ),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function filteredQueryCount(\Illuminate\Database\Eloquent\Builder $query, string $dateColumn, array $filters): int
    {
        $query->whereNotNull($dateColumn);

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween($dateColumn, [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function opsIntakeHeadCount(ProcessorDashboardContext $ctx, array $filters): int
    {
        return $this->intakeHeadCountForFacilities($ctx->facilityIds, $filters);
    }

    /**
     * @param  Collection<int, int|string>  $facilityIds
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function intakeHeadCountForFacilities(Collection $facilityIds, array $filters): int
    {
        if ($facilityIds->isEmpty()) {
            return 0;
        }

        $query = AnimalIntake::query()
            ->with('items:id,animal_intake_id,species')
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereNotNull('intake_date');

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('intake_date', [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        return (int) $query->get()->sum(function (AnimalIntake $intake): int {
            if ($intake->items->isNotEmpty()) {
                return $intake->items->count();
            }

            return (int) $intake->number_of_animals;
        });
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function opsExecutionAnimals(ProcessorDashboardContext $ctx, array $filters): int
    {
        return $this->executionAnimalsForPlans($ctx->planIds, $filters);
    }

    /**
     * @param  Collection<int, int|string>  $planIds
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function executionAnimalsForPlans(Collection $planIds, array $filters): int
    {
        if ($planIds->isEmpty()) {
            return 0;
        }

        $query = SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $planIds)
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
     * @param  Collection<int, int|string>  $facilityIds
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array<string, mixed>
     */
    private function facilitySpeciesIntakeTrend(Collection $facilityIds, array $filters, string $slug): array
    {
        [$start, $end, $groupByMonth] = $this->intakeTrendRange($filters);
        [$periodKeys, $labels] = $this->buildTrendPeriods($start, $end, $groupByMonth);

        $counts = [
            AnimalIntake::SPECIES_CATTLE => array_fill_keys($periodKeys, 0),
            AnimalIntake::SPECIES_GOAT => array_fill_keys($periodKeys, 0),
            AnimalIntake::SPECIES_SHEEP => array_fill_keys($periodKeys, 0),
        ];

        if ($facilityIds->isNotEmpty()) {
            $intakes = AnimalIntake::query()
                ->with(['items:id,animal_intake_id,species'])
                ->whereIn('facility_id', $facilityIds)
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
        }

        $datasets = [
            $this->coloredBarDataset(
                __('Cattle'),
                array_map(fn (string $key) => (int) $counts[AnimalIntake::SPECIES_CATTLE][$key], $periodKeys),
                $this->speciesColor('cattle'),
            ),
            $this->coloredBarDataset(
                __('Goat'),
                array_map(fn (string $key) => (int) $counts[AnimalIntake::SPECIES_GOAT][$key], $periodKeys),
                $this->speciesColor('goat'),
            ),
            $this->coloredBarDataset(
                __('Sheep'),
                array_map(fn (string $key) => (int) $counts[AnimalIntake::SPECIES_SHEEP][$key], $periodKeys),
                $this->speciesColor('sheep'),
            ),
        ];

        return $this->stackedBarChart(
            $slug,
            __('Trend species received'),
            220,
            __('Animal intake by cattle, goat, and sheep over the selected period'),
            $labels,
            $datasets,
        );
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array{0: Carbon, 1: Carbon, 2: bool}
     */
    private function intakeTrendRange(array $filters): array
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();
        } else {
            $end = now()->endOfDay();
            $start = now()->subMonths(5)->startOfMonth()->startOfDay();
        }

        return [$start, $end, $start->diffInDays($end) > 60];
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

    private function resolveInspectorId(ProcessorDashboardContext $ctx, ?User $user): ?int
    {
        if ($user === null || $user->email === null) {
            return null;
        }

        return Inspector::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->where('email', $user->email)
            ->value('id');
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }|null  $filters
     * @return array<int, array<string, mixed>>
     */
    private function transportManager(ProcessorDashboardContext $ctx, ?array $filters = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];

        $tripsTotal = $this->transportTripsInPeriod($ctx, $filters);
        $confirmedTotal = $this->transportConfirmedInPeriod($ctx, $filters);
        $pendingTotal = $this->transportPendingInPeriod($ctx, $filters);
        $inTransitTotal = $this->transportInTransitInPeriod($ctx, $filters);

        $labels = [__('Trips'), __('Confirmed'), __('Pending'), __('In transit')];
        $data = [$tripsTotal, $confirmedTotal, $pendingTotal, $inTransitTotal];
        $colors = [
            $this->brandColor('primary'),
            $this->brandColor('success'),
            $this->brandColor('warning'),
            $this->brandColor('muted'),
        ];

        return [
            $this->barChart(
                'transport-pipeline',
                __('Delivery pipeline'),
                220,
                __('Trips, confirmations, pending deliveries, and in-transit trips for the selected period'),
                $labels,
                [[
                    'label' => __('Volume'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]],
                null,
                collect($labels)->map(fn (string $label, int $index) => [
                    'color' => $colors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            array_merge(
                $this->pieChart(
                    'transport-pipeline-pie',
                    __('Delivery share'),
                    220,
                    __('Share of trips, confirmations, pending, and in-transit activity'),
                    $labels,
                    $data,
                    $colors,
                ),
                ['emptyMessage' => __('No transport activity for this period.')],
            ),
            array_merge(
                $this->transportDomesticExportTrend($ctx, $filters),
                [
                    'fullWidth' => true,
                    'emptyMessage' => __('No trips in this period.'),
                ],
            ),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array<string, mixed>
     */
    private function transportDomesticExportTrend(ProcessorDashboardContext $ctx, array $filters): array
    {
        [$start, $end, $groupByMonth] = $this->intakeTrendRange($filters);
        [$periodKeys, $labels] = $this->buildTrendPeriods($start, $end, $groupByMonth);
        $domestic = strtoupper((string) config('processor.domestic_country', 'RW'));

        $domesticCounts = array_fill_keys($periodKeys, 0);
        $exportCounts = array_fill_keys($periodKeys, 0);

        $trips = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('departure_date', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end): void {
                        $fallback->whereNull('departure_date')->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->get(['departure_date', 'created_at', 'destination_country']);

        foreach ($trips as $trip) {
            $date = $trip->departure_date ?? $trip->created_at;
            if ($date === null) {
                continue;
            }

            $periodKey = $groupByMonth
                ? Carbon::parse($date)->format('Y-m')
                : Carbon::parse($date)->format('Y-m-d');

            if (! isset($domesticCounts[$periodKey])) {
                continue;
            }

            $isExport = filled($trip->destination_country) && strtoupper((string) $trip->destination_country) !== $domestic;
            if ($isExport) {
                $exportCounts[$periodKey]++;
            } else {
                $domesticCounts[$periodKey]++;
            }
        }

        return $this->stackedBarChart(
            'transport-domestic-export-trend',
            __('Domestic vs export trips'),
            220,
            __('Trip volume by domestic and export destinations over the selected period'),
            $labels,
            [
                $this->coloredBarDataset(
                    __('Domestic'),
                    array_map(fn (string $key) => (int) $domesticCounts[$key], $periodKeys),
                    $this->brandColor('primary'),
                ),
                $this->coloredBarDataset(
                    __('Export'),
                    array_map(fn (string $key) => (int) $exportCounts[$key], $periodKeys),
                    $this->brandColor('warning'),
                ),
            ],
        );
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function transportTripsInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()->whereIn('id', $ctx->tripIds);
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function transportConfirmedInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereNotNull('received_date');
        $this->applyTripDateFilter($query, 'received_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function transportPendingInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where(function ($q): void {
                $q->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($d) => $d->where('confirmation_status', '!=', DeliveryConfirmation::STATUS_CONFIRMED));
            });
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function transportInTransitInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where('status', TransportTrip::STATUS_IN_TRANSIT);
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function applyTripDateFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();

            $query->where(function ($q) use ($column, $start, $end): void {
                $q->whereBetween($column, [$start, $end])
                    ->orWhere(function ($fallback) use ($column, $start, $end): void {
                        $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }|null  $filters
     * @return array<int, array<string, mixed>>
     */
    private function accountant(int $businessId, ?array $filters = null): array
    {
        $filters = $filters ?? ['is_filtered' => false, 'start' => null, 'end' => null];

        $revenueTotal = $this->financeRevenueTotal($businessId, $filters);
        $payablesTotal = $this->financePayablesTotal($businessId, $filters);
        $collectedTotal = $this->financeCollectedTotal($businessId, $filters);
        $allocationsTotal = $this->financeAllocationsTotal($businessId, $filters);

        $pipelineLabels = [__('Revenue'), __('Payables'), __('Collected'), __('Allocations')];
        $pipelineData = [$revenueTotal, $payablesTotal, $collectedTotal, $allocationsTotal];
        $pipelineColors = [
            $this->brandColor('success'),
            $this->brandColor('primary'),
            $this->brandColor('warning'),
            $this->brandColor('muted'),
        ];

        $arStatus = $this->financeArStatusCounts($businessId, $filters);
        $arLabels = [__('Paid'), __('Pending'), __('Overdue')];
        $arData = [$arStatus['paid'], $arStatus['pending'], $arStatus['overdue']];
        $arColors = [
            $this->brandColor('success'),
            $this->brandColor('warning'),
            $this->brandColor('primary'),
        ];

        return [
            $this->barChart(
                'finance-pipeline',
                __('Finance pipeline'),
                220,
                __('Revenue, payables, collections, and cost allocations for the selected period'),
                $pipelineLabels,
                [[
                    'label' => __('RWF'),
                    'data' => $pipelineData,
                    'backgroundColor' => $pipelineColors,
                ]],
                null,
                collect($pipelineLabels)->map(fn (string $label, int $index) => [
                    'color' => $pipelineColors[$index] ?? $this->brandColor('primary'),
                    'label' => $label,
                ])->all(),
            ),
            array_merge(
                $this->pieChart(
                    'finance-ar-status',
                    __('AR invoice status'),
                    220,
                    __('Paid, pending, and overdue invoices for the selected period'),
                    $arLabels,
                    $arData,
                    $arColors,
                ),
                ['emptyMessage' => __('No invoices for this period.')],
            ),
            array_merge(
                $this->accountantFinanceTrend($businessId, $filters),
                [
                    'fullWidth' => true,
                    'emptyMessage' => __('No finance activity for this period.'),
                ],
            ),
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array<string, mixed>
     */
    private function accountantFinanceTrend(int $businessId, array $filters): array
    {
        [$start, $end, $groupByMonth] = $this->intakeTrendRange($filters);
        [$periodKeys, $labels] = $this->buildTrendPeriods($start, $end, $groupByMonth);

        $revenueCounts = array_fill_keys($periodKeys, 0.0);
        $payableCounts = array_fill_keys($periodKeys, 0.0);

        $invoices = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('issued_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end): void {
                        $fallback->whereNull('issued_at')->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->get(['total_amount', 'issued_at', 'created_at']);

        foreach ($invoices as $invoice) {
            $date = $invoice->issued_at ?? $invoice->created_at;
            if ($date === null) {
                continue;
            }

            $periodKey = $groupByMonth
                ? Carbon::parse($date)->format('Y-m')
                : Carbon::parse($date)->format('Y-m-d');

            if (! isset($revenueCounts[$periodKey])) {
                continue;
            }

            $revenueCounts[$periodKey] += (float) $invoice->total_amount;
        }

        $payables = FinancePayable::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('issued_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end): void {
                        $fallback->whereNull('issued_at')->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->get(['total_amount', 'issued_at', 'created_at']);

        foreach ($payables as $payable) {
            $date = $payable->issued_at ?? $payable->created_at;
            if ($date === null) {
                continue;
            }

            $periodKey = $groupByMonth
                ? Carbon::parse($date)->format('Y-m')
                : Carbon::parse($date)->format('Y-m-d');

            if (! isset($payableCounts[$periodKey])) {
                continue;
            }

            $payableCounts[$periodKey] += (float) $payable->total_amount;
        }

        $toMillions = static fn (float $amount): float => round($amount / 1_000_000, 1);

        return $this->barChart(
            'finance-revenue-payables-trend',
            __('Revenue vs payables trend'),
            220,
            __('Monthly revenue and payables in RWF millions'),
            $labels,
            [
                $this->coloredBarDataset(
                    __('Revenue'),
                    array_map(fn (string $key) => $toMillions($revenueCounts[$key]), $periodKeys),
                    $this->brandColor('success'),
                ),
                $this->coloredBarDataset(
                    __('Payables'),
                    array_map(fn (string $key) => $toMillions($payableCounts[$key]), $periodKeys),
                    $this->brandColor('primary'),
                ),
            ],
            'millions',
            [
                ['color' => $this->brandColor('success'), 'label' => __('Revenue')],
                ['color' => $this->brandColor('primary'), 'label' => __('Payables')],
            ],
        );
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function financeRevenueTotal(int $businessId, array $filters): int
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return (int) round((float) $query->sum('total_amount'));
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function financePayablesTotal(int $businessId, array $filters): int
    {
        $query = FinancePayable::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return (int) round((float) $query->sum('total_amount'));
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function financeCollectedTotal(int $businessId, array $filters): int
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return (int) round((float) $query->sum('amount_paid'));
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function financeAllocationsTotal(int $businessId, array $filters): int
    {
        $query = FinanceCostAllocation::query()->where('business_id', $businessId);

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('allocation_date', [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }

        return (int) round((float) $query->sum('amount'));
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     * @return array{paid: int, pending: int, overdue: int}
     */
    private function financeArStatusCounts(int $businessId, array $filters): array
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        $counts = ['paid' => 0, 'pending' => 0, 'overdue' => 0];

        foreach ($query->get(['total_amount', 'amount_paid', 'due_date']) as $invoice) {
            $outstanding = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);

            if ($outstanding <= 0) {
                $counts['paid']++;
            } elseif ($invoice->due_date && $invoice->due_date->isPast()) {
                $counts['overdue']++;
            } else {
                $counts['pending']++;
            }
        }

        return $counts;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?\Carbon\Carbon,
     *     end: ?\Carbon\Carbon
     * }  $filters
     */
    private function applyFinanceDateFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();

            $query->where(function ($q) use ($column, $start, $end): void {
                $q->whereBetween($column, [$start, $end])
                    ->orWhere(function ($fallback) use ($column, $start, $end): void {
                        $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                    });
            });
        }

        return $query;
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
            'borderColor' => 'transparent',
            'borderWidth' => 0,
            'borderRadius' => 6,
            'maxBarThickness' => 44,
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
            'borderColor' => 'transparent',
            'borderWidth' => 0,
            'borderRadius' => 6,
            'maxBarThickness' => 44,
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
     * @param  array<int, array<string, mixed>>  $datasets
     * @return array<string, mixed>
     */
    private function speciesTrendLineChart(string $slug, string $title, int $height, string $ariaLabel, array $labels, array $datasets): array
    {
        return [
            'id' => 'chart-'.$slug,
            'title' => $title,
            'height' => $height,
            'ariaLabel' => $ariaLabel,
            'type' => 'line',
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
        return SlaughterPlan::query()
            ->whereIn('facility_id', $this->orgAdminFacilityIds($ctx, $user))
            ->pluck('id');
    }

    /**
     * @return Collection<int, int|string>
     */
    private function orgAdminBatchIds(ProcessorDashboardContext $ctx, ?User $user): Collection
    {
        $planIds = $this->orgAdminPlanIds($ctx, $user);

        if ($planIds->isEmpty()) {
            return collect();
        }

        return Batch::query()
            ->whereHas('slaughterExecution', fn ($query) => $query->whereIn('slaughter_plan_id', $planIds))
            ->pluck('id');
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
