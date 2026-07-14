<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Facility;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaSetting;
use App\Models\SlaughterExecution;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaOverviewDashboardService
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
        $selectedFacilityId = $this->normalizeFacilityId($request->query('facility_id'));

        $current = $this->metricsSnapshot($filters, $selectedDistrictId, $selectedFacilityId);
        $previous = $this->metricsSnapshot(
            $this->previousPeriodFilters($filters),
            $selectedDistrictId,
            $selectedFacilityId,
        );

        $slaughterhouseTables = $this->slaughterhouseTables($filters, $selectedDistrictId, $selectedFacilityId);

        return [
            'filters' => $filters,
            'districtOptions' => $this->districtOptions(),
            'facilityOptions' => $this->facilityOptions($selectedDistrictId),
            'selectedDistrictId' => $selectedDistrictId,
            'selectedFacilityId' => $selectedFacilityId,
            'kpis' => $this->kpis($current, $previous),
            'moduleSummaries' => $this->moduleSummaries($current, $request, $selectedDistrictId),
            'chartSpecs' => $this->chartSpecs($current, $filters, $selectedDistrictId, $selectedFacilityId),
            'districtMap' => $this->districtMapData($current),
            'animalsReceivedRows' => $slaughterhouseTables['received'],
            'animalsSlaughteredRows' => $slaughterhouseTables['slaughtered'],
            'insights' => $this->insights($current, $previous, $filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metricsSnapshot(array $filters, ?int $districtId, ?int $facilityId): array
    {
        $intakes = $this->intakeQuery($filters, $districtId, $facilityId)
            ->with(['items:id,animal_intake_id,species', 'facility:id,district_id,district'])
            ->get();

        $animalsReceived = 0;
        $speciesCounts = [
            'cattle' => 0,
            'goat' => 0,
            'sheep' => 0,
            'pig' => 0,
            'other' => 0,
        ];
        $receivedByDistrictId = [];
        $receivedTrend = [];
        $districtIdByName = $this->districtIdByNameMap();

        foreach ($intakes as $intake) {
            $headCount = $this->intakeHeadCount($intake);
            $animalsReceived += $headCount;

            foreach ($this->headCountsBySpeciesFromIntake($intake) as $species => $count) {
                $bucket = $this->normalizeSpeciesBucket($species);
                $speciesCounts[$bucket] += $count;
            }

            $districtKey = $this->resolveFacilityDistrictId($intake->facility, $districtIdByName) ?? 0;
            if ($districtKey > 0) {
                $receivedByDistrictId[$districtKey] = ($receivedByDistrictId[$districtKey] ?? 0) + $headCount;
            }

            if ($intake->intake_date !== null) {
                $dateKey = Carbon::parse($intake->intake_date)->toDateString();
                $receivedTrend[$dateKey] = ($receivedTrend[$dateKey] ?? 0) + $headCount;
            }
        }

        $healthyAnimals = $this->healthyAnimalsCount($filters, $districtId, $facilityId);
        $unhealthyAnimals = $this->unhealthyAnimalsCount($filters, $districtId, $facilityId);
        $meatApprovedKg = $this->meatApprovedKg($filters, $districtId, $facilityId);
        $meatRejectedKg = $this->meatRejectedKg($filters, $districtId, $facilityId);
        $activeSlaughterhouses = $this->activeSlaughterhousesCount($filters, $districtId, $facilityId);
        $activePmis = $this->activePmisCount($filters, $districtId, $facilityId);
        $topRejectionFacility = $this->topRejectionRateFacility($filters, $districtId, $facilityId);
        $topDiseaseCondition = $this->topAnteMortemCondition($filters, $districtId, $facilityId);
        $slaughteredTrend = $this->slaughteredTrendByDate($filters, $districtId, $facilityId);

        return [
            'animals_received' => $animalsReceived,
            'healthy_animals' => $healthyAnimals,
            'unhealthy_animals' => $unhealthyAnimals,
            'meat_approved_kg' => $meatApprovedKg,
            'meat_rejected_kg' => $meatRejectedKg,
            'active_slaughterhouses' => $activeSlaughterhouses,
            'active_pmis' => $activePmis,
            'species_counts' => $speciesCounts,
            'received_by_district_id' => $receivedByDistrictId,
            'received_trend' => $receivedTrend,
            'slaughtered_trend' => $slaughteredTrend,
            'top_rejection_facility' => $topRejectionFacility,
            'top_disease_condition' => $topDiseaseCondition,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function kpis(array $current, array $previous): array
    {
        return [
            'animals_received' => [
                'value' => $current['animals_received'],
                'trend' => $this->trendDelta($current['animals_received'], $previous['animals_received']),
            ],
            'healthy_animals' => [
                'value' => $current['healthy_animals'],
                'trend' => $this->trendDelta($current['healthy_animals'], $previous['healthy_animals'], positiveIsGood: true),
            ],
            'unhealthy_animals' => [
                'value' => $current['unhealthy_animals'],
                'trend' => $this->trendDelta($current['unhealthy_animals'], $previous['unhealthy_animals'], positiveIsGood: false),
            ],
            'meat_approved_kg' => [
                'value' => round($current['meat_approved_kg'], 2),
                'trend' => $this->trendDelta($current['meat_approved_kg'], $previous['meat_approved_kg'], positiveIsGood: true),
            ],
            'meat_rejected_kg' => [
                'value' => round($current['meat_rejected_kg'], 2),
                'trend' => $this->trendDelta($current['meat_rejected_kg'], $previous['meat_rejected_kg'], positiveIsGood: false),
            ],
            'active_slaughterhouses' => [
                'value' => $current['active_slaughterhouses'],
                'trend' => $this->trendDelta($current['active_slaughterhouses'], $previous['active_slaughterhouses'], positiveIsGood: true),
            ],
            'active_pmis' => [
                'value' => $current['active_pmis'],
                'trend' => $this->trendDelta($current['active_pmis'], $previous['active_pmis'], positiveIsGood: true),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array<string, mixed>>
     */
    private function chartSpecs(
        array $metrics,
        array $filters,
        ?int $districtId = null,
        ?int $facilityId = null,
    ): array {
        $colors = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);

        $speciesLabels = [
            'cattle' => __('Cattle'),
            'goat' => __('Goats'),
            'sheep' => __('Sheep'),
            'pig' => __('Pigs'),
            'other' => __('Others'),
        ];

        $speciesData = collect($metrics['species_counts'])
            ->filter(fn (int $count) => $count > 0)
            ->map(fn (int $count, string $key) => [
                'label' => $speciesLabels[$key] ?? ucfirst($key),
                'value' => $count,
            ])
            ->sortByDesc('value')
            ->values();

        $trend = $this->timeSeriesTrendChart(
            $metrics['slaughtered_trend'],
            $filters,
            __('Animals slaughtered'),
        );
        $slaughterSpeciesTrend = $this->slaughterDashboard->chartSpeciesSlaughterTrend($filters, $districtId, $facilityId);
        $slaughterDatasets = collect($slaughterSpeciesTrend['datasets'] ?? [])
            ->filter(fn (array $dataset) => array_sum($dataset['data'] ?? []) > 0)
            ->values()
            ->all();
        $slaughterLegend = collect($slaughterDatasets)->map(fn (array $dataset) => [
            'color' => $dataset['backgroundColor'] ?? '#718096',
            'label' => $dataset['label'] ?? '',
        ])->all();

        return [
            [
                'id' => 'rica-overview-slaughter-species-trend',
                'title' => __('Slaughtered species trend'),
                'type' => 'bar',
                'stacked' => true,
                'height' => 240,
                'labels' => $slaughterSpeciesTrend['labels'] ?? [],
                'datasets' => $slaughterDatasets,
                'legend' => $slaughterLegend,
                'emptyMessage' => __('No slaughter data for this period.'),
            ],
            array_merge($trend, [
                'id' => 'rica-overview-slaughtered-line',
                'title' => __('Animals slaughtered over time'),
                'type' => 'area-line',
                'height' => 240,
                'emptyMessage' => __('No slaughter data for this period.'),
            ]),
            [
                'id' => 'rica-overview-species-donut',
                'title' => __('Animals by species'),
                'type' => 'donut',
                'height' => 240,
                'labels' => $speciesData->pluck('label')->all(),
                'data' => $speciesData->pluck('value')->all(),
                'colors' => $this->chartColors($speciesData->count(), $colors),
                'centerLabel' => __('Total'),
                'legend' => $speciesData->map(fn (array $row, int $index) => [
                    'color' => $colors[$index % count($colors)],
                    'label' => $row['label'],
                ])->all(),
                'emptyMessage' => __('No species breakdown for this period.'),
            ],
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array{
     *     received: list<array{facility_name: string, animals: int}>,
     *     slaughtered: list<array{facility_name: string, animals: int}>
     * }
     */
    private function slaughterhouseTables(array $filters, ?int $districtId, ?int $facilityId): array
    {
        $facilityNames = TenantEnvironmentScope::applyToFacilities(Facility::query())
            ->when($facilityId !== null, fn (Builder $query) => $query->whereKey($facilityId))
            ->when($districtId !== null, fn (Builder $query) => $query->where('district_id', $districtId))
            ->pluck('facility_name', 'id')
            ->map(fn ($name, $id) => (string) ($name ?: __('Facility #:id', ['id' => $id])))
            ->all();

        /** @var array<int, int> $receivedByFacility */
        $receivedByFacility = [];
        $intakes = $this->intakeQuery($filters, $districtId, $facilityId)
            ->with(['items:id,animal_intake_id,species'])
            ->get(['id', 'facility_id', 'species', 'number_of_animals']);

        foreach ($intakes as $intake) {
            $id = (int) $intake->facility_id;
            if ($id <= 0) {
                continue;
            }

            $receivedByFacility[$id] = ($receivedByFacility[$id] ?? 0) + $this->intakeHeadCount($intake);
        }

        /** @var array<int, int> $slaughteredByFacility */
        $slaughteredByFacility = [];
        $executions = $this->slaughterExecutionQuery($filters, $districtId, $facilityId)
            ->with(['slaughterPlan:id,facility_id'])
            ->withCount('executionItems')
            ->get(['id', 'slaughter_plan_id', 'actual_animals_slaughtered']);

        foreach ($executions as $execution) {
            $id = (int) ($execution->slaughterPlan?->facility_id ?? 0);
            if ($id <= 0) {
                continue;
            }

            $headCount = (int) $execution->execution_items_count > 0
                ? (int) $execution->execution_items_count
                : max(0, (int) $execution->actual_animals_slaughtered);

            if ($headCount <= 0) {
                continue;
            }

            $slaughteredByFacility[$id] = ($slaughteredByFacility[$id] ?? 0) + $headCount;
        }

        $toRows = function (array $countsByFacility) use ($facilityNames): array {
            return collect($countsByFacility)
                ->filter(fn (int $count) => $count > 0)
                ->map(fn (int $count, int $facilityId) => [
                    'facility_name' => $facilityNames[$facilityId] ?? __('Facility #:id', ['id' => $facilityId]),
                    'animals' => $count,
                ])
                ->sortByDesc('animals')
                ->values()
                ->take(15)
                ->all();
        };

        return [
            'received' => $toRows($receivedByFacility),
            'slaughtered' => $toRows($slaughteredByFacility),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array{key: string, title: string, description: string, metric_label: string, metric_value: string, glyph: string, href: string}>
     */
    private function moduleSummaries(array $metrics, Request $request, ?int $districtId): array
    {
        $query = [];
        $period = (string) $request->query('period', 'all');
        if ($period !== 'all') {
            $query['period'] = $period;
        }
        if ($districtId !== null) {
            $query['district_id'] = $districtId;
        }

        $alertRequest = Request::create('/rica/alerts-notifications', 'GET', array_filter([
            'district_id' => $districtId,
        ]));
        $alertCount = (int) (app(RicaAlertsNotificationsDashboardService::class)->build($alertRequest)['kpis']['total'] ?? 0);

        $economicLoss = $metrics['meat_rejected_kg'] * $this->lossPerKgRwf();

        return [
            [
                'key' => 'traceability',
                'title' => __('Traceability'),
                'description' => __('Batch journey, origin, certificate, and destination coverage.'),
                'metric_label' => __('Animals tracked'),
                'metric_value' => number_format((int) $metrics['animals_received']),
                'glyph' => 'qrcode',
                'href' => route('rica.traceability', $query),
            ],
            [
                'key' => 'condemnation',
                'title' => __('Meat condemnation'),
                'description' => __('Condemned meat, rejection causes, and estimated economic loss.'),
                'metric_label' => __('Estimated loss'),
                'metric_value' => $this->formatMoney($economicLoss),
                'glyph' => 'trash',
                'href' => route('rica.meat-condemnation', $query),
            ],
            [
                'key' => 'supply-chain',
                'title' => __('Supply chain'),
                'description' => __('Approved meat movement through storage, transport, and delivery.'),
                'metric_label' => __('Approved meat'),
                'metric_value' => number_format((float) $metrics['meat_approved_kg'], 0).' kg',
                'glyph' => 'truck',
                'href' => route('rica.supply-chain', $query),
            ],
            [
                'key' => 'alerts',
                'title' => __('Alerts & notifications'),
                'description' => __('Open regulatory, reporting, cold-chain, and transport alerts.'),
                'metric_label' => __('Open alerts'),
                'metric_value' => number_format($alertCount),
                'glyph' => 'alert',
                'href' => route('rica.alerts-notifications', array_filter(['district_id' => $districtId])),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array{id: int, name: string, count: int, intensity: float}>
     */
    private function districtMapData(array $metrics): array
    {
        $districts = AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = collect($metrics['received_by_district_id']);
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
     * @return array<string, int>
     */
    private function districtIdByNameMap(): array
    {
        return AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name): array => [Str::lower(trim($name)) => $id])
            ->all();
    }

    private function resolveFacilityDistrictId(?Facility $facility, array $districtIdByName): ?int
    {
        if ($facility === null) {
            return null;
        }

        if ($facility->district_id !== null) {
            return (int) $facility->district_id;
        }

        $legacyDistrict = trim((string) ($facility->getRawOriginal('district') ?? ''));
        if ($legacyDistrict === '') {
            return null;
        }

        return $districtIdByName[Str::lower($legacyDistrict)] ?? null;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return list<array{key: string, title: string, message: string, glyph: string}>
     */
    private function insights(array $current, array $previous, array $filters): array
    {
        $periodLabel = $this->insightPeriodLabel($filters);
        $lossPerKg = $this->lossPerKgRwf();
        $economicLoss = $current['meat_rejected_kg'] * $lossPerKg;

        $receivedTrend = $this->trendDelta($current['animals_received'], $previous['animals_received']);
        $unhealthyTrend = $this->trendDelta($current['unhealthy_animals'], $previous['unhealthy_animals'], positiveIsGood: false);

        $insights = [
            [
                'key' => 'animals_received',
                'title' => __('Animals received'),
                'message' => $receivedTrend['direction'] === 'neutral'
                    ? __('Animal intake unchanged compared to the previous period.')
                    : __(':percent% :direction in animal intake compared to the previous period.', [
                        'percent' => $receivedTrend['percent'],
                        'direction' => $receivedTrend['direction'] === 'up' ? __('increase') : __('decrease'),
                    ]),
                'glyph' => 'trending',
            ],
        ];

        if ($current['top_disease_condition'] !== null) {
            $insights[] = [
                'key' => 'disease_alert',
                'title' => __('Disease alert'),
                'message' => __(':condition is the most recorded ante-mortem finding :period.', [
                    'condition' => $current['top_disease_condition'],
                    'period' => $periodLabel,
                ]),
                'glyph' => 'shield',
            ];
        } elseif ($unhealthyTrend['direction'] !== 'neutral') {
            $insights[] = [
                'key' => 'disease_alert',
                'title' => __('Disease alert'),
                'message' => __('Unhealthy animals :direction by :percent% compared to the previous period.', [
                    'direction' => $unhealthyTrend['direction'] === 'up' ? __('increased') : __('decreased'),
                    'percent' => $unhealthyTrend['percent'],
                ]),
                'glyph' => 'shield',
            ];
        }

        if ($current['meat_rejected_kg'] > 0) {
            $insights[] = [
                'key' => 'meat_loss',
                'title' => __('Meat loss'),
                'message' => __(':kg kg of meat condemned :period worth an estimated :loss.', [
                    'kg' => number_format($current['meat_rejected_kg'], 0),
                    'period' => $periodLabel,
                    'loss' => $this->formatLoss($economicLoss),
                ]),
                'glyph' => 'weight',
            ];
        }

        if ($current['top_rejection_facility'] !== null) {
            $facility = $current['top_rejection_facility'];
            $insights[] = [
                'key' => 'performance',
                'title' => __('Performance'),
                'message' => __(':facility has the highest rejection rate (:rate%).', [
                    'facility' => $facility['facility_name'],
                    'rate' => number_format($facility['rejection_rate'], 1),
                ]),
                'glyph' => 'chart',
            ];
        }

        return $insights;
    }

    /**
     * @param  array<string, int>  $dailyTrend
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function timeSeriesTrendChart(array $dailyTrend, array $filters, string $datasetLabel): array
    {
        if ($dailyTrend === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $dates = collect($dailyTrend)->keys()->sort()->values();
        $start = $filters['start'] ?? Carbon::parse($dates->first())->startOfDay();
        $end = $filters['end'] ?? Carbon::parse($dates->last())->endOfDay();
        $start = $start instanceof Carbon ? $start->copy()->startOfDay() : Carbon::parse($start)->startOfDay();
        $end = $end instanceof Carbon ? $end->copy()->endOfDay() : Carbon::parse($end)->endOfDay();

        $days = max(1, $start->diffInDays($end) + 1);
        $bucketCount = min(12, max(4, (int) ceil($days / 7)));
        $bucketSize = (int) ceil($days / $bucketCount);
        $primary = config('bucha.chart.series.0', '#A11D1E');

        $labels = [];
        $data = [];
        $cursor = $start->copy();

        for ($bucket = 0; $bucket < $bucketCount; $bucket++) {
            $bucketStart = $cursor->copy();
            $bucketEnd = $cursor->copy()->addDays($bucketSize - 1)->min($end);
            $labels[] = $bucketStart->format('M j');
            $data[] = collect($dailyTrend)
                ->filter(function (int $count, string $date) use ($bucketStart, $bucketEnd): bool {
                    $parsed = Carbon::parse($date);

                    return $parsed->between($bucketStart, $bucketEnd);
                })
                ->sum();
            $cursor = $bucketEnd->copy()->addDay();
            if ($cursor->gt($end)) {
                break;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => $datasetLabel,
                'data' => $data,
                'borderColor' => $primary,
                'backgroundColor' => 'rgba(161, 29, 30, 0.12)',
                'fill' => true,
            ]],
        ];
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<string, int>
     */
    private function slaughteredTrendByDate(array $filters, ?int $districtId, ?int $facilityId): array
    {
        $slaughteredTrend = [];

        $executions = $this->slaughterExecutionQuery($filters, $districtId, $facilityId)
            ->withCount('executionItems')
            ->get(['id', 'slaughter_time', 'actual_animals_slaughtered']);

        foreach ($executions as $execution) {
            if ($execution->slaughter_time === null) {
                continue;
            }

            $headCount = (int) $execution->execution_items_count > 0
                ? (int) $execution->execution_items_count
                : max(0, (int) $execution->actual_animals_slaughtered);

            if ($headCount <= 0) {
                continue;
            }

            $dateKey = Carbon::parse($execution->slaughter_time)->toDateString();
            $slaughteredTrend[$dateKey] = ($slaughteredTrend[$dateKey] ?? 0) + $headCount;
        }

        return $slaughteredTrend;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function slaughterExecutionQuery(array $filters, ?int $districtId, ?int $facilityId): Builder
    {
        $query = TenantEnvironmentScope::applyToSlaughterExecutions(SlaughterExecution::query())
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time');

        $this->applyDateFilter($query, 'slaughter_time', $filters);
        $query->whereHas('slaughterPlan', function (Builder $planQuery) use ($districtId, $facilityId): void {
            TenantEnvironmentScope::applyToSlaughterPlans($planQuery);
            if ($facilityId !== null) {
                $planQuery->where('facility_id', $facilityId);
            }
            if ($districtId !== null) {
                $planQuery->whereHas(
                    'facility',
                    fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId),
                );
            }
        });

        return $query;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function intakeQuery(array $filters, ?int $districtId, ?int $facilityId): Builder
    {
        $query = TenantEnvironmentScope::applyToAnimalIntakes(AnimalIntake::query())
            ->where('is_draft', false)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereNotNull('intake_date');

        $this->applyDateFilter($query, 'intake_date', $filters);
        $this->applyFacilityScope($query, $districtId, $facilityId, 'facility');

        return $query;
    }

  private function healthyAnimalsCount(array $filters, ?int $districtId, ?int $facilityId): int
    {
        $itemCount = (int) AnteMortemInspectionItem::query()
            ->approved()
            ->whereHas('inspection', fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->count();

        $legacyCount = (int) AnteMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('number_approved');

        return $itemCount + $legacyCount;
    }

    private function unhealthyAnimalsCount(array $filters, ?int $districtId, ?int $facilityId): int
    {
        $itemCount = (int) AnteMortemInspectionItem::query()
            ->whereIn('outcome', [
                AnteMortemInspectionItem::OUTCOME_REJECTED,
                AnteMortemInspectionItem::OUTCOME_DEFERRED,
            ])
            ->whereHas('inspection', fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->count();

        $legacyCount = (int) AnteMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('number_rejected');

        return $itemCount + $legacyCount;
    }

    private function meatApprovedKg(array $filters, ?int $districtId, ?int $facilityId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->approved()
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('carcass_weight_kg');

        $legacyKg = (float) PostMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyPostMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('approved_quantity');

        return $itemKg + $legacyKg;
    }

    private function meatRejectedKg(array $filters, ?int $districtId, ?int $facilityId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->condemned()
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('carcass_weight_kg');

        $legacyKg = (float) PostMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where('condemned_quantity', '>', 0)
            ->where(fn (Builder $query) => $this->applyPostMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->sum('condemned_quantity');

        return $itemKg + $legacyKg;
    }

    private function activeSlaughterhousesCount(array $filters, ?int $districtId, ?int $facilityId): int
    {
        $intakeFacilityIds = $this->intakeQuery($filters, $districtId, $facilityId)
            ->distinct()
            ->pluck('facility_id');

        $executionQuery = TenantEnvironmentScope::applyToSlaughterExecutions(SlaughterExecution::query())
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time')
            ->whereHas('slaughterPlan', function (Builder $planQuery) use ($districtId, $facilityId): void {
                TenantEnvironmentScope::applyToSlaughterPlans($planQuery);
                if ($facilityId !== null) {
                    $planQuery->where('facility_id', $facilityId);
                }
                if ($districtId !== null) {
                    $planQuery->whereHas('facility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId));
                }
            });

        $this->applyDateFilter($executionQuery, 'slaughter_time', $filters);

        $executionFacilityIds = $executionQuery
            ->with('slaughterPlan:id,facility_id')
            ->get(['id', 'slaughter_plan_id'])
            ->pluck('slaughterPlan.facility_id')
            ->filter();

        return $intakeFacilityIds->merge($executionFacilityIds)->unique()->filter()->count();
    }

    private function activePmisCount(array $filters, ?int $districtId, ?int $facilityId): int
    {
        $anteInspectorIds = AnteMortemInspection::query()
            ->where(fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->pluck('inspector_id');

        $postInspectorIds = PostMortemInspection::query()
            ->where(fn (Builder $query) => $this->applyPostMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->pluck('inspector_id');

        return $anteInspectorIds->merge($postInspectorIds)->unique()->filter()->count();
    }

    /**
     * @return array{facility_name: string, rejection_rate: float}|null
     */
    private function topRejectionRateFacility(array $filters, ?int $districtId, ?int $facilityId): ?array
    {
        $facilityQuery = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
        );

        if ($districtId !== null) {
            $facilityQuery->where('district_id', $districtId);
        }
        if ($facilityId !== null) {
            $facilityQuery->whereKey($facilityId);
        }

        $top = $facilityQuery
            ->get(['id', 'facility_name'])
            ->map(function (Facility $facility) use ($filters): ?array {
                $approved = $this->meatApprovedKgForFacility($filters, (int) $facility->id);
                $rejected = $this->meatRejectedKgForFacility($filters, (int) $facility->id);
                $total = $approved + $rejected;
                if ($total <= 0) {
                    return null;
                }

                return [
                    'facility_name' => (string) $facility->facility_name,
                    'rejection_rate' => round($rejected / $total * 100, 2),
                ];
            })
            ->filter()
            ->sortByDesc('rejection_rate')
            ->first();

        return is_array($top) ? $top : null;
    }

    private function meatApprovedKgForFacility(array $filters, int $facilityId): float
    {
        return $this->meatApprovedKg($filters, null, $facilityId);
    }

    private function meatRejectedKgForFacility(array $filters, int $facilityId): float
    {
        return $this->meatRejectedKg($filters, null, $facilityId);
    }

    private function topAnteMortemCondition(array $filters, ?int $districtId, ?int $facilityId): ?string
    {
        $reasons = AnteMortemInspectionItem::query()
            ->whereIn('outcome', [
                AnteMortemInspectionItem::OUTCOME_REJECTED,
                AnteMortemInspectionItem::OUTCOME_DEFERRED,
            ])
            ->whereHas('inspection', fn (Builder $query) => $this->applyAnteMortemInspectionScope($query, $filters, $districtId, $facilityId))
            ->pluck('conditions')
            ->map(fn (?string $reason) => trim((string) $reason))
            ->filter()
            ->countBy()
            ->sortDesc();

        $top = $reasons->keys()->first();

        return is_string($top) && $top !== '' ? Str::limit($top, 80) : null;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyAnteMortemInspectionScope(
        Builder $query,
        array $filters,
        ?int $districtId,
        ?int $facilityId,
    ): void {
        $this->applyDateFilter($query, 'inspection_date', $filters);
        $query->whereHas('slaughterPlan', function (Builder $planQuery) use ($districtId, $facilityId): void {
            TenantEnvironmentScope::applyToSlaughterPlans($planQuery);
            if ($facilityId !== null) {
                $planQuery->where('facility_id', $facilityId);
            }
            if ($districtId !== null) {
                $planQuery->whereHas('facility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId));
            }
        });
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyPostMortemInspectionScope(
        Builder $query,
        array $filters,
        ?int $districtId,
        ?int $facilityId,
    ): void {
        $this->applyDateFilter($query, 'inspection_date', $filters);
        $query->whereHas(
            'batch.slaughterExecution.slaughterPlan.facility',
            function (Builder $facilityQuery) use ($districtId, $facilityId): void {
                TenantEnvironmentScope::applyToFacilities($facilityQuery);
                if ($facilityId !== null) {
                    $facilityQuery->whereKey($facilityId);
                }
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
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyFacilityScope(
        Builder $query,
        ?int $districtId,
        ?int $facilityId,
        string $relation,
    ): void {
        if ($facilityId !== null) {
            $query->where($relation === 'facility' ? 'facility_id' : "{$relation}_id", $facilityId);
        }

        if ($districtId !== null) {
            $query->whereHas($relation, fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId));
        }
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

    private function intakeHeadCount(AnimalIntake $intake): int
    {
        return array_sum($this->headCountsBySpeciesFromIntake($intake));
    }

    private function normalizeSpeciesBucket(string $species): string
    {
        $normalized = Str::lower(trim($species));

        return match (true) {
            str_contains($normalized, 'cattle'), str_contains($normalized, 'cow') => 'cattle',
            str_contains($normalized, 'goat') => 'goat',
            str_contains($normalized, 'sheep') => 'sheep',
            str_contains($normalized, 'pig') => 'pig',
            default => 'other',
        };
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
     * @return array<int, string>
     */
    private function facilityOptions(?int $districtId): array
    {
        $query = TenantEnvironmentScope::applyToFacilities(
            Facility::query()
                ->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
                ->orderBy('facility_name')
        );

        if ($districtId !== null) {
            $query->where('district_id', $districtId);
        }

        return $query->pluck('facility_name', 'id')->all();
    }

    private function lossPerKgRwf(): float
    {
        return RicaSetting::condemnationLossPerKgRwf();
    }

    private function formatLoss(float $amount): string
    {
        if ($amount >= 1_000_000) {
            return 'RWF '.number_format($amount / 1_000_000, 1).'M';
        }

        if ($amount >= 1_000) {
            return 'RWF '.number_format($amount / 1_000, 1).'K';
        }

        return 'RWF '.number_format($amount, 0);
    }

    private function insightPeriodLabel(array $filters): string
    {
        return match ($filters['period'] ?? 'all') {
            'day' => __('today'),
            'month' => __('this month'),
            'year' => __('this year'),
            default => Str::lower((string) ($filters['range_label'] ?? __('this period'))),
        };
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

    /**
     * @return array{direction: string, percent: float, label: string}
     */
    private function trendDelta(float|int $current, float|int $previous, bool $positiveIsGood = true): array
    {
        if ($previous <= 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'neutral',
                'percent' => 0.0,
                'label' => __('vs previous period'),
                'sentiment' => $current > 0 ? ($positiveIsGood ? 'good' : 'bad') : 'neutral',
            ];
        }

        $delta = (($current - $previous) / $previous) * 100;
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral');
        $sentiment = match (true) {
            $direction === 'neutral' => 'neutral',
            $direction === 'up' => $positiveIsGood ? 'good' : 'bad',
            default => $positiveIsGood ? 'bad' : 'good',
        };

        return [
            'direction' => $direction,
            'percent' => round(abs($delta), 1),
            'label' => __('vs previous period'),
            'sentiment' => $sentiment,
        ];
    }

    private function formatMoney(float $amount): string
    {
        if ($amount >= 1_000_000) {
            return 'RWF '.number_format($amount / 1_000_000, 1).'M';
        }

        if ($amount >= 1_000) {
            return 'RWF '.number_format($amount / 1_000, 1).'K';
        }

        return 'RWF '.number_format($amount, 0);
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     period: string
     * }  $filters
     * @return array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     period: string,
     *     range_label?: string,
     *     slaughter_label?: string,
     *     has_custom_range?: bool,
     *     date_from?: string,
     *     date_to?: string
     * }
     */
    private function previousPeriodFilters(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $end = now()->subMonth()->endOfMonth();
            $start = now()->subMonth()->startOfMonth();

            return array_merge($filters, [
                'is_filtered' => true,
                'start' => $start,
                'end' => $end,
                'period' => 'month',
                'range_label' => $start->format('M Y'),
            ]);
        }

        $days = max(1, $filters['start']->diffInDays($filters['end']) + 1);
        $previousEnd = $filters['start']->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return array_merge($filters, [
            'start' => $previousStart,
            'end' => $previousEnd,
        ]);
    }

    private function normalizeDistrictId(mixed $districtId): ?int
    {
        if ($districtId === null || $districtId === '' || $districtId === 'all') {
            return null;
        }

        return is_numeric($districtId) ? (int) $districtId : null;
    }

    private function normalizeFacilityId(mixed $facilityId): ?int
    {
        if ($facilityId === null || $facilityId === '' || $facilityId === 'all') {
            return null;
        }

        return is_numeric($facilityId) ? (int) $facilityId : null;
    }
}
