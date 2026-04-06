<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaughterExecutionController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function userSlaughterPlanIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterPlan::whereIn('facility_id', $this->userFacilityIds($request))
            ->pluck('id');
    }

    private function authorizeExecution(Request $request, SlaughterExecution $execution): void
    {
        if (! $this->userSlaughterPlanIds($request)->contains($execution->slaughter_plan_id)) {
            abort(404);
        }
    }

    private function authorizePlanId(Request $request, int $planId): void
    {
        if (! $this->userSlaughterPlanIds($request)->contains($planId)) {
            abort(404);
        }
    }

    public function hub(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);
        $base = SlaughterExecution::query()->whereIn('slaughter_plan_id', $planIds);

        $totalExecutions = (clone $base)->count();
        $completedCount = (clone $base)->where('status', SlaughterExecution::STATUS_COMPLETED)->count();
        $inProgressCount = (clone $base)->where('status', SlaughterExecution::STATUS_IN_PROGRESS)->count();
        $scheduledCount = (clone $base)->where('status', SlaughterExecution::STATUS_SCHEDULED)->count();
        $cancelledCount = (clone $base)->where('status', SlaughterExecution::STATUS_CANCELLED)->count();
        $totalAnimalsSlaughtered = (int) (clone $base)->sum('actual_animals_slaughtered');
        $executionsWithBatchCount = (clone $base)->has('batches')->count();

        return view('slaughter-executions.hub', compact(
            'totalExecutions',
            'completedCount',
            'inProgressCount',
            'scheduledCount',
            'cancelledCount',
            'totalAnimalsSlaughtered',
            'executionsWithBatchCount',
        ));
    }

    public function index(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        $executions = SlaughterExecution::with(['slaughterPlan.facility', 'slaughterPlan.inspector'])
            ->whereIn('slaughter_plan_id', $planIds)
            ->latest('slaughter_time')
            ->paginate(10);

        $kpis = [
            'total' => SlaughterExecution::whereIn('slaughter_plan_id', $planIds)->count(),
            'completed' => SlaughterExecution::whereIn('slaughter_plan_id', $planIds)->where('status', SlaughterExecution::STATUS_COMPLETED)->count(),
        ];

        return view('slaughter-executions.index', compact('executions', 'kpis'));
    }

    public function create(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        $plans = SlaughterPlan::with('facility')
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get()
            ->map(fn (SlaughterPlan $p) => [
                'id' => $p->id,
                'label' => $p->slaughter_date->format('d M Y').' — '.$p->facility->facility_name.' ('.$p->species.', '.$p->number_of_animals_scheduled.' scheduled)',
            ]);

        return view('slaughter-executions.create', compact('plans'));
    }

    public function store(StoreSlaughterExecutionRequest $request): RedirectResponse
    {
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        SlaughterExecution::create($request->validated());

        return redirect()->route('slaughter-executions.hub')
            ->with('status', __('Slaughter execution recorded successfully.'));
    }

    public function show(Request $request, SlaughterExecution $slaughterExecution): View|RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);
        $slaughterExecution->load(['slaughterPlan.facility.business', 'slaughterPlan.inspector', 'batches']);

        return view('slaughter-executions.show', ['execution' => $slaughterExecution]);
    }

    public function edit(Request $request, SlaughterExecution $slaughterExecution): View|RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);

        $planIds = $this->userSlaughterPlanIds($request);

        $plans = SlaughterPlan::with('facility')
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get()
            ->map(fn (SlaughterPlan $p) => [
                'id' => $p->id,
                'label' => $p->slaughter_date->format('d M Y').' — '.$p->facility->facility_name.' ('.$p->species.')',
            ]);

        return view('slaughter-executions.edit', [
            'execution' => $slaughterExecution,
            'plans' => $plans,
        ]);
    }

    public function update(UpdateSlaughterExecutionRequest $request, SlaughterExecution $slaughterExecution): RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        $slaughterExecution->update($request->validated());

        return redirect()->route('slaughter-executions.hub')
            ->with('status', __('Slaughter execution updated successfully.'));
    }

    public function destroy(Request $request, SlaughterExecution $slaughterExecution): RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);
        $slaughterExecution->delete();

        return redirect()->route('slaughter-executions.hub')
            ->with('status', __('Slaughter execution removed.'));
    }
}
