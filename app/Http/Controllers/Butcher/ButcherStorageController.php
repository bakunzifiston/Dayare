<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherDisposalLogRequest;
use App\Http\Requests\Butcher\StoreButcherTemperatureLogRequest;
use App\Models\ButcherInventoryBatch;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherStorageController extends Controller
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
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.storage.index', [
            'business' => $business,
            'summary' => $this->storage->getStorageSummary($business),
        ]);
    }

    public function batchesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->storage->checkExpiringBatches($business);

        $batches = $business->butcherInventoryBatches()
            ->with(['outlet', 'delivery.supplier'])
            ->orderBy('received_at')
            ->paginate(20);

        return view('butcher.storage.batches.index', [
            'business' => $business,
            'batches' => $batches,
        ]);
    }

    public function batchesShow(Request $request, ButcherInventoryBatch $batch): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $batch->business_id === (int) $business->id, 404);

        $batch->load(['outlet', 'delivery.supplier', 'disposalLogs.disposedByUser']);

        $temperatureLogs = $business->butcherTemperatureLogs()
            ->with('loggedByUser')
            ->when($batch->storage_location, fn ($q) => $q->where('storage_location', $batch->storage_location))
            ->when($batch->outlet_id, fn ($q) => $q->where('outlet_id', $batch->outlet_id))
            ->latest('logged_at')
            ->limit(20)
            ->get();

        return view('butcher.storage.batches.show', [
            'business' => $business,
            'batch' => $batch,
            'temperatureLogs' => $temperatureLogs,
        ]);
    }

    public function temperaturesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $logs = $business->butcherTemperatureLogs()
            ->with(['outlet', 'loggedByUser'])
            ->latest('logged_at')
            ->paginate(20);

        return view('butcher.storage.temperatures.index', [
            'business' => $business,
            'logs' => $logs,
            'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
            'freshThreshold' => $this->storage->temperatureThreshold($business, 'fresh'),
            'frozenThreshold' => $this->storage->temperatureThreshold($business, 'frozen'),
        ]);
    }

    public function temperaturesStore(StoreButcherTemperatureLogRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $log = $this->storage->logTemperature($business, $request->validated(), $request->user());

        $message = $log->is_breach
            ? __('Temperature logged — breach alert recorded.')
            : __('Temperature logged.');

        return redirect()
            ->route('butcher.storage.temperatures.index')
            ->with('status', $message);
    }

    public function disposalsIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $disposals = $business->butcherDisposalLogs()
            ->with(['batch', 'disposedByUser'])
            ->latest('disposed_at')
            ->paginate(20);

        $activeBatches = $business->butcherInventoryBatches()
            ->whereIn('status', [
                ButcherInventoryBatch::STATUS_IN_STORAGE,
                ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                ButcherInventoryBatch::STATUS_EXPIRED,
            ])
            ->where('remaining_weight_kg', '>', 0)
            ->orderBy('received_at')
            ->get();

        return view('butcher.storage.disposals.index', [
            'business' => $business,
            'disposals' => $disposals,
            'activeBatches' => $activeBatches,
        ]);
    }

    public function disposalsStore(StoreButcherDisposalLogRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $batch = ButcherInventoryBatch::query()
            ->where('business_id', $business->id)
            ->findOrFail((int) $request->validated('batch_id'));

        $this->storage->logDisposal($batch, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.storage.disposals.index')
            ->with('status', __('Disposal recorded and batch weight updated.'));
    }
}
