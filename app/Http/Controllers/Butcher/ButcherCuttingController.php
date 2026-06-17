<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherCutOutputRequest;
use App\Http\Requests\Butcher\StoreButcherCuttingSessionRequest;
use App\Http\Requests\Butcher\StoreButcherCutTypeRequest;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Services\Butcher\ButcherCuttingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ButcherCuttingController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherCuttingService $cutting,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.cutting.index', [
            'business' => $business,
            'summary' => $this->cutting->getCuttingSummary($business),
        ]);
    }

    public function typesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $cutTypes = $business->butcherCutTypes()
            ->orderBy('name')
            ->paginate(20);

        return view('butcher.cutting.types.index', [
            'business' => $business,
            'cutTypes' => $cutTypes,
            'meatTypes' => \App\Models\ButcherCutType::MEAT_TYPES,
        ]);
    }

    public function typesStore(StoreButcherCutTypeRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $validated = $request->validated();
        $business->butcherCutTypes()->create([
            'name' => $validated['name'],
            'meat_type' => $validated['meat_type'],
            'expected_yield_pct' => $validated['expected_yield_pct'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('butcher.cutting.types.index')
            ->with('status', __('Cut type added.'));
    }

    public function sessionsIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $sessions = $business->butcherCuttingSessions()
            ->with(['batch', 'outlet'])
            ->latest('session_date')
            ->latest('id')
            ->paginate(20);

        return view('butcher.cutting.sessions.index', [
            'business' => $business,
            'sessions' => $sessions,
        ]);
    }

    public function sessionsCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $batches = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->where('remaining_weight_kg', '>', 0)
            ->with('outlet')
            ->orderBy('received_at')
            ->get()
            ->filter(fn (ButcherInventoryBatch $batch) => ! $batch->isExpired());

        return view('butcher.cutting.sessions.create', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'batches' => $batches,
        ]);
    }

    public function sessionsStore(StoreButcherCuttingSessionRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $session = $this->cutting->openSession($business, $request->validated());

        return redirect()
            ->route('butcher.cutting.sessions.show', $session)
            ->with('status', __('Cutting session opened. Record cut outputs below.'));
    }

    public function sessionsShow(Request $request, ButcherCuttingSession $session): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $session->load(['batch', 'outlet', 'cutOutputs.cutType']);

        $cutTypes = $business->butcherCutTypes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $wastage = $session->isOpen()
            ? $this->cutting->calculateWastage($session)
            : [
                'wastage_kg' => (float) $session->wastage_kg,
                'wastage_pct' => (float) $session->wastage_pct,
                'total_cuts_weight_kg' => (float) $session->total_cuts_weight_kg,
                'source_weight_kg' => (float) $session->source_weight_kg,
            ];

        return view('butcher.cutting.sessions.show', [
            'business' => $business,
            'session' => $session,
            'cutTypes' => $cutTypes,
            'wastage' => $wastage,
        ]);
    }

    public function outputsStore(StoreButcherCutOutputRequest $request, ButcherCuttingSession $session): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $this->cutting->addCutOutput($session, $request->validated());

        return redirect()
            ->route('butcher.cutting.sessions.show', $session)
            ->with('status', __('Cut output recorded.'));
    }

    public function sessionsClose(Request $request, ButcherCuttingSession $session): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $this->cutting->closeSession($session);

        return redirect()
            ->route('butcher.cutting.sessions.show', $session)
            ->with('status', __('Session closed. Wastage calculated.'));
    }

    public function generateLabel(Request $request, ButcherCuttingSession $session, ButcherCutOutput $cutOutput): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);
        abort_unless((int) $cutOutput->session_id === (int) $session->id, 404);
        abort_unless((int) $cutOutput->business_id === (int) $business->id, 404);

        $path = $this->cutting->generateLabel($cutOutput);

        return Storage::disk('public')->download(
            $path,
            sprintf('label-%s.pdf', $cutOutput->cutType?->name ?? $cutOutput->id)
        );
    }
}
