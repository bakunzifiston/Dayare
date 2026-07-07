<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Http\Requests\StoreMonthlyInspectionReportRequest;
use App\Models\Business;
use App\Models\Facility;
use App\Models\RicaMonthlyInspectionReport;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportPdfService;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportService;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportSubmissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MonthlyInspectionReportController extends Controller
{
    use ScopesProcessorData;

    public function __construct(
        private readonly RicaMonthlyInspectionReportService $monthlyReportService,
        private readonly RicaMonthlyInspectionReportPdfService $monthlyReportPdfService,
        private readonly RicaMonthlyInspectionReportSubmissionService $monthlyReportSubmissionService,
    ) {}

    public function index(Request $request): View
    {
        $view = $request->string('view', 'submitted')->toString();
        if (! in_array($view, ['submitted', 'facilities'], true)) {
            $view = 'submitted';
        }

        $period = $this->monthlyReportService->resolvePeriod($request);
        $accessibleFacilityIds = $this->accessibleFacilityIds($request);
        $allPeriods = $this->resolveAllPeriodsFilter($request, $view);

        $businesses = Business::query()
            ->whereIn('id', $request->user()->accessibleBusinessIds())
            ->whereHas('facilities', fn ($q) => $q->eligibleForRicaMonthlyReport())
            ->orderBy('business_name')
            ->get();

        if ($view === 'submitted' && $allPeriods) {
            $submittedReports = RicaMonthlyInspectionReport::query()
                ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
                ->whereIn('facility_id', $accessibleFacilityIds)
                ->with(['facility.business', 'facility.districtDivision', 'submittedBy'])
                ->when($request->filled('search'), fn ($q) => $q->whereHas(
                    'facility',
                    fn ($facilityQuery) => $facilityQuery->where('facility_name', 'like', '%'.$request->string('search').'%')
                ))
                ->when($request->filled('business_id'), fn ($q) => $q->whereHas(
                    'facility',
                    fn ($facilityQuery) => $facilityQuery->where('business_id', $request->integer('business_id'))
                ))
                ->orderByDesc('submitted_at')
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->paginate(50)
                ->withQueryString();

            return view('monthly-inspection-reports.index', [
                'view' => $view,
                'periodScoped' => false,
                'submittedReports' => $submittedReports,
                'businesses' => $businesses,
                'year' => $period['year'],
                'month' => $period['month'],
                'periodStart' => $period['periodStart'],
                'periodEnd' => $period['periodEnd'],
                'allPeriods' => $allPeriods,
            ]);
        }

        $facilities = $this->monthlyReportFacilitiesQuery($request, $accessibleFacilityIds)
            ->paginate(50)
            ->withQueryString();

        $submissionStatuses = RicaMonthlyInspectionReport::query()
            ->where('period_year', $period['year'])
            ->where('period_month', $period['month'])
            ->whereIn('facility_id', $facilities->pluck('id'))
            ->get()
            ->keyBy('facility_id');

        return view('monthly-inspection-reports.index', [
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
        ]);
    }

    private function monthlyReportFacilitiesQuery(Request $request, $accessibleFacilityIds): Builder
    {
        return Facility::query()
            ->eligibleForRicaMonthlyReport()
            ->whereIn('id', $accessibleFacilityIds)
            ->with(['business', 'districtDivision'])
            ->when($request->filled('search'), fn ($q) => $q->where('facility_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('business_id'), fn ($q) => $q->where('business_id', $request->integer('business_id')))
            ->orderBy('facility_name');
    }

    public function show(Request $request, Facility $facility): View
    {
        $this->assertAccessibleMonthlyReportFacility($request, $facility);

        $period = $this->monthlyReportService->resolvePeriod($request);
        $report = $this->monthlyReportService->build(
            $facility,
            $period['periodStart'],
            $period['periodEnd'],
        );

        $facility->load('business');

        return view('monthly-inspection-reports.show', [
            'facility' => $facility,
            'report' => $report,
            'year' => $period['year'],
            'month' => $period['month'],
        ]);
    }

    public function closure(
        StoreMonthlyInspectionReportRequest $request,
        Facility $facility,
    ): RedirectResponse {
        $this->assertAccessibleMonthlyReportFacility($request, $facility);

        $period = $this->monthlyReportService->resolvePeriod($request);
        $payload = $request->validated();

        if ($request->boolean('submit_to_rica')) {
            $this->monthlyReportSubmissionService->submit(
                $facility,
                $period['periodStart'],
                $payload,
                $request->user(),
            );

            return redirect()
                ->route('monthly-inspection-reports.show', [
                    'facility' => $facility,
                    'month' => $period['periodStart']->format('Y-m'),
                ])
                ->with('status', __('Report submitted to RICA.'));
        }

        $this->monthlyReportSubmissionService->saveDraft(
            $facility,
            $period['periodStart'],
            $payload,
        );

        return redirect()
            ->route('monthly-inspection-reports.show', [
                'facility' => $facility,
                'month' => $period['periodStart']->format('Y-m'),
            ])
            ->with('status', __('Report draft saved.'));
    }

    public function pdf(Request $request, Facility $facility): Response
    {
        $this->assertAccessibleMonthlyReportFacility($request, $facility);

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

    private function assertAccessibleMonthlyReportFacility(Request $request, Facility $facility): void
    {
        abort_unless(
            $this->accessibleFacilityIds($request)->contains($facility->id),
            404,
        );

        abort_unless(
            $facility->facility_type === Facility::TYPE_SLAUGHTERHOUSE
            || $facility->slaughterPlans()->exists(),
            404,
        );
    }

    private function resolveAllPeriodsFilter(Request $request, string $view): bool
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
}
