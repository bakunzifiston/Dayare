<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherCustomerRequest;
use App\Http\Requests\Butcher\UpdateButcherCustomerRequest;
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

        $search = trim((string) $request->query('q', ''));
        $tier = (string) $request->query('tier', 'all');
        if ($tier !== 'all' && ! in_array($tier, ButcherCustomer::TIERS, true)) {
            $tier = 'all';
        }

        $baseQuery = $business->butcherCustomers();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'wholesale' => (int) (clone $baseQuery)->where('tier', ButcherCustomer::TIER_WHOLESALE)->count(),
            'with_credit' => (int) (clone $baseQuery)->where('credit_limit', '>', 0)->count(),
            'outstanding' => (float) (clone $baseQuery)->sum('outstanding_balance'),
        ];

        $customersQuery = $business->butcherCustomers()->orderBy('name');

        if ($search !== '') {
            $customersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($tier !== 'all') {
            $customersQuery->where('tier', $tier);
        }

        return view('butcher.customers.index', [
            'business' => $business,
            'customers' => $customersQuery->paginate(20)->withQueryString(),
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'tier' => $tier,
            ],
            'tiers' => ButcherCustomer::TIERS,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.customers.form', [
            'business' => $business,
            'customer' => null,
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

    public function edit(Request $request, ButcherCustomer $customer): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $customer->business_id === (int) $business->id, 404);

        return view('butcher.customers.form', [
            'business' => $business,
            'customer' => $customer,
            'tiers' => ButcherCustomer::TIERS,
        ]);
    }

    public function update(UpdateButcherCustomerRequest $request, ButcherCustomer $customer): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $customer->business_id === (int) $business->id, 404);

        $this->sales->updateCustomer($customer, $request->validated());

        return redirect()
            ->route('butcher.customers.index')
            ->with('status', __('Customer updated.'));
    }
}
