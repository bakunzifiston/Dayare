<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherDeliveryRequest;
use App\Models\ButcherDelivery;
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

        $period = (string) $request->query('period', '30d');

        $deliveries = $business->butcherDeliveries()
            ->with(['supplier', 'outlet'])
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        return view('butcher.receiving.index', [
            'business' => $business,
            'summary' => $this->procurement->getReceivingSummary($business, $period),
            'period' => $period,
            'deliveries' => $deliveries,
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
                ->route('butcher.dashboard')
                ->with('status', __('Configure an outlet before receiving stock.'));
        }

        return view('butcher.receiving.create', [
            'business' => $business,
            'suppliers' => $suppliers,
            'outlets' => $outlets,
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
            'inventoryBatch',
            'rejection',
            'receivedByUser',
        ]);

        return view('butcher.receiving.show', [
            'business' => $business,
            'delivery' => $delivery,
        ]);
    }
}
