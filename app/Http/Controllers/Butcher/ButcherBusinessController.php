<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\UpdateButcherBusinessRequest;
use App\Models\Business;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherBusinessController extends Controller
{
    public function edit(Request $request, ButcherOnboardingService $onboarding): View|RedirectResponse
    {
        if ($request->user()->accessibleButcherBusinessIds()->isEmpty()) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.business.edit', [
            'business' => $onboarding->resolveButcherBusiness($request->user()),
        ]);
    }

    public function update(UpdateButcherBusinessRequest $request, ButcherOnboardingService $onboarding): RedirectResponse
    {
        if ($request->user()->accessibleButcherBusinessIds()->isEmpty()) {
            return redirect()->route('butcher.dashboard');
        }

        $business = $onboarding->resolveButcherBusiness($request->user());
        $validated = $request->validated();

        $business->fill([
            'business_name' => $validated['business_name'],
            'business_name_normalized' => Business::normalizeDisplayName($validated['business_name']),
            'registration_number' => $validated['registration_number'],
            'contact_phone' => $validated['contact_phone'],
            'email' => $validated['email'] ?? $business->email,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => Business::STATUS_ACTIVE,
        ]);
        $business->save();

        return redirect()
            ->route('butcher.business.edit')
            ->with('status', __('Business profile updated.'));
    }
}
