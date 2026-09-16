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

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        if ($status !== 'all' && ! in_array($status, ButcherPermit::STATUSES, true)) {
            $status = 'all';
        }

        $allPermits = $business->butcherPermits()->get();
        $today = now()->startOfDay();
        $expiringCutoff = now()->addDays(30)->endOfDay();

        $kpis = [
            'total' => $allPermits->count(),
            'valid' => $allPermits->where('status', ButcherPermit::STATUS_VALID)->count(),
            'expiring' => $allPermits->filter(function (ButcherPermit $permit) use ($today, $expiringCutoff) {
                $expiry = $permit->expiry_date;

                return $expiry !== null
                    && $expiry->greaterThanOrEqualTo($today)
                    && $expiry->lessThanOrEqualTo($expiringCutoff);
            })->count(),
            'expired' => $allPermits->where('status', ButcherPermit::STATUS_EXPIRED)->count(),
        ];

        $permitsQuery = $business->butcherPermits()->orderByDesc('expiry_date');

        if ($search !== '') {
            $permitsQuery->where(function ($query) use ($search) {
                $query->where('permit_number', 'like', '%'.$search.'%')
                    ->orWhere('issued_by', 'like', '%'.$search.'%');
            });
        }

        if ($status !== 'all') {
            $permitsQuery->where('status', $status);
        }

        $permits = $permitsQuery->get();

        return view('butcher.permits.index', [
            'business' => $business,
            'permits' => $permits,
            'kpis' => $kpis,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => ButcherPermit::STATUSES,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.permits.form', [
            'business' => $business,
            'permit' => null,
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

    public function edit(Request $request, ButcherPermit $permit): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        abort_unless((int) $permit->business_id === (int) $business->id, 404);

        return view('butcher.permits.form', [
            'business' => $business,
            'permit' => $permit,
            'permitTypes' => ButcherPermit::PERMIT_TYPES,
            'statuses' => ButcherPermit::STATUSES,
        ]);
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
