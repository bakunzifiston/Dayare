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
use App\Services\SuperAdmin\RicaReportService;
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
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
    ) {}

    /**
     * All slaughterhouses across all businesses — no tenant scoping.
     */
    private function slaughterhouseQuery(): Builder
    {
        return Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
            ->with('business');
    }

    /**
     * Get all slaughter plan IDs for a given set of facility IDs.
     */
    private function planIdsForFacilities(Collection $facilityIds): Collection
    {
        return SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id');
    }

    public function hub(Request $request): View
    {
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));

        $filters = $this->slaughterDashboard->resolveHubFilters($request);

        $facilityIds = TenantEnvironmentScope::applyToFacilities(
            Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
        )->pluck('id');
        $planIds = $this->planIdsForFacilities($facilityIds);

        $hubStats = $this->hubStats($filters, $facilityIds, $planIds);
        $speciesSlaughtered = $this->slaughterDashboard->speciesSlaughteredCounts($filters);
        $facilitySlaughterRows = $this->slaughterDashboard->facilitySlaughterRows($filters, Facility::TYPE_SLAUGHTERHOUSE);
        $chartSpecs = $this->slaughterDashboard->workspaceChartSpecs($filters, $speciesSlaughtered);

        return view('superadmin.rica.hub', compact(
            'hubStats',
            'speciesSlaughtered',
            'facilitySlaughterRows',
            'filters',
            'chartSpecs',
        ))->with('tenantEnvironmentFilter', TenantEnvironmentScope::current());
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

        $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $execItemsBase = SlaughterExecutionItem::whereHas('execution', fn ($q) => $q
            ->whereIn('slaughter_plan_id', $planIds)
            ->whereBetween('slaughter_time', [$dateFrom, $dateTo]));

        $stats = [
            'animals_slaughtered' => (clone $execItemsBase)->count(),
            'total_meat_kg' => (float) (clone $execItemsBase)->sum('meat_quantity_kg'),
            'condemned' => PostMortemInspectionItem::whereHas(
                'inspection.batch.slaughterExecution',
                fn ($q) => $q->whereIn('slaughter_plan_id', $planIds)
                    ->whereBetween('slaughter_time', [$dateFrom, $dateTo])
            )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                ->count(),
            'certificates' => Certificate::whereHas(
                'batch.slaughterExecution',
                fn ($q) => $q->whereIn('slaughter_plan_id', $planIds)
                    ->whereBetween('slaughter_time', [$dateFrom, $dateTo])
            )->count(),
        ];

        $speciesBreakdown = SlaughterExecutionItem::whereHas('execution', fn ($q) => $q
            ->whereIn('slaughter_plan_id', $planIds)
            ->whereBetween('slaughter_time', [$dateFrom, $dateTo]))
            ->join('animal_intake_items', 'slaughter_execution_items.animal_intake_item_id', '=', 'animal_intake_items.id')
            ->selectRaw('animal_intake_items.species, COUNT(*) as count, SUM(slaughter_execution_items.meat_quantity_kg) as total_kg')
            ->groupBy('animal_intake_items.species')
            ->orderByDesc('count')
            ->get();

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
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));

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
            'tenantEnvironmentFilter' => TenantEnvironmentScope::current(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));
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
