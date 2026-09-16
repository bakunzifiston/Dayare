<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherDisposalLogRequest;
use App\Http\Requests\Butcher\StoreButcherInventoryAdjustmentRequest;
use App\Models\ButcherInventoryBatch;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherWasteController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherStorageService $storage,
        private readonly ButcherOnboardingService $onboarding,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'all');
        if (! in_array($type, ['all', 'waste', 'adjustment'], true)) {
            $type = 'all';
        }

        $wasteBase = $business->butcherDisposalLogs();
        $adjustBase = $business->butcherInventoryAdjustments();

        $kpis = [
            'waste_kg' => (float) (clone $wasteBase)->sum('weight_disposed_kg'),
            'waste_events' => (int) (clone $wasteBase)->count(),
            'adjustment_kg' => (float) (clone $adjustBase)->sum('weight_change_kg'),
            'adjustment_events' => (int) (clone $adjustBase)->count(),
        ];

        $wasteQuery = $business->butcherDisposalLogs()
            ->with(['batch', 'disposedByUser'])
            ->latest('disposed_at');

        $adjustQuery = $business->butcherInventoryAdjustments()
            ->with(['batch', 'adjustedByUser'])
            ->latest('adjusted_at');

        if ($search !== '') {
            $wasteQuery->where(function ($query) use ($search) {
                $query->where('reason', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhereHas('batch', fn ($batchQuery) => $batchQuery->where('batch_number', 'like', '%'.$search.'%'));
            });
            $adjustQuery->where(function ($query) use ($search) {
                $query->where('reason', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhereHas('batch', fn ($batchQuery) => $batchQuery->where('batch_number', 'like', '%'.$search.'%'));
            });
        }

        return view('butcher.waste.index', [
            'business' => $business,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'type' => $type,
            ],
            'waste' => $wasteQuery->paginate(15, ['*'], 'waste_page')->withQueryString(),
            'adjustments' => $adjustQuery->paginate(15, ['*'], 'adjust_page')->withQueryString(),
        ]);
    }

    public function createWaste(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $batches = $this->activeBatches($business->id)
            ->filter(fn (ButcherInventoryBatch $batch) => (float) $batch->remaining_weight_kg > 0)
            ->values();

        if ($batches->isEmpty()) {
            return redirect()
                ->route('butcher.waste.index')
                ->with('status', __('No active batches available for waste logging.'));
        }

        return view('butcher.waste.create', [
            'business' => $business,
            'batches' => $batches,
            'mode' => 'waste',
        ]);
    }

    public function createAdjustment(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $batches = $this->activeBatches($business->id);
        if ($batches->isEmpty()) {
            return redirect()
                ->route('butcher.waste.index')
                ->with('status', __('No batches available for adjustment.'));
        }

        return view('butcher.waste.create', [
            'business' => $business,
            'batches' => $batches,
            'mode' => 'adjustment',
        ]);
    }

    public function storeWaste(StoreButcherDisposalLogRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $batch = ButcherInventoryBatch::query()
            ->where('business_id', $business->id)
            ->findOrFail((int) $request->validated('batch_id'));

        $this->storage->logDisposal($batch, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.waste.index')
            ->with('status', __('Waste disposal recorded.'));
    }

    public function storeAdjustment(StoreButcherInventoryAdjustmentRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $batch = ButcherInventoryBatch::query()
            ->where('business_id', $business->id)
            ->findOrFail((int) $request->validated('batch_id'));

        $this->storage->logAdjustment($batch, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.waste.index')
            ->with('status', __('Inventory adjustment recorded.'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, ButcherInventoryBatch>
     */
    private function activeBatches(int $businessId)
    {
        return ButcherInventoryBatch::query()
            ->where('business_id', $businessId)
            ->whereIn('status', [
                ButcherInventoryBatch::STATUS_IN_STORAGE,
                ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                ButcherInventoryBatch::STATUS_EXPIRED,
                ButcherInventoryBatch::STATUS_FULLY_USED,
            ])
            ->orderBy('received_at')
            ->get();
    }
}
