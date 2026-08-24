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

        return view('butcher.waste.index', [
            'business' => $business,
            'summary' => $this->storage->getWasteSummary($business, '30d'),
            'activeBatches' => $this->activeBatches($business->id),
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
