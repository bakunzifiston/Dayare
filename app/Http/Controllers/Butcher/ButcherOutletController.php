<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherOutletRequest;
use App\Http\Requests\Butcher\UpdateButcherOutletRequest;
use App\Models\ButcherOutlet;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherOutletController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function index(Request $request, ButcherOnboardingService $onboarding): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $editingId = $request->integer('edit');
        $editing = $editingId > 0
            ? $business->butcherOutlets()->whereKey($editingId)->first()
            : null;

        return view('butcher.outlets.index', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->orderByDesc('is_primary')->orderBy('name')->get(),
            'editing' => $editing,
            'districts' => $onboarding->rwandaDistrictNames(),
        ]);
    }

    public function store(StoreButcherOutletRequest $request, ButcherOnboardingService $onboarding): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null, 404);

        $onboarding->addOutlet($business, $request->validated());

        return redirect()
            ->route('butcher.outlets.index')
            ->with('status', __('Outlet created.'));
    }

    public function update(
        UpdateButcherOutletRequest $request,
        ButcherOutlet $outlet,
        ButcherOnboardingService $onboarding,
    ): RedirectResponse {
        abort_unless(
            $this->accessibleBusinessIds($request)->contains((int) $outlet->business_id),
            404
        );

        $onboarding->updateOutlet($outlet, $request->validated());

        return redirect()
            ->route('butcher.outlets.index')
            ->with('status', __('Outlet updated.'));
    }
}
