<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherCustomerRequest;
use App\Http\Requests\Butcher\StoreButcherOrderRequest;
use App\Http\Requests\Butcher\StoreButcherSaleRequest;
use App\Http\Requests\Butcher\UpdateButcherOrderStatusRequest;
use App\Models\ButcherCutOutput;
use App\Models\ButcherOrder;
use App\Models\ButcherOutlet;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherSalesService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ButcherSalesController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherSalesService $sales,
        private readonly ButcherCatalogService $catalog,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $date = $request->query('date', now()->toDateString());
        $status = $request->query('status');

        $sales = $business->butcherSales()
            ->with(['customer', 'outlet', 'soldByUser'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($date, fn ($q) => $q->whereDate('sale_date', $date))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('butcher.sales.index', [
            'business' => $business,
            'sales' => $sales,
            'summary' => $this->sales->getDailySalesSummary($business, Carbon::parse($date)),
            'filterDate' => $date,
            'filterStatus' => $status,
        ]);
    }

    public function pos(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
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
        ]);
    }

    public function store(StoreButcherSaleRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
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
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $sale->load(['items.product', 'items.cutOutput', 'customer', 'outlet', 'soldByUser', 'payments']);

        return view('butcher.sales.show', [
            'business' => $business,
            'sale' => $sale,
        ]);
    }

    public function cancel(Request $request, ButcherSale $sale): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $this->sales->cancelSale($sale);

        return redirect()
            ->route('butcher.sales.show', $sale)
            ->with('status', __('Sale cancelled and stock restored.'));
    }

    public function downloadReceipt(Request $request, ButcherSale $sale): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $path = $sale->receipt_path ?: $this->sales->generateReceipt($sale);

        return Storage::disk('public')->download($path, $sale->sale_number.'-receipt.pdf');
    }

    public function downloadInvoice(Request $request, ButcherSale $sale): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $path = $sale->invoice_path ?: $this->sales->generateInvoice($sale);

        return Storage::disk('public')->download($path, $sale->sale_number.'-invoice.pdf');
    }

    public function customersIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $customers = $business->butcherCustomers()
            ->orderBy('name')
            ->paginate(20);

        return view('butcher.sales.customers.index', [
            'business' => $business,
            'customers' => $customers,
            'tiers' => \App\Models\ButcherCustomer::TIERS,
        ]);
    }

    public function customersStore(StoreButcherCustomerRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->sales->createCustomer($business, $request->validated());

        return redirect()
            ->route('butcher.sales.customers.index')
            ->with('status', __('Customer added.'));
    }

    public function ordersIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $orders = $business->butcherOrders()
            ->with(['customer', 'items.product'])
            ->latest('order_date')
            ->latest('id')
            ->paginate(20);

        return view('butcher.sales.orders.index', [
            'business' => $business,
            'orders' => $orders,
            'customers' => $business->butcherCustomers()->orderBy('name')->get(),
            'products' => $business->butcherProducts()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => ButcherOrder::STATUSES,
        ]);
    }

    public function ordersStore(StoreButcherOrderRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->sales->createOrder($business, $request->validated());

        return redirect()
            ->route('butcher.sales.orders.index')
            ->with('status', __('Order created.'));
    }

    public function ordersStatus(UpdateButcherOrderStatusRequest $request, ButcherOrder $order): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $order->business_id === (int) $business->id, 404);

        $this->sales->updateOrderStatus($order, $request->validated('status'));

        return redirect()
            ->route('butcher.sales.orders.index')
            ->with('status', __('Order status updated.'));
    }
}
