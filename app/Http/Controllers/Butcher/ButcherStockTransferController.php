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

        $search = trim((string) $request->query('q', ''));

        $baseQuery = $business->butcherStockTransfers();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'quantity_kg' => (float) (clone $baseQuery)->sum('quantity_kg'),
            'today' => (int) (clone $baseQuery)->whereDate('transferred_at', now()->toDateString())->count(),
            'outlets' => (int) $business->butcherOutlets()->where('status', 'active')->count(),
        ];

        $transfersQuery = $business->butcherStockTransfers()
            ->with(['fromOutlet', 'toOutlet', 'batch', 'destinationBatch', 'transferredByUser'])
            ->latest('transferred_at');

        if ($search !== '') {
            $transfersQuery->where(function ($query) use ($search) {
                $query->where('notes', 'like', '%'.$search.'%')
                    ->orWhereHas('fromOutlet', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('toOutlet', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('batch', fn ($q) => $q->where('batch_number', 'like', '%'.$search.'%'))
                    ->orWhereHas('destinationBatch', fn ($q) => $q->where('batch_number', 'like', '%'.$search.'%'));
            });
        }

        $transfers = $transfersQuery
            ->paginate(20)
            ->withQueryString();

        return view('butcher.transfers.index', [
            'business' => $business,
            'transfers' => $transfers,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
            ],
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
