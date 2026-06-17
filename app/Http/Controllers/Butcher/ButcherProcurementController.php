<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherDeliveryRequest;
use App\Http\Requests\Butcher\StoreButcherPurchaseOrderRequest;
use App\Http\Requests\Butcher\UpdateButcherPurchaseOrderStatusRequest;
use App\Models\ButcherDelivery;
use App\Models\ButcherPurchaseOrder;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherProcurementController extends Controller
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
            return redirect()->route('butcher.onboarding.index');
        }
        $period = (string) $request->query('period', '30d');

        return view('butcher.procurement.index', [
            'business' => $business,
            'summary' => $this->procurement->getProcurementSummary($business, $period),
            'period' => $period,
        ]);
    }

    public function ordersIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $orders = $business->butcherPurchaseOrders()
            ->with('supplier')
            ->latest()
            ->paginate(15);

        return view('butcher.procurement.orders.index', [
            'business' => $business,
            'orders' => $orders,
        ]);
    }

    public function ordersCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $suppliers = $business->butcherSuppliers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('butcher.procurement.orders.create', [
            'business' => $business,
            'suppliers' => $suppliers,
        ]);
    }

    public function ordersStore(StoreButcherPurchaseOrderRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $order = $this->procurement->createPurchaseOrder($business, $request->validated());

        return redirect()
            ->route('butcher.procurement.orders.show', $order)
            ->with('status', __('Purchase order :number created.', ['number' => $order->po_number]));
    }

    public function ordersShow(Request $request, ButcherPurchaseOrder $order): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $order->business_id === (int) $business->id, 404);

        $order->load(['supplier', 'deliveries.outlet', 'deliveries.inventoryBatch', 'deliveries.rejection']);

        return view('butcher.procurement.orders.show', [
            'business' => $business,
            'order' => $order,
        ]);
    }

    public function ordersStatus(UpdateButcherPurchaseOrderStatusRequest $request, ButcherPurchaseOrder $order): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        abort_unless((int) $order->business_id === (int) $business->id, 404);

        $this->procurement->updateOrderStatus($order, (string) $request->validated('status'));

        return redirect()
            ->route('butcher.procurement.orders.show', $order)
            ->with('status', __('Purchase order status updated.'));
    }

    public function deliveriesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $deliveries = $business->butcherDeliveries()
            ->with(['supplier', 'outlet', 'purchaseOrder'])
            ->latest('received_at')
            ->paginate(15);

        return view('butcher.procurement.deliveries.index', [
            'business' => $business,
            'deliveries' => $deliveries,
        ]);
    }

    public function deliveriesCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.procurement.deliveries.create', [
            'business' => $business,
            'suppliers' => $business->butcherSuppliers()->where('is_active', true)->orderBy('name')->get(),
            'outlets' => $business->butcherOutlets()->where('status', 'active')->orderBy('name')->get(),
            'openOrders' => $business->butcherPurchaseOrders()
                ->with('supplier')
                ->whereNotIn('status', [
                    ButcherPurchaseOrder::STATUS_DELIVERED,
                    ButcherPurchaseOrder::STATUS_CANCELLED,
                ])
                ->latest()
                ->get(),
            'selectedOrderId' => $request->query('purchase_order_id'),
        ]);
    }

    public function deliveriesStore(StoreButcherDeliveryRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $delivery = $this->procurement->receiveDelivery($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.procurement.deliveries.show', $delivery)
            ->with('status', __('Delivery :number recorded.', ['number' => $delivery->delivery_number]));
    }

    public function deliveriesShow(Request $request, ButcherDelivery $delivery): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $delivery->business_id === (int) $business->id, 404);

        $delivery->load([
            'supplier',
            'outlet',
            'purchaseOrder',
            'inventoryBatch',
            'rejection',
            'receivedByUser',
        ]);

        return view('butcher.procurement.deliveries.show', [
            'business' => $business,
            'delivery' => $delivery,
        ]);
    }
}
