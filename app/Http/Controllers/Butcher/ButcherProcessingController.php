<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherCutOutputRequest;
use App\Http\Requests\Butcher\StoreButcherCuttingSessionRequest;
use App\Http\Requests\Butcher\StoreButcherCuttingSessionSourceRequest;
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
use Illuminate\Validation\ValidationException;

class ButcherProcessingController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherCuttingService $cutting,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.processing.index', [
            'business' => $business,
            'summary' => $this->cutting->getCuttingSummary($business),
        ]);
    }

    public function typesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $search = trim((string) $request->query('q', ''));
        $meatType = (string) $request->query('meat_type', 'all');
        if ($meatType !== 'all' && ! in_array($meatType, \App\Models\ButcherCutType::MEAT_TYPES, true)) {
            $meatType = 'all';
        }

        $baseQuery = $business->butcherCutTypes();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'active' => (int) (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (int) (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $cutTypesQuery = $business->butcherCutTypes()->orderBy('name');
        if ($search !== '') {
            $cutTypesQuery->where('name', 'like', '%'.$search.'%');
        }
        if ($meatType !== 'all') {
            $cutTypesQuery->where('meat_type', $meatType);
        }

        return view('butcher.processing.types.index', [
            'business' => $business,
            'cutTypes' => $cutTypesQuery->paginate(20)->withQueryString(),
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'meat_type' => $meatType,
            ],
            'meatTypes' => \App\Models\ButcherCutType::MEAT_TYPES,
        ]);
    }

    public function typesCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.processing.types.create', [
            'business' => $business,
            'meatTypes' => \App\Models\ButcherCutType::MEAT_TYPES,
        ]);
    }

    public function typesStore(StoreButcherCutTypeRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $validated = $request->validated();
        $business->butcherCutTypes()->create([
            'name' => $validated['name'],
            'meat_type' => $validated['meat_type'],
            'expected_yield_pct' => $validated['expected_yield_pct'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('butcher.processing.types.index')
            ->with('status', __('Cut type added.'));
    }

    public function sessionsIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        if ($status !== 'all' && ! in_array($status, ButcherCuttingSession::STATUSES, true)) {
            $status = 'all';
        }

        $baseQuery = $business->butcherCuttingSessions();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'open' => (int) (clone $baseQuery)->where('status', ButcherCuttingSession::STATUS_OPEN)->count(),
            'closed' => (int) (clone $baseQuery)->where('status', ButcherCuttingSession::STATUS_CLOSED)->count(),
            'yield_kg' => (float) (clone $baseQuery)->where('status', ButcherCuttingSession::STATUS_CLOSED)->sum('total_cuts_weight_kg'),
        ];

        $sessionsQuery = $business->butcherCuttingSessions()
            ->with(['batch', 'outlet'])
            ->latest('session_date')
            ->latest('id');

        if ($search !== '') {
            $sessionsQuery->where(function ($query) use ($search) {
                $query->where('session_number', 'like', '%'.$search.'%')
                    ->orWhereHas('batch', fn ($q) => $q->where('batch_number', 'like', '%'.$search.'%'));
            });
        }

        if ($status !== 'all') {
            $sessionsQuery->where('status', $status);
        }

        return view('butcher.processing.sessions.index', [
            'business' => $business,
            'sessions' => $sessionsQuery->paginate(20)->withQueryString(),
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => ButcherCuttingSession::STATUSES,
        ]);
    }

    public function sessionsCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $canOverride = (bool) $request->user()?->canButcherPermission(
            \App\Models\BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY,
            $business->id
        );

        $batches = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->where('remaining_weight_kg', '>', 0)
            ->with('outlet')
            ->orderBy('received_at')
            ->get()
            ->filter(function (ButcherInventoryBatch $batch) use ($canOverride) {
                if ($canOverride) {
                    return true;
                }

                return ! $batch->isSafetyBlocked();
            });

        return view('butcher.processing.sessions.create', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'batches' => $batches,
            'canOverride' => $canOverride,
        ]);
    }

    public function sessionsStore(StoreButcherCuttingSessionRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        try {
            $session = $this->cutting->openSession($business, $request->validated(), $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.processing.sessions.show', $session)
            ->with('status', __('Processing session opened. Inventory will be deducted only when you close the session.'));
    }

    public function sessionsShow(Request $request, ButcherCuttingSession $session): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $session->load(['batch', 'outlet', 'cutOutputs.cutType', 'sources.batch']);

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

        $availableBatches = collect();
        if ($session->isOpen()) {
            $availableBatches = $business->butcherInventoryBatches()
                ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
                ->where('remaining_weight_kg', '>', 0)
                ->with('outlet')
                ->orderBy('received_at')
                ->get()
                ->filter(fn (ButcherInventoryBatch $batch) => ! $batch->isExpired());
        }

        return view('butcher.processing.sessions.show', [
            'business' => $business,
            'session' => $session,
            'cutTypes' => $cutTypes,
            'wastage' => $wastage,
            'availableBatches' => $availableBatches,
            'canOverride' => (bool) $request->user()?->canButcherPermission(
                \App\Models\BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY,
                (int) $business->id
            ),
            'needsSafetyOverride' => $session->isOpen() && $session->sources->contains(
                fn ($source) => $source->batch?->isSafetyBlocked()
            ),
        ]);
    }

    public function sourcesStore(StoreButcherCuttingSessionSourceRequest $request, ButcherCuttingSession $session): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $this->cutting->addSource($session, $request->validated());

        return redirect()
            ->route('butcher.processing.sessions.show', $session)
            ->with('status', __('Source batch added. Inventory is still unchanged until close.'));
    }

    public function outputsStore(StoreButcherCutOutputRequest $request, ButcherCuttingSession $session): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        $this->cutting->addCutOutput($session, $request->validated());

        return redirect()
            ->route('butcher.processing.sessions.show', $session)
            ->with('status', __('Cut output recorded.'));
    }

    public function sessionsClose(Request $request, ButcherCuttingSession $session): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $session->business_id === (int) $business->id, 404);

        try {
            $this->cutting->closeSession(
                $session,
                $request->user(),
                $request->input('safety_override_reason')
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('butcher.processing.sessions.show', $session)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.processing.sessions.show', $session)
            ->with('status', __('Session closed. Inventory deducted and wastage calculated.'));
    }

    public function generateLabel(Request $request, ButcherCuttingSession $session, ButcherCutOutput $cutOutput): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
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
