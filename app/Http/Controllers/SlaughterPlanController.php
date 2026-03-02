<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
use App\Models\AnimalIntake;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaughterPlanController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->pluck('id');
    }

    private function authorizePlan(Request $request, SlaughterPlan $plan): void
    {
        if (! $this->userFacilityIds($request)->contains($plan->facility_id)) {
            abort(404);
        }
    }

    private function authorizeFacilityId(Request $request, int $facilityId): void
    {
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $plans = SlaughterPlan::with(['facility.business', 'inspector'])
            ->whereIn('facility_id', $facilityIds)
            ->latest('slaughter_date')
            ->paginate(10);

        $kpis = [
            'total' => SlaughterPlan::whereIn('facility_id', $facilityIds)->count(),
            'planned' => SlaughterPlan::whereIn('facility_id', $facilityIds)->where('status', SlaughterPlan::STATUS_PLANNED)->count(),
            'approved' => SlaughterPlan::whereIn('facility_id', $facilityIds)->where('status', SlaughterPlan::STATUS_APPROVED)->count(),
        ];

        return view('slaughter-plans.index', compact('plans', 'kpis'));
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $facilities = Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $eligibleIntakes = AnimalIntake::whereIn('facility_id', $facilityIds)
            ->where('status', AnimalIntake::STATUS_APPROVED)
            ->with('facility')
            ->latest('intake_date')
            ->get()
            ->filter(fn (AnimalIntake $i) => ! $i->isHealthCertificateExpired() && $i->remainingAnimalsAvailable() > 0)
            ->map(fn (AnimalIntake $i) => [
                'id' => $i->id,
                'facility_id' => $i->facility_id,
                'label' => $i->intake_date->format('d M Y') . ' — ' . $i->supplier_firstname . ' ' . $i->supplier_lastname . ' · ' . $i->species . ' · ' . $i->remainingAnimalsAvailable() . ' ' . __('available'),
            ]);

        return view('slaughter-plans.create', [
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
            'eligibleIntakes' => $eligibleIntakes,
        ]);
    }

    public function store(StoreSlaughterPlanRequest $request): RedirectResponse
    {
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        SlaughterPlan::create($request->validated());

        return redirect()->route('slaughter-plans.index')
            ->with('status', __('Slaughter plan created successfully.'));
    }

    public function show(Request $request, SlaughterPlan $slaughterPlan): View|RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);
        $slaughterPlan->load(['facility.business', 'inspector', 'animalIntake', 'anteMortemInspections', 'slaughterExecutions']);

        return view('slaughter-plans.show', ['plan' => $slaughterPlan]);
    }

    public function edit(Request $request, SlaughterPlan $slaughterPlan): View|RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);

        $facilityIds = $this->userFacilityIds($request);

        $facilities = Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $eligibleIntakes = AnimalIntake::whereIn('facility_id', $facilityIds)
            ->where('status', AnimalIntake::STATUS_APPROVED)
            ->with('facility')
            ->latest('intake_date')
            ->get()
            ->filter(fn (AnimalIntake $i) => ! $i->isHealthCertificateExpired() && ($i->remainingAnimalsAvailable() > 0 || ($slaughterPlan->animal_intake_id == $i->id)))
            ->map(fn (AnimalIntake $i) => [
                'id' => $i->id,
                'facility_id' => $i->facility_id,
                'label' => $i->intake_date->format('d M Y') . ' — ' . $i->supplier_firstname . ' ' . $i->supplier_lastname . ' · ' . $i->species . ' · ' . $i->remainingAnimalsAvailable() . ' ' . __('available'),
            ]);

        return view('slaughter-plans.edit', [
            'plan' => $slaughterPlan,
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
            'eligibleIntakes' => $eligibleIntakes,
        ]);
    }

    public function update(UpdateSlaughterPlanRequest $request, SlaughterPlan $slaughterPlan): RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        $slaughterPlan->update($request->validated());

        return redirect()->route('slaughter-plans.index')
            ->with('status', __('Slaughter plan updated successfully.'));
    }

    public function destroy(Request $request, SlaughterPlan $slaughterPlan): RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);
        $slaughterPlan->delete();

        return redirect()->route('slaughter-plans.index')
            ->with('status', __('Slaughter plan removed.'));
    }
}
