<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostMortemInspectionRequest;
use App\Http\Requests\UpdatePostMortemInspectionRequest;
use App\Models\Batch;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostMortemInspectionController extends Controller
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

    private function userExecutionIds(Request $request): \Illuminate\Support\Collection
    {
        return SlaughterExecution::whereIn('slaughter_plan_id', $this->userSlaughterPlanIds($request))
            ->pluck('id');
    }

    private function userBatchIds(Request $request): \Illuminate\Support\Collection
    {
        return Batch::whereIn('slaughter_execution_id', $this->userExecutionIds($request))
            ->pluck('id');
    }

    private function authorizeInspection(Request $request, PostMortemInspection $inspection): void
    {
        if (! $this->userBatchIds($request)->contains($inspection->batch_id)) {
            abort(404);
        }
    }

    private function authorizeBatchId(Request $request, int $batchId): void
    {
        if (! $this->userBatchIds($request)->contains($batchId)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);

        $inspections = PostMortemInspection::with(['batch.slaughterExecution.slaughterPlan.facility', 'inspector'])
            ->whereIn('batch_id', $batchIds)
            ->latest('inspection_date')
            ->paginate(10);

        $kpis = [
            'total' => PostMortemInspection::whereIn('batch_id', $batchIds)->count(),
        ];

        return view('post-mortem-inspections.index', compact('inspections', 'kpis'));
    }

    public function create(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        // Batches that don't have a post-mortem yet
        $batches = Batch::with('slaughterExecution.slaughterPlan.facility')
            ->whereIn('id', $batchIds)
            ->whereDoesntHave('postMortemInspection')
            ->latest()
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'label' => $b->batch_code . ' — ' . $b->slaughterExecution->slaughterPlan->facility->facility_name . ' (' . $b->species . ')',
                'facility_id' => $b->slaughterExecution->slaughterPlan->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        return view('post-mortem-inspections.create', [
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
        ]);
    }

    public function store(StorePostMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        PostMortemInspection::create($request->validated());

        return redirect()->route('post-mortem-inspections.index')
            ->with('status', __('Post-mortem inspection recorded successfully.'));
    }

    public function show(Request $request, PostMortemInspection $postMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $postMortemInspection->load(['batch.slaughterExecution.slaughterPlan.facility', 'inspector']);

        return view('post-mortem-inspections.show', ['inspection' => $postMortemInspection]);
    }

    public function edit(Request $request, PostMortemInspection $postMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);

        $batchIds = $this->userBatchIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $batches = Batch::with('slaughterExecution.slaughterPlan.facility')
            ->whereIn('id', $batchIds)
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'label' => $b->batch_code . ' — ' . $b->slaughterExecution->slaughterPlan->facility->facility_name,
                'facility_id' => $b->slaughterExecution->slaughterPlan->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        return view('post-mortem-inspections.edit', [
            'inspection' => $postMortemInspection,
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
        ]);
    }

    public function update(UpdatePostMortemInspectionRequest $request, PostMortemInspection $postMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        $postMortemInspection->update($request->validated());

        return redirect()->route('post-mortem-inspections.index')
            ->with('status', __('Post-mortem inspection updated successfully.'));
    }

    public function destroy(Request $request, PostMortemInspection $postMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $postMortemInspection->delete();

        return redirect()->route('post-mortem-inspections.index')
            ->with('status', __('Post-mortem inspection removed.'));
    }
}
