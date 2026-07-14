<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\RicaSetting;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportService;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportPdfService;
use App\Services\SuperAdmin\RicaAlertsNotificationsDashboardService;
use App\Services\SuperAdmin\RicaCompliancePerformanceDashboardService;
use App\Services\SuperAdmin\RicaDiseaseIntelligenceDashboardService;
use App\Services\SuperAdmin\RicaCondemnationDashboardService;
use App\Services\SuperAdmin\RicaOverviewDashboardService;
use App\Services\SuperAdmin\RicaReportService;
use App\Services\SuperAdmin\RicaTraceabilityDashboardService;
use App\Services\SuperAdmin\RicaSupplyChainDashboardService;
use App\Services\SuperAdmin\SuperAdminSlaughterDashboardService;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RicaController extends Controller
{
    public function __construct(
        private readonly RicaReportService $reportService,
        private readonly RicaMonthlyInspectionReportService $monthlyReportService,
        private readonly RicaMonthlyInspectionReportPdfService $monthlyReportPdfService,
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
    ) {}

    /**
     * All slaughterhouses across all businesses — no tenant scoping.
     */
    private function slaughterhouseQuery(): Builder
    {
        return Facility::query()
            ->eligibleForRicaMonthlyReport()
            ->with('business');
    }

    private function assertRicaMonthlyReportFacility(Facility $facility): void
    {
        abort_unless(
            $facility->facility_type === Facility::TYPE_SLAUGHTERHOUSE
            || $facility->slaughterPlans()->exists(),
            404,
        );
    }

    /**
     * Get all slaughter plan IDs for a given set of facility IDs.
     */
    private function planIdsForFacilities(Collection $facilityIds): Collection
    {
        return SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id');
    }

    private function applyRicaTenantScope(): void
    {
        TenantEnvironmentScope::setFilter(
            RicaSetting::get('default_tenant_environment', TenantEnvironmentScope::FILTER_ALL)
        );
    }

    private function applyRicaDashboardDefaults(Request $request): Request
    {
        if ($request->hasAny(['period', 'date_from', 'date_to'])) {
            return $request;
        }

        $defaultPeriod = RicaSetting::get('default_dashboard_period', 'all');
        if ($defaultPeriod === 'all') {
            return $request;
        }

        return $request->duplicate(
            query: array_merge($request->query(), ['period' => $defaultPeriod])
        );
    }

    public function hub(Request $request): View
    {
        $this->applyRicaTenantScope();
        $request = $this->applyRicaDashboardDefaults($request);

        $dashboard = app(RicaOverviewDashboardService::class)->build($request);

        $pageTitle = __('National Meat Inspection Overview');

        return view('superadmin.rica.hub', array_merge($dashboard, compact('pageTitle')));
    }

    public function traceability(Request $request): View
    {
        $this->applyRicaTenantScope();
        $request = $this->applyRicaDashboardDefaults($request);

        $dashboard = app(RicaTraceabilityDashboardService::class)->build($request);

        return view('superadmin.rica.traceability', $dashboard);
    }

    public function diseasesIntelligence(Request $request): View
    {
        $this->applyRicaTenantScope();

        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            $request = $request->duplicate(
                query: array_merge($request->query(), ['period' => 'all'])
            );
        }

        $dashboard = app(RicaDiseaseIntelligenceDashboardService::class)->build($request);

        return view('superadmin.rica.diseases-intelligence', $dashboard);
    }

    public function supplyChain(Request $request): View
    {
        $this->applyRicaTenantScope();
        $request = $this->applyRicaDashboardDefaults($request);

        $dashboard = app(RicaSupplyChainDashboardService::class)->build($request);

        return view('superadmin.rica.supply-chain', $dashboard);
    }

    public function meatCondemnation(Request $request): View
    {
        $this->applyRicaTenantScope();

        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            $request = $request->duplicate(
                query: array_merge($request->query(), ['period' => 'all'])
            );
        }

        $dashboard = app(RicaCondemnationDashboardService::class)->build($request);

        return view('superadmin.rica.meat-condemnation', $dashboard);
    }

    public function compliancePerformance(Request $request): View
    {
        $this->applyRicaTenantScope();

        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            $request = $request->duplicate(
                query: array_merge($request->query(), ['period' => 'all'])
            );
        }

        $dashboard = app(RicaCompliancePerformanceDashboardService::class)->build($request);

        return view('superadmin.rica.compliance-performance', $dashboard);
    }

    public function alertsNotifications(Request $request): View
    {
        $this->applyRicaTenantScope();

        $dashboard = app(RicaAlertsNotificationsDashboardService::class)->build($request);

        return view('superadmin.rica.alerts-notifications', $dashboard);
    }

    private function renderModule(string $module): View
    {
        $this->applyRicaTenantScope();

        $definition = $this->moduleDefinitions()[$module] ?? abort(404);

        return view('superadmin.rica.modules.show', $definition);
    }

    /**
     * @return array<string, array{title: string, description: string, icon: string, highlights: list<string>}>
     */
    private function moduleDefinitions(): array
    {
        return [
            'diseases-intelligence' => [
                'title' => __('Diseases intelligence'),
                'description' => __('Monitor ante-mortem rejections, post-mortem condemnations, and emerging disease patterns across registered operators.'),
                'icon' => 'shield',
                'highlights' => [
                    __('Species-level condemnation trends'),
                    __('District and operator risk heatmaps'),
                    __('Outbreak early-warning signals'),
                ],
            ],
            'supply-chain' => [
                'title' => __('Supply chain'),
                'description' => __('Track meat movement from slaughter through cold storage, transport, and delivery confirmation.'),
                'icon' => 'truck',
                'highlights' => [
                    __('Certificate-linked dispatch records'),
                    __('Cold-chain compliance by route'),
                    __('Cross-border export visibility'),
                ],
            ],
            'compliance-performance' => [
                'title' => __('Compliance performance'),
                'description' => __('Measure operator adherence to inspection schedules, monthly reporting, and regulatory benchmarks.'),
                'icon' => 'chart',
                'highlights' => [
                    __('Monthly FPU/FRM/018 submission rates'),
                    __('Inspection completion by facility'),
                    __('Operator compliance scorecards'),
                ],
            ],
            'alerts-notifications' => [
                'title' => __('Alerts & notifications'),
                'description' => __('Central inbox for temperature violations, overdue reports, condemned carcass alerts, and licence expiries.'),
                'icon' => 'alert',
                'highlights' => [
                    __('Configurable alert thresholds'),
                    __('District inspector notifications'),
                    __('Acknowledgement and escalation workflow'),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, int|string>  $facilityIds
     * @param  Collection<int, int|string>  $planIds
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array{
     *     total_slaughterhouses: int,
     *     total_operators: int,
     *     animals_slaughtered: int,
     *     meat_kg: float,
     *     condemned: int,
     *     certificates: int
     * }
     */
    private function hubStats(array $filters, Collection $facilityIds, Collection $planIds): array
    {
        $executionFilter = function ($query) use ($planIds, $filters): void {
            $query->whereIn('slaughter_plan_id', $planIds)
                ->where('status', SlaughterExecution::STATUS_COMPLETED)
                ->whereNotNull('slaughter_time');

            if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
                $query->whereBetween('slaughter_time', [
                    $filters['start']->copy()->startOfDay(),
                    $filters['end']->copy()->endOfDay(),
                ]);
            }
        };

        $execItemsBase = SlaughterExecutionItem::whereHas('execution', $executionFilter);

        return [
            'total_slaughterhouses' => $facilityIds->count(),
            'total_operators' => (int) TenantEnvironmentScope::applyToFacilities(
                Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
            )->distinct()->count('business_id'),
            'animals_slaughtered' => (int) SlaughterExecution::whereHas('slaughterPlan', fn ($q) => $q
                ->whereIn('facility_id', $facilityIds))
                ->where('status', SlaughterExecution::STATUS_COMPLETED)
                ->whereNotNull('slaughter_time')
                ->when(
                    $filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null,
                    fn ($q) => $q->whereBetween('slaughter_time', [
                        $filters['start']->copy()->startOfDay(),
                        $filters['end']->copy()->endOfDay(),
                    ])
                )
                ->sum('actual_animals_slaughtered'),
            'meat_kg' => (float) (clone $execItemsBase)->sum('meat_quantity_kg'),
            'condemned' => PostMortemInspectionItem::whereHas(
                'inspection.batch.slaughterExecution',
                $executionFilter
            )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                ->count(),
            'certificates' => Certificate::whereHas(
                'batch.slaughterExecution',
                $executionFilter
            )->count(),
        ];
    }

    public function index(Request $request): View
    {
        $slaughterhouses = $this->slaughterhouseQuery()
            ->withCount('slaughterPlans')
            ->when($request->business_id, fn ($q) => $q->where('business_id', $request->business_id))
            ->when($request->search, fn ($q) => $q->where('facility_name', 'like', '%'.$request->search.'%'))
            ->orderBy('facility_name')
            ->paginate(20)
            ->withQueryString();

        $businesses = Business::whereHas('facilities', fn ($q) => $q
            ->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE))
            ->orderBy('business_name')
            ->get();

        return view('superadmin.rica.slaughterhouses.index', compact('slaughterhouses', 'businesses'));
    }

    public function show(Request $request, Facility $facility): View
    {
        abort_unless($facility->facility_type === Facility::TYPE_SLAUGHTERHOUSE, 404);

        $this->applyRicaTenantScope();

        ['dateFrom' => $dateFrom, 'dateTo' => $dateTo] = $this->reportService->resolveDashboardDateRange($request);
        $dashboard = $this->reportService->facilityPeriodDashboard($facility, $dateFrom, $dateTo);
        $stats = $dashboard['stats'];
        $speciesBreakdown = $dashboard['speciesBreakdown'];

        $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');

        $recentExecutions = SlaughterExecution::whereIn('slaughter_plan_id', $planIds)
            ->with([
                'slaughterPlan',
                'executionItems.intakeItem',
                'executionItems.batchItems.postMortemOutcome',
                'batches.postMortemInspection',
                'batches.certificate',
            ])
            ->orderByDesc('slaughter_time')
            ->limit(20)
            ->get();

        $facility->load('business');

        return view('superadmin.rica.slaughterhouses.show', compact(
            'facility', 'stats', 'speciesBreakdown', 'recentExecutions', 'dateFrom', 'dateTo'
        ));
    }

    public function reports(Request $request): View
    {
        $this->applyRicaTenantScope();

        $report = $this->reportService->buildReport($request);

        $businesses = TenantEnvironmentScope::applyToBusinesses(
            Business::whereHas('facilities', fn ($q) => $q->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE))
        )->orderBy('business_name')->get();

        return view('superadmin.rica.reports', [
            'reportRows' => $report['rows'],
            'totals' => $report['totals'],
            'dateFrom' => $report['dateFrom'],
            'dateTo' => $report['dateTo'],
            'dateBasis' => $report['dateBasis'],
            'businesses' => $businesses,
        ]);
    }

    public function monthlyReportsIndex(Request $request): View
    {
        $this->applyRicaTenantScope();

        $view = $request->string('view', 'submitted')->toString();
        if (! in_array($view, ['submitted', 'facilities'], true)) {
            $view = 'submitted';
        }

        $period = $this->monthlyReportService->resolvePeriod($request);
        $allPeriods = $this->resolveMonthlyReportAllPeriodsFilter($request, $view);
        $scopedFacility = $request->filled('facility_id')
            ? TenantEnvironmentScope::applyToFacilities(Facility::query())->find($request->integer('facility_id'))
            : null;

        $businesses = TenantEnvironmentScope::applyToBusinesses(
            Business::whereHas('facilities', fn ($q) => $q->eligibleForRicaMonthlyReport())
        )->orderBy('business_name')->get();

        if ($view === 'submitted' && $allPeriods) {
            $submittedReports = RicaMonthlyInspectionReport::query()
                ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
                ->with(['facility.business', 'facility.districtDivision', 'submittedBy'])
                ->when($request->filled('search'), fn ($q) => $q->whereHas(
                    'facility',
                    fn ($facilityQuery) => $facilityQuery->where('facility_name', 'like', '%'.$request->string('search').'%')
                ))
                ->when($request->filled('business_id'), fn ($q) => $q->whereHas(
                    'facility',
                    fn ($facilityQuery) => $facilityQuery->where('business_id', $request->integer('business_id'))
                ))
                ->when($request->filled('facility_id'), fn ($q) => $q->where('facility_id', $request->integer('facility_id')))
                ->orderByDesc('submitted_at')
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->paginate(50)
                ->withQueryString();

            return view('superadmin.rica.monthly-reports.index', [
                'view' => $view,
                'periodScoped' => false,
                'submittedReports' => $submittedReports,
                'businesses' => $businesses,
                'year' => $period['year'],
                'month' => $period['month'],
                'periodStart' => $period['periodStart'],
                'periodEnd' => $period['periodEnd'],
                'allPeriods' => $allPeriods,
                'scopedFacility' => $scopedFacility,
            ]);
        }

        $facilities = $this->ricaMonthlyReportFacilitiesQuery($request)
            ->paginate(50)
            ->withQueryString();

        $submissionStatuses = RicaMonthlyInspectionReport::query()
            ->where('period_year', $period['year'])
            ->where('period_month', $period['month'])
            ->whereIn('facility_id', $facilities->pluck('id'))
            ->get()
            ->keyBy('facility_id');

        return view('superadmin.rica.monthly-reports.index', [
            'view' => $view,
            'periodScoped' => true,
            'facilities' => $facilities,
            'submissionStatuses' => $submissionStatuses,
            'businesses' => $businesses,
            'year' => $period['year'],
            'month' => $period['month'],
            'periodStart' => $period['periodStart'],
            'periodEnd' => $period['periodEnd'],
            'allPeriods' => $allPeriods,
            'scopedFacility' => $scopedFacility,
        ]);
    }

    private function ricaMonthlyReportFacilitiesQuery(Request $request): Builder
    {
        return TenantEnvironmentScope::applyToFacilities(
            Facility::query()
                ->eligibleForRicaMonthlyReport()
                ->with(['business', 'districtDivision'])
        )
            ->when($request->filled('facility_id'), fn ($q) => $q->where('id', $request->integer('facility_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('facility_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('business_id'), fn ($q) => $q->where('business_id', $request->integer('business_id')))
            ->orderBy('facility_name');
    }

    private function resolveMonthlyReportAllPeriodsFilter(Request $request, string $view): bool
    {
        if ($view !== 'submitted') {
            return false;
        }

        if (! $request->has('apply')) {
            return true;
        }

        $value = $request->input('all_periods');

        if (is_array($value)) {
            return in_array('1', $value, true) || in_array(1, $value, true);
        }

        return $request->boolean('all_periods');
    }

    public function monthlyReportShow(Request $request, Facility $facility): View
    {
        $this->assertRicaMonthlyReportFacility($facility);

        $this->applyRicaTenantScope();

        $period = $this->monthlyReportService->resolvePeriod($request);
        $report = $this->monthlyReportService->build(
            $facility,
            $period['periodStart'],
            $period['periodEnd'],
        );

        $facility->load('business');

        return view('superadmin.rica.monthly-reports.show', [
            'facility' => $facility,
            'report' => $report,
            'year' => $period['year'],
            'month' => $period['month'],
        ]);
    }

    public function monthlyReportPdf(Request $request, Facility $facility): \Symfony\Component\HttpFoundation\Response
    {
        $this->assertRicaMonthlyReportFacility($facility);

        $this->applyRicaTenantScope();

        $period = $this->monthlyReportService->resolvePeriod($request);
        $pdf = $this->monthlyReportPdfService->generate(
            $facility,
            $period['periodStart'],
            $period['periodEnd'],
        );

        return $pdf->download(
            $this->monthlyReportPdfService->downloadFilename($facility, $period['periodStart'])
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $this->applyRicaTenantScope();
        $report = $this->reportService->buildReport($request);
        $rows = $this->reportService->allRowsForExport($request);

        $dateFrom = $report['dateFrom'];
        $dateTo = $report['dateTo'];

        $filename = 'rica-report-'
            .$dateFrom->format('Y-m-d')
            .'-to-'
            .$dateTo->format('Y-m-d')
            .'.csv';

        return response()->streamDownload(function () use ($rows, $report): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Slaughterhouse',
                'Operator',
                'Animals slaughtered',
                'Total meat (kg)',
                'Condemned at PM',
                'Certificates issued',
                'Released, no certificate',
                'Avg cold room days',
                'Temperature violations',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['facility']->facility_name,
                    $row['facility']->business->business_name ?? '—',
                    $row['animals'],
                    number_format((float) $row['total_meat_kg'], 2),
                    $row['condemned'],
                    $row['certificates'],
                    $row['awaiting_certificate'],
                    $row['avg_cold_room_days'] !== null ? number_format((float) $row['avg_cold_room_days'], 1) : '—',
                    $row['temperature_violations'],
                ]);
            }

            $totals = $report['totals'];
            fputcsv($handle, [
                'TOTALS',
                '',
                $totals['animals'],
                number_format((float) $totals['total_meat_kg'], 2),
                $totals['condemned'],
                $totals['certificates'],
                $totals['awaiting_certificate'],
                $totals['avg_cold_room_days'] !== null ? number_format((float) $totals['avg_cold_room_days'], 1) : '—',
                $totals['temperature_violations'],
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
