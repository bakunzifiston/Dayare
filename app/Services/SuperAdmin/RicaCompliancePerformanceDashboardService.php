<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnteMortemInspection;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\SlaughterPlan;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaCompliancePerformanceDashboardService
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
        $previousFilters = $this->previousPeriodFilters($filters);

        $current = $this->metricsSnapshot($filters, $selectedDistrictId);
        $previous = $this->metricsSnapshot($previousFilters, $selectedDistrictId);

        return [
            'filters' => $filters,
            'districtOptions' => $this->districtOptions(),
            'selectedDistrictId' => $selectedDistrictId,
            'kpis' => $this->kpis($current, $previous),
            'chartSpecs' => $this->chartSpecs($current, $filters),
            'slaughterhouseRows' => $current['slaughterhouse_rows'],
            'pmiRows' => $current['pmi_rows'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metricsSnapshot(array $filters, ?int $districtId): array
    {
        $reportSlots = $this->reportSlots($filters, $districtId);
        $statusCounts = [
            'approved' => 0,
            'rejected' => 0,
            'pending' => 0,
            'in_review' => 0,
        ];

        $submittedCount = 0;
        $expectedCount = 0;

        foreach ($reportSlots as $slot) {
            $expectedCount++;
            $bucket = $this->classifyReportStatus($slot['report'], $slot['year'], $slot['month']);
            $statusCounts[$bucket]++;

            if ($slot['report']?->isSubmitted()) {
                $submittedCount++;
            }
        }

        $rejectedKg = $this->rejectedKg($filters, $districtId);
        $approvedKg = $this->approvedKg($filters, $districtId);
        $rejectionRate = $this->rejectionRate($rejectedKg, $approvedKg);
        $activePmis = $this->activePmisCount($filters, $districtId);
        $submissionRate = $expectedCount > 0 ? round($submittedCount / $expectedCount * 100, 1) : 0.0;
        $avgComplianceScore = $this->averageComplianceScore($reportSlots, $filters, $districtId);

        return [
            'active_pmis' => $activePmis,
            'reports_submitted' => $submittedCount,
            'submission_rate' => $submissionRate,
            'avg_compliance_score' => $avgComplianceScore,
            'avg_rejection_rate' => $rejectionRate,
            'status_counts' => $statusCounts,
            'total_reports' => array_sum($statusCounts),
            'slaughterhouse_rows' => $this->slaughterhouseRows($filters, $districtId),
            'pmi_rows' => $this->pmiRows($filters, $districtId),
            'compliance_trend' => $this->complianceTrendSeries($filters, $districtId),
            'submission_trend' => $this->submissionTrendSeries($filters, $districtId),
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
            'active_pmis' => [
                'value' => $current['active_pmis'],
                'trend' => $this->trendDelta($current['active_pmis'], $previous['active_pmis'], positiveIsGood: true),
            ],
            'reports_submitted' => [
                'value' => $current['reports_submitted'],
                'trend' => $this->trendDelta($current['reports_submitted'], $previous['reports_submitted'], positiveIsGood: true),
            ],
            'submission_rate' => [
                'value' => $current['submission_rate'],
                'trend' => $this->trendDeltaPoints($current['submission_rate'], $previous['submission_rate'], positiveIsGood: true),
            ],
            'avg_compliance_score' => [
                'value' => $current['avg_compliance_score'],
                'trend' => $this->trendDeltaPoints($current['avg_compliance_score'], $previous['avg_compliance_score'], positiveIsGood: true),
            ],
            'avg_rejection_rate' => [
                'value' => $current['avg_rejection_rate'],
                'trend' => $this->trendDeltaPoints($current['avg_rejection_rate'], $previous['avg_rejection_rate'], positiveIsGood: false),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array<string, mixed>>
     */
    private function chartSpecs(array $metrics, array $filters): array
    {
        $colors = config('bucha.chart.series', ['#38A169', '#A11D1E', '#D69E2E', '#3182CE', '#718096']);
        $statusCounts = $metrics['status_counts'];
        $totalReports = max(1, (int) $metrics['total_reports']);

        $statusLabels = [
            __('Approved'),
            __('Rejected'),
            __('Pending'),
            __('In review'),
        ];

        $statusData = [
            $statusCounts['approved'],
            $statusCounts['rejected'],
            $statusCounts['pending'],
            $statusCounts['in_review'],
        ];

        $statusColors = ['#38A169', '#A11D1E', '#D69E2E', '#3182CE'];

        $complianceTrend = $metrics['compliance_trend'];
        $submissionTrend = $metrics['submission_trend'];

        return [
            [
                'id' => 'rica-cp-status-donut',
                'title' => __('Report status'),
                'type' => 'donut',
                'height' => 240,
                'labels' => $statusLabels,
                'data' => $statusData,
                'colors' => $statusColors,
                'centerLabel' => __('Total reports'),
                'legendItems' => collect($statusLabels)->map(fn (string $label, int $index) => [
                    'label' => $label,
                    'value' => $statusData[$index],
                    'percent' => round($statusData[$index] / $totalReports * 100, 1),
                ])->values()->all(),
                'emptyMessage' => __('No monthly reports for this period.'),
            ],
            array_merge($complianceTrend, [
                'id' => 'rica-cp-compliance-line',
                'title' => __('Compliance trend'),
                'type' => 'area-line',
                'height' => 240,
                'yMax' => 100,
                'emptyMessage' => __('No compliance trend for this period.'),
            ]),
            array_merge($submissionTrend, [
                'id' => 'rica-cp-submission-line',
                'title' => __('Submission trend'),
                'type' => 'area-line',
                'height' => 240,
                'emptyMessage' => __('No submission trend for this period.'),
            ]),
        ];
    }

    /**
     * @return list<array{
     *     facility_id: int,
     *     year: int,
     *     month: int,
     *     report: RicaMonthlyInspectionReport|null
     * }>
     */
    private function reportSlots(array $filters, ?int $districtId): array
    {
        $months = $this->monthsInRange($filters);
        $facilityIds = $this->eligibleFacilityIds($districtId);
        $slots = [];

        foreach ($months as $month) {
            $activeFacilityIds = $this->facilityIdsWithActivity((int) $month['year'], (int) $month['month'], $facilityIds);

            if ($activeFacilityIds === []) {
                continue;
            }

            $reports = RicaMonthlyInspectionReport::query()
                ->where('period_year', $month['year'])
                ->where('period_month', $month['month'])
                ->whereIn('facility_id', $activeFacilityIds)
                ->get()
                ->keyBy('facility_id');

            foreach ($activeFacilityIds as $facilityId) {
                $slots[] = [
                    'facility_id' => $facilityId,
                    'year' => (int) $month['year'],
                    'month' => (int) $month['month'],
                    'report' => $reports->get($facilityId),
                ];
            }
        }

        return $slots;
    }

    /**
     * @return list<array{year: int, month: int}>
     */
    private function monthsInRange(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $cursor = now()->copy()->subMonths(11)->startOfMonth();
            $end = now()->copy()->startOfMonth();
        } else {
            $cursor = $filters['start']->copy()->startOfMonth();
            $end = $filters['end']->copy()->startOfMonth();
        }

        $months = [];
        while ($cursor->lessThanOrEqualTo($end)) {
            $months[] = ['year' => (int) $cursor->year, 'month' => (int) $cursor->month];
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @return list<int>
     */
    private function eligibleFacilityIds(?int $districtId): array
    {
        $query = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->eligibleForRicaMonthlyReport()
        );

        if ($districtId !== null) {
            $query->where('district_id', $districtId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>  $facilityIds
     * @return list<int>
     */
    private function facilityIdsWithActivity(int $year, int $month, array $facilityIds): array
    {
        if ($facilityIds === []) {
            return [];
        }

        return SlaughterPlan::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereYear('slaughter_date', $year)
            ->whereMonth('slaughter_date', $month)
            ->pluck('facility_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function classifyReportStatus(?RicaMonthlyInspectionReport $report, int $year, int $month): string
    {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $isPastPeriod = $periodEnd->isPast() && ! $periodEnd->isCurrentMonth();

        if ($report === null) {
            return $isPastPeriod ? 'rejected' : 'pending';
        }

        if ($report->isSubmitted()) {
            if ($report->stamp_acknowledged) {
                return 'approved';
            }

            return 'in_review';
        }

        $signatures = $report->normalizedInspectorSignatures();
        $hasProgress = $signatures !== [] || trim((string) $report->operator_name) !== '';

        if (! $hasProgress) {
            return $isPastPeriod ? 'rejected' : 'pending';
        }

        return $isPastPeriod ? 'rejected' : 'in_review';
    }

    /**
     * @param  list<array{facility_id: int, year: int, month: int, report: RicaMonthlyInspectionReport|null}>  $reportSlots
     */
    private function averageComplianceScore(array $reportSlots, array $filters, ?int $districtId): float
    {
        $scores = collect($reportSlots)
            ->groupBy('facility_id')
            ->map(function (Collection $slots, int|string $facilityId) use ($filters): ?float {
                $facilityId = (int) $facilityId;
                $submitted = $slots->contains(fn (array $slot) => $slot['report']?->isSubmitted() ?? false);
                $hasDraftProgress = $slots->contains(function (array $slot): bool {
                    $report = $slot['report'];
                    if ($report === null || $report->isSubmitted()) {
                        return false;
                    }

                    return $report->normalizedInspectorSignatures() !== []
                        || trim((string) $report->operator_name) !== '';
                });

                $submissionScore = $submitted ? 100.0 : ($hasDraftProgress ? 50.0 : 0.0);
                $rejectedKg = $this->rejectedKgForFacility($filters, $facilityId);
                $approvedKg = $this->approvedKgForFacility($filters, $facilityId);
                $rejectionRate = $this->rejectionRate($rejectedKg, $approvedKg);

                return round(0.4 * $submissionScore + 0.6 * (100 - $rejectionRate), 1);
            })
            ->filter();

        if ($scores->isEmpty()) {
            $facilityRows = $this->slaughterhouseRows($filters, $districtId);

            return round(collect($facilityRows)->avg('compliance_score') ?: 0, 1);
        }

        return round($scores->avg(), 1);
    }

    /**
     * @return list<array{
     *     rank: int,
     *     name: string,
     *     reports: int,
     *     compliance_score: float,
     *     rejection_rate: float,
     *     sparkline: array{values: list<float>, direction: string}
     * }>
     */
    private function slaughterhouseRows(array $filters, ?int $districtId): array
    {
        $facilityQuery = TenantEnvironmentScope::applyToFacilities(
            Facility::query()
                ->eligibleForRicaMonthlyReport()
                ->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
        );

        if ($districtId !== null) {
            $facilityQuery->where('district_id', $districtId);
        }

        return $facilityQuery
            ->orderBy('facility_name')
            ->get(['id', 'facility_name'])
            ->map(function (Facility $facility) use ($filters): ?array {
                $facilityId = (int) $facility->id;
                $rejectedKg = $this->rejectedKgForFacility($filters, $facilityId);
                $approvedKg = $this->approvedKgForFacility($filters, $facilityId);
                $totalMeat = $rejectedKg + $approvedKg;

                if ($totalMeat <= 0 && ! $this->facilityHasInspectionActivity($filters, $facilityId)) {
                    return null;
                }

                $rejectionRate = $this->rejectionRate($rejectedKg, $approvedKg);
                $reportsSubmitted = $this->submittedReportsCountForFacility($filters, $facilityId);
                $submissionScore = $reportsSubmitted > 0 ? 100.0 : 0.0;
                $complianceScore = round(0.4 * $submissionScore + 0.6 * (100 - $rejectionRate), 1);

                return [
                    'name' => (string) $facility->facility_name,
                    'reports' => $reportsSubmitted,
                    'compliance_score' => $complianceScore,
                    'rejection_rate' => $rejectionRate,
                    'sparkline' => $this->facilitySparkline($filters, $facilityId),
                ];
            })
            ->filter()
            ->sortByDesc('compliance_score')
            ->values()
            ->take(5)
            ->values()
            ->map(fn (array $row, int $index) => array_merge($row, ['rank' => $index + 1]))
            ->all();
    }

    /**
     * @return list<array{
     *     rank: int,
     *     name: string,
     *     reports: int,
     *     compliance_score: float,
     *     rejection_rate: float,
     *     sparkline: array{values: list<float>, direction: string}
     * }>
     */
    private function pmiRows(array $filters, ?int $districtId): array
    {
        $inspectorIds = $this->activeInspectorIds($filters, $districtId);

        if ($inspectorIds === []) {
            return [];
        }

        return Inspector::query()
            ->whereIn('id', $inspectorIds)
            ->orderBy('first_name')
            ->get()
            ->map(function (Inspector $inspector) use ($filters): ?array {
                $inspectorId = (int) $inspector->id;
                $rejectedKg = $this->rejectedKgForInspector($filters, $inspectorId);
                $approvedKg = $this->approvedKgForInspector($filters, $inspectorId);
                $totalMeat = $rejectedKg + $approvedKg;
                $inspectionCount = $this->inspectionCountForInspector($filters, $inspectorId);

                if ($inspectionCount <= 0) {
                    return null;
                }

                $rejectionRate = $this->rejectionRate($rejectedKg, $approvedKg);
                $reportsSigned = $this->signedReportsCountForInspector($filters, $inspector);
                $submissionScore = $reportsSigned > 0 ? 100.0 : 0.0;
                $complianceScore = round(0.4 * $submissionScore + 0.6 * (100 - $rejectionRate), 1);

                return [
                    'name' => trim($inspector->first_name.' '.$inspector->last_name),
                    'reports' => $reportsSigned,
                    'compliance_score' => $complianceScore,
                    'rejection_rate' => $rejectionRate,
                    'sparkline' => $this->inspectorSparkline($filters, $inspectorId),
                ];
            })
            ->filter()
            ->sortByDesc('compliance_score')
            ->values()
            ->take(5)
            ->values()
            ->map(fn (array $row, int $index) => array_merge($row, ['rank' => $index + 1]))
            ->all();
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function complianceTrendSeries(array $filters, ?int $districtId): array
    {
        $buckets = $this->dailyBuckets($filters);
        $labels = [];
        $data = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $dayFilters = [
                'is_filtered' => true,
                'start' => $bucket['start'],
                'end' => $bucket['end'],
                'period' => 'day',
            ];
            $slots = $this->reportSlots($dayFilters, $districtId);
            $data[] = $this->averageComplianceScore($slots, $dayFilters, $districtId);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __('Average compliance score'),
                'data' => $data,
                'borderColor' => '#A11D1E',
                'backgroundColor' => 'rgba(161, 29, 30, 0.12)',
                'fill' => true,
            ]],
        ];
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function submissionTrendSeries(array $filters, ?int $districtId): array
    {
        $buckets = $this->dailyBuckets($filters);
        $labels = [];
        $data = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $query = RicaMonthlyInspectionReport::query()
                ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
                ->whereBetween('submitted_at', [$bucket['start'], $bucket['end']])
                ->whereHas('facility', function (Builder $facilityQuery) use ($districtId): void {
                    TenantEnvironmentScope::applyToFacilities($facilityQuery);

                    if ($districtId !== null) {
                        $facilityQuery->where('district_id', $districtId);
                    }
                });

            $data[] = (int) $query->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __('Reports submitted'),
                'data' => $data,
                'borderColor' => '#A11D1E',
                'backgroundColor' => 'rgba(161, 29, 30, 0.12)',
                'fill' => true,
            ]],
        ];
    }

    /**
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    private function dailyBuckets(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $start = now()->copy()->startOfMonth();
            $end = now()->copy()->endOfDay();
        } else {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();
        }

        $days = max(1, $start->diffInDays($end) + 1);
        $step = max(1, (int) ceil($days / 12));
        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->addDays($step - 1)->endOfDay();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'label' => $cursor->format('j M'),
                'start' => $cursor->copy()->startOfDay(),
                'end' => $bucketEnd,
            ];

            $cursor = $bucketEnd->copy()->addDay()->startOfDay();
        }

        return $buckets;
    }

    /**
     * @return array{values: list<float>, direction: string}
     */
    private function facilitySparkline(array $filters, int $facilityId): array
    {
        $buckets = $this->weeklyBuckets($filters);
        $values = [];

        foreach ($buckets as $bucket) {
            $bucketFilters = [
                'is_filtered' => true,
                'start' => $bucket['start'],
                'end' => $bucket['end'],
                'period' => 'day',
            ];
            $rejectedKg = $this->rejectedKgForFacility($bucketFilters, $facilityId);
            $approvedKg = $this->approvedKgForFacility($bucketFilters, $facilityId);
            $values[] = round(100 - $this->rejectionRate($rejectedKg, $approvedKg), 1);
        }

        return $this->sparklineTrend($values);
    }

    /**
     * @return array{values: list<float>, direction: string}
     */
    private function inspectorSparkline(array $filters, int $inspectorId): array
    {
        $buckets = $this->weeklyBuckets($filters);
        $values = [];

        foreach ($buckets as $bucket) {
            $bucketFilters = [
                'is_filtered' => true,
                'start' => $bucket['start'],
                'end' => $bucket['end'],
                'period' => 'day',
            ];
            $rejectedKg = $this->rejectedKgForInspector($bucketFilters, $inspectorId);
            $approvedKg = $this->approvedKgForInspector($bucketFilters, $inspectorId);
            $values[] = round(100 - $this->rejectionRate($rejectedKg, $approvedKg), 1);
        }

        return $this->sparklineTrend($values);
    }

    /**
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    private function weeklyBuckets(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $start = now()->copy()->startOfMonth();
            $end = now()->copy()->endOfDay();
        } else {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();
        }

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end) && count($buckets) < 7) {
            $bucketEnd = $cursor->copy()->addDays(6)->endOfDay();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'label' => $cursor->format('j M'),
                'start' => $cursor->copy()->startOfDay(),
                'end' => $bucketEnd,
            ];

            $cursor = $bucketEnd->copy()->addDay()->startOfDay();
        }

        return $buckets;
    }

    /**
     * @param  list<float>  $values
     * @return array{values: list<float>, direction: string}
     */
    private function sparklineTrend(array $values): array
    {
        if ($values === []) {
            return ['values' => [], 'direction' => 'neutral'];
        }

        if (count($values) < 2) {
            return ['values' => $values, 'direction' => 'neutral'];
        }

        $first = $values[0];
        $last = $values[array_key_last($values)];

        return [
            'values' => $values,
            'direction' => $last > $first + 0.5 ? 'up' : ($last < $first - 0.5 ? 'down' : 'neutral'),
        ];
    }

    private function activePmisCount(array $filters, ?int $districtId): int
    {
        return count($this->activeInspectorIds($filters, $districtId));
    }

    /**
     * @return list<int>
     */
    private function activeInspectorIds(array $filters, ?int $districtId): array
    {
        $anteInspectorIds = AnteMortemInspection::query()
            ->where(fn (Builder $query) => $this->applyAnteMortemScope($query, $filters, $districtId))
            ->pluck('inspector_id');

        $postInspectorIds = PostMortemInspection::query()
            ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->pluck('inspector_id');

        return $anteInspectorIds
            ->merge($postInspectorIds)
            ->unique()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function inspectionCountForInspector(array $filters, int $inspectorId): int
    {
        $ante = AnteMortemInspection::query()
            ->where('inspector_id', $inspectorId)
            ->where(fn (Builder $query) => $this->applyAnteMortemScope($query, $filters, null))
            ->count();

        $post = PostMortemInspection::query()
            ->where('inspector_id', $inspectorId)
            ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, null))
            ->count();

        return $ante + $post;
    }

    private function signedReportsCountForInspector(array $filters, Inspector $inspector): int
    {
        $inspectorName = Str::lower(trim($inspector->first_name.' '.$inspector->last_name));
        $months = $this->monthsInRange($filters);

        return RicaMonthlyInspectionReport::query()
            ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
            ->where(function (Builder $query) use ($months): void {
                foreach ($months as $month) {
                    $query->orWhere(function (Builder $inner) use ($month): void {
                        $inner->where('period_year', $month['year'])
                            ->where('period_month', $month['month']);
                    });
                }
            })
            ->whereHas('facility', fn (Builder $facilityQuery) => TenantEnvironmentScope::applyToFacilities($facilityQuery))
            ->get()
            ->filter(function (RicaMonthlyInspectionReport $report) use ($inspectorName): bool {
                foreach ($report->normalizedInspectorSignatures() as $signature) {
                    if (Str::lower(trim((string) ($signature['name'] ?? ''))) === $inspectorName) {
                        return true;
                    }
                }

                return false;
            })
            ->count();
    }

    private function submittedReportsCountForFacility(array $filters, int $facilityId): int
    {
        $months = $this->monthsInRange($filters);

        return RicaMonthlyInspectionReport::query()
            ->where('facility_id', $facilityId)
            ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
            ->where(function (Builder $query) use ($months): void {
                foreach ($months as $month) {
                    $query->orWhere(function (Builder $inner) use ($month): void {
                        $inner->where('period_year', $month['year'])
                            ->where('period_month', $month['month']);
                    });
                }
            })
            ->count();
    }

    private function facilityHasInspectionActivity(array $filters, int $facilityId): bool
    {
        return AnteMortemInspection::query()
                ->whereHas('slaughterPlan', fn (Builder $query) => $query->where('facility_id', $facilityId))
                ->where(fn (Builder $query) => $this->applyAnteMortemScope($query, $filters, null))
                ->exists()
            || PostMortemInspection::query()
                ->whereHas(
                    'batch.slaughterExecution.slaughterPlan',
                    fn (Builder $query) => $query->where('facility_id', $facilityId),
                )
                ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, null))
                ->exists();
    }

    private function rejectedKg(array $filters, ?int $districtId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->condemned()
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => round((float) ($item->condemned_weight_kg ?? $item->carcass_weight_kg ?? 0), 2));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('condemned_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->sum('condemned_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function approvedKg(array $filters, ?int $districtId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->approved()
            ->with(['batchItem'])
            ->whereHas('inspection', fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => $this->approvedMeatKgForItem($item));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('approved_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyPostMortemScope($query, $filters, $districtId))
            ->sum('approved_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function approvedKgForFacility(array $filters, int $facilityId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->approved()
            ->with(['batchItem'])
            ->whereHas(
                'inspection',
                fn (Builder $query) => $this->applyPostMortemScopeForFacility($query, $filters, $facilityId),
            )
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => $this->approvedMeatKgForItem($item));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('approved_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyPostMortemScopeForFacility($query, $filters, $facilityId))
            ->sum('approved_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function rejectedKgForFacility(array $filters, int $facilityId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->condemned()
            ->whereHas(
                'inspection',
                fn (Builder $query) => $this->applyPostMortemScopeForFacility($query, $filters, $facilityId),
            )
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => round((float) ($item->condemned_weight_kg ?? $item->carcass_weight_kg ?? 0), 2));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('condemned_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->where(fn (Builder $query) => $this->applyPostMortemScopeForFacility($query, $filters, $facilityId))
            ->sum('condemned_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function rejectedKgForInspector(array $filters, int $inspectorId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->condemned()
            ->whereHas(
                'inspection',
                fn (Builder $query) => $this->applyPostMortemScopeForInspector($query, $filters, $inspectorId),
            )
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => round((float) ($item->condemned_weight_kg ?? $item->carcass_weight_kg ?? 0), 2));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('inspector_id', $inspectorId)
            ->where('condemned_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->when(
                $filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null,
                fn (Builder $query) => $query->whereBetween('inspection_date', [
                    $filters['start']->copy()->startOfDay()->toDateString(),
                    $filters['end']->copy()->endOfDay()->toDateString(),
                ]),
            )
            ->sum('condemned_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function approvedKgForInspector(array $filters, int $inspectorId): float
    {
        $itemKg = (float) PostMortemInspectionItem::query()
            ->approved()
            ->with(['batchItem'])
            ->whereHas(
                'inspection',
                fn (Builder $query) => $this->applyPostMortemScopeForInspector($query, $filters, $inspectorId),
            )
            ->get()
            ->sum(fn (PostMortemInspectionItem $item) => $this->approvedMeatKgForItem($item));

        $legacyKg = (float) PostMortemInspection::query()
            ->where('inspector_id', $inspectorId)
            ->where('approved_quantity', '>', 0)
            ->whereDoesntHave('inspectionItems')
            ->when(
                $filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null,
                fn (Builder $query) => $query->whereBetween('inspection_date', [
                    $filters['start']->copy()->startOfDay()->toDateString(),
                    $filters['end']->copy()->endOfDay()->toDateString(),
                ]),
            )
            ->sum('approved_quantity');

        return round($itemKg + $legacyKg, 2);
    }

    private function approvedMeatKgForItem(PostMortemInspectionItem $item): float
    {
        $beforeKg = (float) ($item->batchItem?->meat_quantity_kg ?? 0);
        $carcassKg = (float) ($item->carcass_weight_kg ?? 0);
        $carcassPart = $carcassKg > 0 ? $carcassKg : $beforeKg;
        $otherPart = $carcassKg > 0 && $beforeKg > $carcassKg ? $beforeKg - $carcassKg : 0.0;

        return $carcassPart + $otherPart;
    }

    /**
     * @param  Builder<AnteMortemInspection>  $query
     */
    private function applyAnteMortemScope(Builder $query, array $filters, ?int $districtId): void
    {
        $query->whereHas('slaughterPlan.facility', function (Builder $facilityQuery) use ($districtId): void {
            TenantEnvironmentScope::applyToFacilities($facilityQuery);

            if ($districtId !== null) {
                $facilityQuery->where('district_id', $districtId);
            }
        });

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('inspection_date', [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }
    }

    /**
     * @param  Builder<PostMortemInspection>  $query
     */
    private function applyPostMortemScope(Builder $query, array $filters, ?int $districtId): void
    {
        $query->whereHas(
            'batch.slaughterExecution.slaughterPlan.facility',
            function (Builder $facilityQuery) use ($districtId): void {
                TenantEnvironmentScope::applyToFacilities($facilityQuery);

                if ($districtId !== null) {
                    $facilityQuery->where('district_id', $districtId);
                }
            },
        );

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('inspection_date', [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }
    }

    /**
     * @param  Builder<PostMortemInspection>  $query
     */
    private function applyPostMortemScopeForFacility(Builder $query, array $filters, int $facilityId): void
    {
        $query->whereHas(
            'batch.slaughterExecution.slaughterPlan',
            fn (Builder $planQuery) => $planQuery->where('facility_id', $facilityId),
        );

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('inspection_date', [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }
    }

    /**
     * @param  Builder<PostMortemInspection>  $query
     */
    private function applyPostMortemScopeForInspector(Builder $query, array $filters, int $inspectorId): void
    {
        $query->where('inspector_id', $inspectorId);

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween('inspection_date', [
                $filters['start']->copy()->startOfDay()->toDateString(),
                $filters['end']->copy()->endOfDay()->toDateString(),
            ]);
        }
    }

    private function rejectionRate(float $rejectedKg, float $approvedKg): float
    {
        $total = $rejectedKg + $approvedKg;

        return $total > 0 ? round($rejectedKg / $total * 100, 2) : 0.0;
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
     * @param  array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}  $filters
     * @return array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}
     */
    private function previousPeriodFilters(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $end = now()->subMonth()->endOfMonth();
            $start = now()->subMonth()->startOfMonth();

            return [
                'is_filtered' => true,
                'start' => $start,
                'end' => $end,
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
    private function trendDelta(float|int $current, float|int $previous, bool $positiveIsGood = true): array
    {
        if ($previous <= 0) {
            $direction = $current > 0
                ? ($positiveIsGood ? 'up' : 'down')
                : 'neutral';

            return [
                'direction' => $direction,
                'percent' => 0.0,
                'label' => __('vs last month'),
            ];
        }

        $delta = (($current - $previous) / $previous) * 100;
        $rawDirection = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral');
        $direction = $positiveIsGood
            ? $rawDirection
            : ($rawDirection === 'up' ? 'down' : ($rawDirection === 'down' ? 'up' : 'neutral'));

        return [
            'direction' => $direction,
            'percent' => round(abs($delta), 1),
            'label' => __('vs last month'),
        ];
    }

    /**
     * @return array{direction: string, percent: float, label: string}
     */
    private function trendDeltaPoints(float $current, float $previous, bool $positiveIsGood = true): array
    {
        $delta = $current - $previous;
        $rawDirection = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral');
        $direction = $positiveIsGood
            ? $rawDirection
            : ($rawDirection === 'up' ? 'down' : ($rawDirection === 'down' ? 'up' : 'neutral'));

        return [
            'direction' => $direction,
            'percent' => round(abs($delta), 1),
            'label' => __('pp vs last month'),
        ];
    }

    private function normalizeDistrictId(mixed $districtId): ?int
    {
        if ($districtId === null || $districtId === '' || $districtId === 'all') {
            return null;
        }

        return is_numeric($districtId) ? (int) $districtId : null;
    }
}
