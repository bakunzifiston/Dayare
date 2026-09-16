<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherSupplierRequest;
use App\Http\Requests\Butcher\UpdateButcherSupplierRequest;
use App\Models\ButcherSupplier;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherSupplierController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
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
        $type = (string) $request->query('type', 'all');

        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }
        if ($type !== 'all' && ! in_array($type, ButcherSupplier::SUPPLIER_TYPES, true)) {
            $type = 'all';
        }

        $baseQuery = $business->butcherSuppliers();

        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'active' => (int) (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (int) (clone $baseQuery)->where('is_active', false)->count(),
            'types' => (int) (clone $baseQuery)->select('supplier_type')->distinct()->get()->count(),
        ];

        $suppliersQuery = $business->butcherSuppliers()
            ->withCount(['purchaseOrders', 'deliveries'])
            ->orderBy('name');

        if ($search !== '') {
            $suppliersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('district', 'like', '%'.$search.'%')
                    ->orWhere('sector', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $suppliersQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $suppliersQuery->where('is_active', false);
        }

        if ($type !== 'all') {
            $suppliersQuery->where('supplier_type', $type);
        }

        $suppliers = $suppliersQuery
            ->paginate(20)
            ->withQueryString();

        return view('butcher.suppliers.index', [
            'business' => $business,
            'suppliers' => $suppliers,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.suppliers.form', [
            'business' => $business,
            'supplier' => null,
            'districts' => $this->onboarding->rwandaDistrictNames(),
        ]);
    }

    public function store(StoreButcherSupplierRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $this->onboarding->createSupplier($business, $request->validated());

        return redirect()
            ->route('butcher.suppliers.index')
            ->with('status', __('Supplier added.'));
    }

    public function edit(Request $request, ButcherSupplier $supplier): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        abort_unless((int) $supplier->business_id === (int) $business->id, 404);

        return view('butcher.suppliers.form', [
            'business' => $business,
            'supplier' => $supplier,
            'districts' => $this->onboarding->rwandaDistrictNames(),
        ]);
    }

    public function update(UpdateButcherSupplierRequest $request, ButcherSupplier $supplier): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        abort_unless((int) $supplier->business_id === (int) $business->id, 404);

        $this->onboarding->updateSupplier($supplier, $request->validated());

        return redirect()
            ->route('butcher.suppliers.index')
            ->with('status', __('Supplier updated.'));
    }

    public function destroy(Request $request, ButcherSupplier $supplier): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        abort_unless((int) $supplier->business_id === (int) $business->id, 404);

        $supplier->delete();

        return redirect()
            ->route('butcher.suppliers.index')
            ->with('status', __('Supplier removed.'));
    }
}
