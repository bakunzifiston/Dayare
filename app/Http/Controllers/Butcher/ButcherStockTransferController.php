<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherStockTransferRequest;
use App\Models\ButcherInventoryBatch;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherStockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherStockTransferController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherStockTransferService $transfers,
        private readonly ButcherOnboardingService $onboarding,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $transfers = $business->butcherStockTransfers()
            ->with(['fromOutlet', 'toOutlet', 'batch', 'destinationBatch', 'transferredByUser'])
            ->latest('transferred_at')
            ->paginate(20);

        return view('butcher.transfers.index', [
            'business' => $business,
            'transfers' => $transfers,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $outlets = $business->butcherOutlets()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        if ($outlets->count() < 2) {
            return redirect()
                ->route('butcher.outlets.index')
                ->with('status', __('Add at least two active outlets before transferring stock.'));
        }

        $batches = $business->butcherInventoryBatches()
            ->with('outlet')
            ->whereIn('status', [
                ButcherInventoryBatch::STATUS_IN_STORAGE,
                ButcherInventoryBatch::STATUS_PARTIALLY_USED,
            ])
            ->where('remaining_weight_kg', '>', 0)
            ->orderBy('received_at')
            ->get();

        return view('butcher.transfers.create', [
            'business' => $business,
            'outlets' => $outlets,
            'batches' => $batches,
        ]);
    }

    public function store(StoreButcherStockTransferRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $transfer = $this->transfers->transfer($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.transfers.index')
            ->with('status', __('Transferred :kg kg from :from to :to.', [
                'kg' => number_format((float) $transfer->quantity_kg, 3),
                'from' => $transfer->fromOutlet?->name,
                'to' => $transfer->toOutlet?->name,
            ]));
    }
}
