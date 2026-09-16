<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherPurchaseOrderRequest;
use App\Http\Requests\Butcher\UpdateButcherPurchaseOrderStatusRequest;
use App\Models\ButcherPurchaseOrder;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherPurchaseOrderController extends Controller
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
        $status = (string) $request->query('status', 'all');
        $meatType = (string) $request->query('meat_type', 'all');

        if ($status !== 'all' && ! in_array($status, ButcherPurchaseOrder::STATUSES, true)) {
            $status = 'all';
        }
        if ($meatType !== 'all' && ! in_array($meatType, ButcherPurchaseOrder::MEAT_TYPES, true)) {
            $meatType = 'all';
        }

        $baseQuery = $business->butcherPurchaseOrders();
        $openStatuses = [
            ButcherPurchaseOrder::STATUS_DRAFT,
            ButcherPurchaseOrder::STATUS_SENT,
            ButcherPurchaseOrder::STATUS_CONFIRMED,
        ];

        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'open' => (int) (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
            'delivered' => (int) (clone $baseQuery)->where('status', ButcherPurchaseOrder::STATUS_DELIVERED)->count(),
            'requested_kg' => (float) (clone $baseQuery)->sum('requested_weight_kg'),
        ];

        $ordersQuery = $business->butcherPurchaseOrders()
            ->with('supplier')
            ->latest();

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('po_number', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($status !== 'all') {
            $ordersQuery->where('status', $status);
        }

        if ($meatType !== 'all') {
            $ordersQuery->where('meat_type', $meatType);
        }

        $orders = $ordersQuery
            ->paginate(15)
            ->withQueryString();

        return view('butcher.purchase-orders.index', [
            'business' => $business,
            'orders' => $orders,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'meat_type' => $meatType,
            ],
            'statuses' => ButcherPurchaseOrder::STATUSES,
            'meatTypes' => ButcherPurchaseOrder::MEAT_TYPES,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $suppliers = $business->butcherSuppliers()->where('is_active', true)->orderBy('name')->get();
        if ($suppliers->isEmpty()) {
            return redirect()
                ->route('butcher.suppliers.index')
                ->with('status', __('Add at least one active supplier before creating a purchase order.'));
        }

        return view('butcher.purchase-orders.create', [
            'business' => $business,
            'suppliers' => $suppliers,
            'meatTypes' => ButcherPurchaseOrder::MEAT_TYPES,
        ]);
    }

    public function store(StoreButcherPurchaseOrderRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $order = $this->procurement->createPurchaseOrder($business, $request->validated());

        return redirect()
            ->route('butcher.purchase-orders.show', $order)
            ->with('status', __('Purchase order :number created.', ['number' => $order->po_number]));
    }

    public function show(Request $request, ButcherPurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $purchaseOrder->business_id === (int) $business->id, 404);

        $purchaseOrder->load(['supplier', 'deliveries.outlet', 'deliveries.lines']);

        $linkableDeliveries = $business->butcherDeliveries()
            ->whereNull('purchase_order_id')
            ->latest('received_at')
            ->limit(25)
            ->get(['id', 'delivery_number', 'meat_type', 'received_weight_kg', 'received_at']);

        return view('butcher.purchase-orders.show', [
            'business' => $business,
            'order' => $purchaseOrder,
            'statuses' => ButcherPurchaseOrder::STATUSES,
            'linkableDeliveries' => $linkableDeliveries,
        ]);
    }

    public function updateStatus(
        UpdateButcherPurchaseOrderStatusRequest $request,
        ButcherPurchaseOrder $purchaseOrder,
    ): RedirectResponse {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $purchaseOrder->business_id === (int) $business->id, 404);

        $this->procurement->updateOrderStatus($purchaseOrder, (string) $request->validated('status'));

        return redirect()
            ->route('butcher.purchase-orders.show', $purchaseOrder)
            ->with('status', __('Purchase order status updated.'));
    }

    public function linkDelivery(Request $request, ButcherPurchaseOrder $purchaseOrder): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $purchaseOrder->business_id === (int) $business->id, 404);

        $validated = $request->validate([
            'delivery_id' => [
                'required',
                'integer',
                'exists:butcher_deliveries,id',
            ],
        ]);

        $delivery = $business->butcherDeliveries()->whereKey($validated['delivery_id'])->firstOrFail();
        abort_unless($delivery->purchase_order_id === null, 422);

        $delivery->update(['purchase_order_id' => $purchaseOrder->id]);

        $ordered = (float) $purchaseOrder->requested_weight_kg;
        $received = (float) $delivery->received_weight_kg;
        $delta = round($received - $ordered, 3);
        $notes = trim((string) $purchaseOrder->notes);
        if (abs($delta) > 0.001) {
            $note = $delta > 0
                ? __('Over-delivery vs PO: +:kg kg.', ['kg' => number_format($delta, 3)])
                : __('Under-delivery vs PO: :kg kg.', ['kg' => number_format(abs($delta), 3)]);
            $notes = trim($notes === '' ? $note : $notes."\n".$note);
        }

        $purchaseOrder->update([
            'status' => ButcherPurchaseOrder::STATUS_DELIVERED,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        return redirect()
            ->route('butcher.purchase-orders.show', $purchaseOrder)
            ->with('status', __('Delivery linked to this purchase order.'));
    }
}
