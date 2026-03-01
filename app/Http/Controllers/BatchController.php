<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\Batch;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->pluck('id');
    }

    private function userSlaughterPlanIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterPlan::whereIn('facility_id', $this->userFacilityIds($request))
            ->pluck('id');
    }

    private function userExecutionIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterExecution::whereIn('slaughter_plan_id', $this->userSlaughterPlanIds($request))
            ->pluck('id');
    }

    private function authorizeBatch(Request $request, Batch $batch): void
    {
        if (! $this->userExecutionIds($request)->contains($batch->slaughter_execution_id)) {
            abort(404);
        }
    }

    private function authorizeExecutionId(Request $request, int $executionId): void
    {
        if (! $this->userExecutionIds($request)->contains($executionId)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $executionIds = $this->userExecutionIds($request);

        $batches = Batch::with(['slaughterExecution.slaughterPlan.facility', 'inspector'])
            ->whereIn('slaughter_execution_id', $executionIds)
            ->latest()
            ->paginate(10);

        $kpis = [
            'total' => Batch::whereIn('slaughter_execution_id', $executionIds)->count(),
            'approved' => Batch::whereIn('slaughter_execution_id', $executionIds)->where('status', Batch::STATUS_APPROVED)->count(),
            'pending' => Batch::whereIn('slaughter_execution_id', $executionIds)->where('status', Batch::STATUS_PENDING)->count(),
        ];

        return view('batches.index', compact('batches', 'kpis'));
    }

    public function create(Request $request): View
    {
        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $executions = SlaughterExecution::with('slaughterPlan.facility')
            ->whereIn('id', $executionIds)
            ->latest('slaughter_time')
            ->get()
            ->map(fn (SlaughterExecution $e) => [
                'id' => $e->id,
                'label' => $e->slaughter_time->format('d M Y H:i') . ' — ' . $e->slaughterPlan->facility->facility_name . ' (' . $e->actual_animals_slaughtered . ' animals)',
                'facility_id' => $e->slaughterPlan->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        return view('batches.create', [
            'executions' => $executions,
            'inspectorsByFacility' => $inspectorsByFacility,
        ]);
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        $this->authorizeExecutionId($request, (int) $request->validated('slaughter_execution_id'));

        Batch::create($request->validated());

        return redirect()->route('batches.index')
            ->with('status', __('Batch created successfully.'));
    }

    public function show(Request $request, Batch $batch): View|RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $batch->load(['slaughterExecution.slaughterPlan.facility.business', 'inspector', 'postMortemInspection', 'certificate']);

        return view('batches.show', ['batch' => $batch]);
    }

    public function edit(Request $request, Batch $batch): View|RedirectResponse
    {
        $this->authorizeBatch($request, $batch);

        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $executions = SlaughterExecution::with('slaughterPlan.facility')
            ->whereIn('id', $executionIds)
            ->latest('slaughter_time')
            ->get()
            ->map(fn (SlaughterExecution $e) => [
                'id' => $e->id,
                'label' => $e->slaughter_time->format('d M Y H:i') . ' — ' . $e->slaughterPlan->facility->facility_name,
                'facility_id' => $e->slaughterPlan->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        return view('batches.edit', [
            'batch' => $batch,
            'executions' => $executions,
            'inspectorsByFacility' => $inspectorsByFacility,
        ]);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $this->authorizeExecutionId($request, (int) $request->validated('slaughter_execution_id'));

        $batch->update($request->validated());

        return redirect()->route('batches.index')
            ->with('status', __('Batch updated successfully.'));
    }

    public function destroy(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $batch->delete();

        return redirect()->route('batches.index')
            ->with('status', __('Batch removed.'));
    }
}
