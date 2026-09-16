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

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $baseQuery = $business->butcherOutlets();
        $kpis = [
            'total' => (int) (clone $baseQuery)->count(),
            'active' => (int) (clone $baseQuery)->where('status', ButcherOutlet::STATUS_ACTIVE)->count(),
            'inactive' => (int) (clone $baseQuery)->where('status', ButcherOutlet::STATUS_INACTIVE)->count(),
            'primary' => (int) (clone $baseQuery)->where('is_primary', true)->count(),
        ];

        $outletsQuery = $business->butcherOutlets()
            ->orderByDesc('is_primary')
            ->orderBy('name');

        if ($search !== '') {
            $outletsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('district', 'like', '%'.$search.'%')
                    ->orWhere('sector', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $outletsQuery->where('status', ButcherOutlet::STATUS_ACTIVE);
        } elseif ($status === 'inactive') {
            $outletsQuery->where('status', ButcherOutlet::STATUS_INACTIVE);
        }

        $outlets = $outletsQuery->get();

        return view('butcher.outlets.index', [
            'business' => $business,
            'outlets' => $outlets,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request, ButcherOnboardingService $onboarding): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.outlets.form', [
            'business' => $business,
            'outlet' => null,
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

    public function edit(
        Request $request,
        ButcherOutlet $outlet,
        ButcherOnboardingService $onboarding,
    ): View|RedirectResponse {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        abort_unless((int) $outlet->business_id === (int) $business->id, 404);

        return view('butcher.outlets.form', [
            'business' => $business,
            'outlet' => $outlet,
            'districts' => $onboarding->rwandaDistrictNames(),
        ]);
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
