<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreSalePaymentRequest;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Farmer\SaleHistoryService;
use App\Services\Farmer\SalePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $farmIds = \App\Models\Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $records = SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->whereIn('farm_id', $farmIds))
            ->with(['sale.buyer', 'receiver'])
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString();

        return view('farmer.sales.payments.index', compact('records'));
    }

    public function create(Sale $sale): View
    {
        $this->authorize('view', $sale);

        return view('farmer.sales.payments.create', compact('sale'));
    }

    public function store(StoreSalePaymentRequest $request, Sale $sale, SalePaymentService $payments, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $sale);
        $payments->record($sale, $request->validated(), $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.sales.records.show', $sale)->with('status', __('Payment recorded.'));
    }
}
