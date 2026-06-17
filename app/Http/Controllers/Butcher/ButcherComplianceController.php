<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherHygieneLogRequest;
use App\Http\Requests\Butcher\StoreButcherSanitationRecordRequest;
use App\Http\Requests\Butcher\StoreButcherStaffHealthRecordRequest;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherOutlet;
use App\Models\Business;
use App\Models\User;
use App\Services\Butcher\ButcherComplianceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ButcherComplianceController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherComplianceService $compliance,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.compliance.index', [
            'business' => $business,
            'alerts' => $this->compliance->getComplianceAlerts($business),
        ]);
    }

    public function hygieneIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $today = now()->toDateString();
        $outlets = $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get();

        $todayLogs = $business->butcherHygieneLogs()
            ->with(['outlet', 'signedByUser'])
            ->whereDate('log_date', $today)
            ->get()
            ->keyBy('outlet_id');

        $logs = $business->butcherHygieneLogs()
            ->with(['outlet', 'signedByUser'])
            ->latest('log_date')
            ->latest('id')
            ->paginate(20);

        return view('butcher.compliance.hygiene.index', [
            'business' => $business,
            'outlets' => $outlets,
            'todayLogs' => $todayLogs,
            'logs' => $logs,
            'checklistKeys' => ButcherHygieneLog::CHECKLIST_LABELS,
            'defaultChecklist' => ButcherHygieneLog::DEFAULT_CHECKLIST,
        ]);
    }

    public function hygieneStore(StoreButcherHygieneLogRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $log = $this->compliance->logHygiene($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.compliance.hygiene.show', $log)
            ->with('status', __('Hygiene log saved.'));
    }

    public function hygieneShow(Request $request, ButcherHygieneLog $hygieneLog): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $hygieneLog->business_id === (int) $business->id, 404);

        $hygieneLog->load(['outlet', 'signedByUser']);

        return view('butcher.compliance.hygiene.show', [
            'business' => $business,
            'log' => $hygieneLog,
            'checklistKeys' => ButcherHygieneLog::CHECKLIST_LABELS,
        ]);
    }

    public function sanitationIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $records = $business->butcherSanitationRecords()
            ->with(['outlet', 'performedByUser'])
            ->latest('performed_at')
            ->paginate(20);

        return view('butcher.compliance.sanitation.index', [
            'business' => $business,
            'records' => $records,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'cleaningTypes' => \App\Models\ButcherSanitationRecord::CLEANING_TYPES,
        ]);
    }

    public function sanitationStore(StoreButcherSanitationRecordRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->compliance->logSanitation($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.compliance.sanitation.index')
            ->with('status', __('Sanitation record logged.'));
    }

    public function healthIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $records = $business->butcherStaffHealthRecords()
            ->with('user')
            ->orderBy('expiry_date')
            ->paginate(20);

        return view('butcher.compliance.health.index', [
            'business' => $business,
            'records' => $records,
            'staffUsers' => $this->businessStaffUsers($business),
            'healthStatuses' => \App\Models\ButcherStaffHealthRecord::HEALTH_STATUSES,
        ]);
    }

    public function healthStore(StoreButcherStaffHealthRecordRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->compliance->upsertStaffHealth($business, $request->validated());

        return redirect()
            ->route('butcher.compliance.health.index')
            ->with('status', __('Staff health record saved.'));
    }

    public function report(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        return view('butcher.compliance.report', [
            'business' => $business,
            'report' => $this->compliance->getAuditReport($business, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function reportExport(Request $request): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        $path = $this->compliance->exportAuditReport($business, $from, $to);

        return Storage::disk('public')->download(
            $path,
            sprintf('compliance-audit-%s-%s.csv', $from->format('Ymd'), $to->format('Ymd'))
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function businessStaffUsers(Business $business): Collection
    {
        $owner = $business->user;
        $members = $business->memberUsers()->get();

        return collect($owner ? [$owner] : [])
            ->merge($members)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
}
