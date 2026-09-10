<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\UpdateButcherBusinessRequest;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherBusinessController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function edit(Request $request, ButcherOnboardingService $onboarding): View|RedirectResponse
    {
        if ($this->accessibleBusinessIds($request)->isEmpty()) {
            return redirect()->route('butcher.dashboard');
        }

        $business = $onboarding->resolveButcherBusiness($request->user());

        return view('butcher.business.edit', [
            'business' => $business,
            'districts' => $onboarding->rwandaDistrictNames(),
            'progress' => $onboarding->getOnboardingProgress($business),
        ]);
    }

    public function update(UpdateButcherBusinessRequest $request, ButcherOnboardingService $onboarding): RedirectResponse
    {
        if ($this->accessibleBusinessIds($request)->isEmpty()) {
            return redirect()->route('butcher.dashboard');
        }

        $business = $onboarding->resolveButcherBusiness($request->user());
        $onboarding->updateBusinessProfile($business, $request->validated());

        return redirect()
            ->route('butcher.business.edit')
            ->with('status', __('Business profile updated.'));
    }
}
