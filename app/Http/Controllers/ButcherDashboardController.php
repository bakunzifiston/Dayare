<?php

namespace App\Http\Controllers;

use App\Services\Butcher\ButcherDashboardService;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ButcherDashboardService $dashboard,
        ButcherOnboardingService $onboarding,
    ): View|RedirectResponse {
        $user = $request->user();
        $business = $user->accessibleButcherBusinessIds()->isNotEmpty()
            ? $onboarding->resolveButcherBusiness($user)
            : null;

        if ($business !== null) {
            $progress = $onboarding->getOnboardingProgress($business);
            if (($progress['percent'] ?? 0) < 100) {
                return redirect()->route('butcher.onboarding.index');
            }
        }

        $payload = $dashboard->build($user);

        return view('butcher.dashboard', array_merge(['user' => $user], $payload));
    }
}
