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

        $business->load('butcherSuppliers');

        return view('butcher.suppliers.index', [
            'business' => $business,
            'suppliers' => $business->butcherSuppliers,
            'districts' => $this->onboarding->rwandaDistrictNames(),
            'editing' => $request->query('edit')
                ? $business->butcherSuppliers()->find($request->query('edit'))
                : null,
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
