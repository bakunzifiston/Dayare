<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherPermitRequest;
use App\Http\Requests\Butcher\UpdateButcherPermitRequest;
use App\Models\ButcherPermit;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherPermitController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $editingId = $request->integer('edit');
        $editing = $editingId > 0
            ? $business->butcherPermits()->whereKey($editingId)->first()
            : null;

        return view('butcher.permits.index', [
            'business' => $business,
            'permits' => $business->butcherPermits()->orderByDesc('expiry_date')->get(),
            'editing' => $editing,
            'permitTypes' => ButcherPermit::PERMIT_TYPES,
            'statuses' => ButcherPermit::STATUSES,
        ]);
    }

    public function store(StoreButcherPermitRequest $request, ButcherOnboardingService $onboarding): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null, 404);

        $onboarding->uploadPermit(
            $business,
            $request->validated(),
            $request->file('document')
        );

        return redirect()
            ->route('butcher.permits.index')
            ->with('status', __('Permit saved.'));
    }

    public function update(
        UpdateButcherPermitRequest $request,
        ButcherPermit $permit,
        ButcherOnboardingService $onboarding,
    ): RedirectResponse {
        abort_unless(
            $this->accessibleBusinessIds($request)->contains((int) $permit->business_id),
            404
        );

        $onboarding->updatePermit($permit, $request->validated(), $request->file('document'));

        return redirect()
            ->route('butcher.permits.index')
            ->with('status', __('Permit updated.'));
    }
}
