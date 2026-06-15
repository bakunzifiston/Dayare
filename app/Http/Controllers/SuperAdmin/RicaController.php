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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RicaController extends Controller
{
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

    public function hub(): View
    {
        $facilityIds = Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)->pluck('id');
        $planIds = $this->planIdsForFacilities($facilityIds);

        $monthExecutionFilter = fn ($q) => $q->whereIn('slaughter_plan_id', $planIds)
            ->whereMonth('slaughter_time', now()->month)
            ->whereYear('slaughter_time', now()->year);

        $hubStats = [
            'total_slaughterhouses' => $facilityIds->count(),
            'total_operators' => Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
                ->distinct()
                ->count('business_id'),
            'animals_slaughtered_month' => SlaughterExecutionItem::whereHas('execution', $monthExecutionFilter)->count(),
            'meat_kg_month' => (float) SlaughterExecutionItem::whereHas('execution', $monthExecutionFilter)->sum('meat_quantity_kg'),
            'condemned_month' => PostMortemInspectionItem::whereHas(
                'inspection.batch.slaughterExecution.slaughterPlan',
                fn ($q) => $q->whereIn('facility_id', $facilityIds)
            )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'certificates_month' => Certificate::whereHas(
                'batch.slaughterExecution.slaughterPlan',
                fn ($q) => $q->whereIn('facility_id', $facilityIds)
            )->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count(),
        ];

        $recentExecutions = SlaughterExecution::whereIn('slaughter_plan_id', $planIds)
            ->with(['slaughterPlan.facility.business'])
            ->orderByDesc('slaughter_time')
            ->limit(10)
            ->get();

        $slaughterhouses = $this->slaughterhouseQuery()
            ->withCount('slaughterPlans')
            ->orderBy('facility_name')
            ->limit(6)
            ->get();

        return view('superadmin.rica.hub', compact('hubStats', 'recentExecutions', 'slaughterhouses'));
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
                'inspection.batch.slaughterExecution.slaughterPlan',
                fn ($q) => $q->where('facility_id', $facility->id)
            )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'certificates' => Certificate::whereHas(
                'batch.slaughterExecution.slaughterPlan',
                fn ($q) => $q->where('facility_id', $facility->id)
            )->whereBetween('issued_at', [$dateFrom, $dateTo])
                ->count(),
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
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfMonth();

        $businesses = Business::whereHas('facilities', fn ($q) => $q
            ->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE))
            ->orderBy('business_name')
            ->get();

        $reportRows = Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
            ->with('business')
            ->when($request->business_id, fn ($q) => $q->where('business_id', $request->business_id))
            ->orderBy('facility_name')
            ->get()
            ->map(function (Facility $facility) use ($dateFrom, $dateTo) {
                $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');
                $execBase = SlaughterExecutionItem::whereHas('execution', fn ($q) => $q
                    ->whereIn('slaughter_plan_id', $planIds)
                    ->whereBetween('slaughter_time', [$dateFrom, $dateTo]));

                return [
                    'facility' => $facility,
                    'animals' => (clone $execBase)->count(),
                    'total_meat_kg' => (float) (clone $execBase)->sum('meat_quantity_kg'),
                    'condemned' => PostMortemInspectionItem::whereHas(
                        'inspection.batch.slaughterExecution.slaughterPlan',
                        fn ($q) => $q->where('facility_id', $facility->id)
                    )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->count(),
                    'certificates' => Certificate::whereHas(
                        'batch.slaughterExecution.slaughterPlan',
                        fn ($q) => $q->where('facility_id', $facility->id)
                    )->whereBetween('issued_at', [$dateFrom, $dateTo])
                        ->count(),
                ];
            });

        return view('superadmin.rica.reports', compact('reportRows', 'businesses', 'dateFrom', 'dateTo'));
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfMonth();

        $filename = 'rica-report-'
            .$dateFrom->format('Y-m-d')
            .'-to-'
            .$dateTo->format('Y-m-d')
            .'.csv';

        return response()->streamDownload(function () use ($dateFrom, $dateTo, $request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Slaughterhouse',
                'Operator',
                'Animals slaughtered',
                'Total meat (kg)',
                'Condemned at PM',
                'Certificates issued',
            ]);

            Facility::where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
                ->with('business')
                ->when($request->business_id, fn ($q) => $q->where('business_id', $request->business_id))
                ->orderBy('facility_name')
                ->chunk(50, function ($facilities) use ($handle, $dateFrom, $dateTo): void {
                    foreach ($facilities as $facility) {
                        $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');
                        $execBase = SlaughterExecutionItem::whereHas('execution', fn ($q) => $q
                            ->whereIn('slaughter_plan_id', $planIds)
                            ->whereBetween('slaughter_time', [$dateFrom, $dateTo]));

                        fputcsv($handle, [
                            $facility->facility_name,
                            $facility->business->business_name ?? '—',
                            (clone $execBase)->count(),
                            number_format((float) (clone $execBase)->sum('meat_quantity_kg'), 2),
                            PostMortemInspectionItem::whereHas(
                                'inspection.batch.slaughterExecution.slaughterPlan',
                                fn ($q) => $q->where('facility_id', $facility->id)
                            )->where('outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED)
                                ->whereBetween('created_at', [$dateFrom, $dateTo])
                                ->count(),
                            Certificate::whereHas(
                                'batch.slaughterExecution.slaughterPlan',
                                fn ($q) => $q->where('facility_id', $facility->id)
                            )->whereBetween('issued_at', [$dateFrom, $dateTo])
                                ->count(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
