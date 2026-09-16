<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\FulfillButcherOrderRequest;
use App\Http\Requests\Butcher\StoreButcherOrderRequest;
use App\Http\Requests\Butcher\StoreButcherReturnRequest;
use App\Http\Requests\Butcher\StoreButcherSaleRequest;
use App\Http\Requests\Butcher\UpdateButcherOrderStatusRequest;
use App\Models\ButcherCutOutput;
use App\Models\ButcherOrder;
use App\Models\ButcherOutlet;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherOrderFulfillmentService;
use App\Services\Butcher\ButcherReturnService;
use App\Services\Butcher\ButcherSalesService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ButcherSalesController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherSalesService $sales,
        private readonly ButcherCatalogService $catalog,
        private readonly ButcherOrderFulfillmentService $fulfillment,
        private readonly ButcherReturnService $returns,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $date = $request->query('date', now()->toDateString());
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $outletId = $this->requestedOutletId($request, $business);

        if ($status !== 'all' && ! in_array($status, ButcherSale::STATUSES, true)) {
            $status = 'all';
        }

        $salesQuery = $business->butcherSales()
            ->with(['customer', 'outlet', 'soldByUser'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($date, fn ($q) => $q->whereDate('sale_date', $date))
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->latest('id');

        if ($search !== '') {
            $salesQuery->where(function ($query) use ($search) {
                $query->where('sale_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            });
        }

        return view('butcher.sales.index', [
            'business' => $business,
            'sales' => $salesQuery->paginate(20)->withQueryString(),
            'summary' => $this->sales->getDailySalesSummary($business, Carbon::parse($date), $outletId),
            'filters' => [
                'q' => $search,
                'date' => $date,
                'status' => $status,
            ],
            'filterOutletId' => $outletId,
            'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
            'statuses' => ButcherSale::STATUSES,
        ]);
    }

    public function pos(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $outletId = (int) ($request->query('outlet_id') ?: $business->butcherOutlets()->value('id'));
        $customerId = $request->query('customer_id');

        $customer = $customerId
            ? $business->butcherCustomers()->find($customerId)
            : null;

        $products = $business->butcherProducts()
            ->where('is_active', true)
            ->with('cutType')
            ->orderBy('name')
            ->get()
            ->map(function (ButcherProduct $product) use ($outletId, $customer) {
                $stockKg = $product->cut_type_id
                    ? (float) ButcherCutOutput::query()
                        ->where('business_id', $product->business_id)
                        ->where('cut_type_id', $product->cut_type_id)
                        ->where('remaining_weight_kg', '>', 0)
                        ->when($outletId, function ($q) use ($outletId) {
                            $q->whereHas('session', fn ($s) => $s->where('outlet_id', $outletId));
                        })
                        ->sum('remaining_weight_kg')
                    : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'meat_type' => $product->meat_type,
                    'price' => $this->catalog->resolvePrice(
                        $product,
                        $outletId ?: null,
                        $customer?->tier
                    ),
                    'stock_kg' => $stockKg,
                ];
            });

        return view('butcher.sales.pos', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'customers' => $business->butcherCustomers()->orderBy('name')->get(),
            'products' => $products,
            'selectedOutletId' => $outletId,
            'selectedCustomerId' => $customer?->id,
            'paymentMethods' => ButcherSale::PAYMENT_METHODS,
            'canOverrideSafety' => (bool) $request->user()?->canButcherPermission(
                \App\Models\BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY,
                $business->id
            ),
            'hygieneBanner' => app(\App\Services\Butcher\ButcherComplianceService::class)
                ->hygieneMissingBanner($business, $outletId ?: null),
        ]);
    }

    public function store(StoreButcherSaleRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $sale = $this->sales->createSale($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.sales.show', $sale)
            ->with('status', __('Sale completed.'));
    }

    public function show(Request $request, ButcherSale $sale): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $sale->load([
            'items.product',
            'items.cutOutput.session.batch',
            'items.cutOutput.session.sources.batch',
            'items.returns',
            'customer',
            'outlet',
            'soldByUser',
            'payments',
            'order',
        ]);

        return view('butcher.sales.show', [
            'business' => $business,
            'sale' => $sale,
        ]);
    }

    public function cancel(Request $request, ButcherSale $sale): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $this->sales->cancelSale($sale, $request->user());

        return redirect()
            ->route('butcher.sales.show', $sale)
            ->with('status', __('Sale cancelled and stock restored.'));
    }

    public function storeReturn(StoreButcherReturnRequest $request, ButcherSale $sale): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $sale->business_id === (int) $business->id, 404);

        try {
            $this->returns->processReturn($sale, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.sales.show', $sale)
            ->with('status', __('Return processed and stock restored.'));
    }

    public function downloadReceipt(Request $request, ButcherSale $sale): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $path = $sale->receipt_path ?: $this->sales->generateReceipt($sale);

        return Storage::disk('public')->download($path, $sale->sale_number.'-receipt.pdf');
    }

    public function downloadInvoice(Request $request, ButcherSale $sale): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $path = $sale->invoice_path ?: $this->sales->generateInvoice($sale);

        return Storage::disk('public')->download($path, $sale->sale_number.'-invoice.pdf');
    }

    public function ordersIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $orderStatuses = [
            ButcherOrder::STATUS_PENDING,
            ButcherOrder::STATUS_CONFIRMED,
            ButcherOrder::STATUS_READY,
            ButcherOrder::STATUS_CANCELLED,
        ];
        if ($status !== 'all' && ! in_array($status, $orderStatuses, true)) {
            $status = 'all';
        }

        $baseQuery = $business->butcherOrders();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'pending' => (int) (clone $baseQuery)->where('status', ButcherOrder::STATUS_PENDING)->count(),
            'ready' => (int) (clone $baseQuery)->where('status', ButcherOrder::STATUS_READY)->count(),
            'confirmed' => (int) (clone $baseQuery)->where('status', ButcherOrder::STATUS_CONFIRMED)->count(),
        ];

        $ordersQuery = $business->butcherOrders()
            ->with(['customer', 'items.product', 'sale', 'outlet'])
            ->latest('order_date')
            ->latest('id');

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($status !== 'all') {
            $ordersQuery->where('status', $status);
        }

        return view('butcher.sales.orders.index', [
            'business' => $business,
            'orders' => $ordersQuery->paginate(20)->withQueryString(),
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => $orderStatuses,
        ]);
    }

    public function ordersCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $customers = $business->butcherCustomers()->orderBy('name')->get();
        if ($customers->isEmpty()) {
            return redirect()
                ->route('butcher.customers.create')
                ->with('status', __('Add a customer before creating an order.'));
        }

        return view('butcher.sales.orders.create', [
            'business' => $business,
            'customers' => $customers,
            'products' => $business->butcherProducts()->where('is_active', true)->orderBy('name')->get(),
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function ordersStore(StoreButcherOrderRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $order = $this->sales->createOrder($business, $request->validated());

        return redirect()
            ->route('butcher.sales.orders.show', $order)
            ->with('status', __('Order created.'));
    }

    public function ordersShow(Request $request, ButcherOrder $order): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $order->business_id === (int) $business->id, 404);

        $order->load(['customer', 'items.product.cutType', 'sale.items', 'outlet']);

        $stockPreview = [];
        if ($order->isFulfillable()) {
            foreach ($order->items as $item) {
                $cutTypeId = $item->product?->cut_type_id;
                $available = $cutTypeId
                    ? (float) ButcherCutOutput::query()
                        ->where('business_id', $business->id)
                        ->where('cut_type_id', $cutTypeId)
                        ->where('remaining_weight_kg', '>', 0)
                        ->sum('remaining_weight_kg')
                    : 0;
                $stockPreview[] = [
                    'product' => $item->product?->name,
                    'ordered_kg' => (float) $item->quantity_kg,
                    'available_kg' => $available,
                ];
            }
        }

        return view('butcher.sales.orders.show', [
            'business' => $business,
            'order' => $order,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'paymentMethods' => ButcherSale::PAYMENT_METHODS,
            'stockPreview' => $stockPreview,
            'statuses' => $this->sales->nextOrderStatuses($order),
            'canOverrideSafety' => (bool) $request->user()?->canButcherPermission(
                \App\Models\BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY,
                (int) $business->id
            ),
        ]);
    }

    public function ordersStatus(UpdateButcherOrderStatusRequest $request, ButcherOrder $order): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $order->business_id === (int) $business->id, 404);

        try {
            $this->sales->updateOrderStatus($order, $request->validated('status'));
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.sales.orders.show', $order)
            ->with('status', __('Order status updated.'));
    }

    public function ordersFulfill(FulfillButcherOrderRequest $request, ButcherOrder $order): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $order->business_id === (int) $business->id, 404);

        try {
            $sale = $this->fulfillment->fulfill($order, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.sales.show', $sale)
            ->with('status', __('Order fulfilled and sale created.'));
    }
}
