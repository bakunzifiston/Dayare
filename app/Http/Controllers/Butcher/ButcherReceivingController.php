<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherDeliveryRequest;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherPurchaseOrder;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherReceivingController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherProcurementService $procurement,
        private readonly ButcherOnboardingService $onboarding,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $search = trim((string) $request->query('q', ''));
        $condition = (string) $request->query('condition', 'all');
        $meatType = (string) $request->query('meat_type', 'all');

        if ($condition !== 'all' && ! in_array($condition, ButcherDelivery::CONDITIONS, true)) {
            $condition = 'all';
        }
        if ($meatType !== 'all' && ! in_array($meatType, ButcherDelivery::MEAT_TYPES, true)) {
            $meatType = 'all';
        }

        $baseQuery = $business->butcherDeliveries();
        $acceptedConditions = [
            ButcherDelivery::CONDITION_GOOD,
            ButcherDelivery::CONDITION_FAIR,
        ];

        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'received_kg' => (float) (clone $baseQuery)->whereIn('condition', $acceptedConditions)->sum('received_weight_kg'),
            'total_spend' => (float) (clone $baseQuery)->whereIn('condition', $acceptedConditions)->sum('total_cost'),
            'rejected' => (int) (clone $baseQuery)->where('condition', ButcherDelivery::CONDITION_REJECTED)->count(),
        ];

        $deliveriesQuery = $business->butcherDeliveries()
            ->with(['supplier', 'outlet', 'lines'])
            ->latest('received_at');

        if ($search !== '') {
            $deliveriesQuery->where(function ($query) use ($search) {
                $query->where('delivery_number', 'like', '%'.$search.'%')
                    ->orWhere('certificate_ref', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('outlet', function ($outletQuery) use ($search) {
                        $outletQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($condition !== 'all') {
            $deliveriesQuery->where('condition', $condition);
        }

        if ($meatType !== 'all') {
            $deliveriesQuery->where('meat_type', $meatType);
        }

        $deliveries = $deliveriesQuery
            ->paginate(15)
            ->withQueryString();

        return view('butcher.receiving.index', [
            'business' => $business,
            'deliveries' => $deliveries,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'condition' => $condition,
                'meat_type' => $meatType,
            ],
            'conditions' => ButcherDelivery::CONDITIONS,
            'meatTypes' => ButcherDelivery::MEAT_TYPES,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $suppliers = $business->butcherSuppliers()->where('is_active', true)->orderBy('name')->get();
        $outlets = $business->butcherOutlets()->where('status', 'active')->orderBy('name')->get();

        if ($suppliers->isEmpty()) {
            return redirect()
                ->route('butcher.suppliers.index')
                ->with('status', __('Add at least one active supplier before receiving stock.'));
        }

        if ($outlets->isEmpty()) {
            return redirect()
                ->route('butcher.outlets.index')
                ->with('status', __('Configure an outlet before receiving stock.'));
        }

        $openOrders = $business->butcherPurchaseOrders()
            ->with('supplier')
            ->whereIn('status', [
                ButcherPurchaseOrder::STATUS_DRAFT,
                ButcherPurchaseOrder::STATUS_SENT,
                ButcherPurchaseOrder::STATUS_CONFIRMED,
            ])
            ->latest()
            ->get();

        $selectedPo = null;
        if ($request->filled('purchase_order_id')) {
            $selectedPo = $openOrders->firstWhere('id', (int) $request->integer('purchase_order_id'));
        }

        return view('butcher.receiving.create', [
            'business' => $business,
            'suppliers' => $suppliers,
            'outlets' => $outlets,
            'openOrders' => $openOrders,
            'selectedPo' => $selectedPo,
            'meatTypes' => ButcherDelivery::MEAT_TYPES,
            'outcomes' => ButcherDeliveryLine::OUTCOMES,
            'hygieneBanner' => app(\App\Services\Butcher\ButcherComplianceService::class)
                ->hygieneMissingBanner(
                    $business,
                    (int) ($selectedPo?->outlet_id ?? $outlets->first()?->id)
                ),
        ]);
    }

    public function store(StoreButcherDeliveryRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $delivery = $this->procurement->receiveDelivery($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.receiving.show', $delivery)
            ->with('status', __('Delivery :number recorded.', ['number' => $delivery->delivery_number]));
    }

    public function show(Request $request, ButcherDelivery $delivery): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $delivery->business_id === (int) $business->id, 404);

        $delivery->load([
            'supplier',
            'outlet',
            'purchaseOrder',
            'lines.inventoryBatch',
            'lines.rejection',
            'inventoryBatches',
            'rejections',
            'receivedByUser',
        ]);

        return view('butcher.receiving.show', [
            'business' => $business,
            'delivery' => $delivery,
        ]);
    }
}
