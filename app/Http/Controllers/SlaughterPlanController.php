<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
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

        return view('slaughter-plans.index', compact('plans'));
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

        return view('slaughter-plans.create', [
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
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
        $slaughterPlan->load(['facility.business', 'inspector', 'anteMortemInspections', 'slaughterExecutions']);

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

        return view('slaughter-plans.edit', [
            'plan' => $slaughterPlan,
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
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
