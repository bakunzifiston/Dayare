<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherCustomerRequest;
use App\Models\ButcherCustomer;
use App\Services\Butcher\ButcherSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherCustomerController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherSalesService $sales,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $customers = $business->butcherCustomers()
            ->orderBy('name')
            ->paginate(20);

        return view('butcher.customers.index', [
            'business' => $business,
            'customers' => $customers,
            'tiers' => ButcherCustomer::TIERS,
        ]);
    }

    public function store(StoreButcherCustomerRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $this->sales->createCustomer($business, $request->validated());

        return redirect()
            ->route('butcher.customers.index')
            ->with('status', __('Customer added.'));
    }
}
