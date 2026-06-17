<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherOutletRequest;
use App\Http\Requests\Butcher\StoreButcherPermitRequest;
use App\Http\Requests\Butcher\StoreButcherProfileRequest;
use App\Http\Requests\Butcher\StoreButcherSupplierRequest;
use App\Http\Requests\Butcher\UpdateButcherSupplierRequest;
use App\Models\ButcherSupplier;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherOnboardingController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherOnboardingService $onboarding,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard')
                ->with('status', __('No butcher business is linked to your account yet.'));
        }

        return view('butcher.onboarding.index', [
            'business' => $business,
            'progress' => $this->onboarding->getOnboardingProgress($business),
        ]);
    }

    public function profile(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.onboarding.profile', [
            'business' => $business,
            'progress' => $this->onboarding->getOnboardingProgress($business),
            'districts' => $this->onboarding->rwandaDistrictNames(),
        ]);
    }

    public function storeProfile(StoreButcherProfileRequest $request): RedirectResponse
    {
        $this->onboarding->createBusinessProfile($request->validated(), $request->user());

        return redirect()
            ->route('butcher.onboarding.outlets')
            ->with('status', __('Business profile saved.'));
    }

    public function outlets(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $business->load('butcherOutlets');

        return view('butcher.onboarding.outlets', [
            'business' => $business,
            'outlets' => $business->butcherOutlets,
            'progress' => $this->onboarding->getOnboardingProgress($business),
            'districts' => $this->onboarding->rwandaDistrictNames(),
        ]);
    }

    public function storeOutlet(StoreButcherOutletRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $this->onboarding->addOutlet($business, $request->validated());

        return redirect()
            ->route('butcher.onboarding.outlets')
            ->with('status', __('Outlet added.'));
    }

    public function permits(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $business->load('butcherPermits');

        return view('butcher.onboarding.permits', [
            'business' => $business,
            'permits' => $business->butcherPermits,
            'progress' => $this->onboarding->getOnboardingProgress($business),
        ]);
    }

    public function storePermit(StoreButcherPermitRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $this->onboarding->uploadPermit(
            $business,
            $request->validated(),
            $request->file('document'),
        );

        return redirect()
            ->route('butcher.onboarding.permits')
            ->with('status', __('Permit uploaded.'));
    }

    public function suppliers(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $business->load('butcherSuppliers');

        return view('butcher.onboarding.suppliers', [
            'business' => $business,
            'suppliers' => $business->butcherSuppliers,
            'progress' => $this->onboarding->getOnboardingProgress($business),
            'districts' => $this->onboarding->rwandaDistrictNames(),
            'editing' => $request->query('edit')
                ? $business->butcherSuppliers()->find($request->query('edit'))
                : null,
        ]);
    }

    public function storeSupplier(StoreButcherSupplierRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $this->onboarding->createSupplier($business, $request->validated());

        return redirect()
            ->route('butcher.onboarding.suppliers')
            ->with('status', __('Supplier added.'));
    }

    public function updateSupplier(UpdateButcherSupplierRequest $request, ButcherSupplier $supplier): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        abort_unless((int) $supplier->business_id === (int) $business->id, 404);

        $this->onboarding->updateSupplier($supplier, $request->validated());

        return redirect()
            ->route('butcher.onboarding.suppliers')
            ->with('status', __('Supplier updated.'));
    }

    public function destroySupplier(Request $request, ButcherSupplier $supplier): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        abort_unless((int) $supplier->business_id === (int) $business->id, 404);

        $supplier->delete();

        return redirect()
            ->route('butcher.onboarding.suppliers')
            ->with('status', __('Supplier removed.'));
    }
}
