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
use App\Support\PostMortemChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    private function checklistConfig(): array
    {
        return PostMortemChecklist::all();
    }

    private function mapObservationPayload(array $observations, string $species): array
    {
        $items = PostMortemChecklist::itemsForSpecies($species);

        return collect($observations)
            ->filter(fn ($row, $item) => array_key_exists($item, $items))
            ->map(function ($row, $item) use ($items) {
                return [
                    'category' => (string) ($items[$item]['category'] ?? 'carcass'),
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function computeResult(string $species, array $observations): string
    {
        $hasMinor = false;

        foreach ($observations as $item => $row) {
            $value = (string) ($row['value'] ?? '');
            if (! PostMortemChecklist::isAbnormalValue($value)) {
                continue;
            }

            if (PostMortemChecklist::isCriticalItem($species, (string) $item)) {
                return PostMortemInspection::RESULT_REJECTED;
            }

            $hasMinor = true;
        }

        return $hasMinor ? PostMortemInspection::RESULT_PARTIAL : PostMortemInspection::RESULT_APPROVED;
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

        $batches = Batch::with('slaughterExecution.slaughterPlan.facility')
            ->whereIn('id', $batchIds)
            ->whereDoesntHave('postMortemInspection')
            ->latest()
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'label' => $b->batch_code.' — '.$b->slaughterExecution->slaughterPlan->facility->facility_name.' ('.$b->species.')',
                'facility_id' => $b->slaughterExecution->slaughterPlan->facility_id,
                'species' => $b->species,
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
            'checklists' => $this->checklistConfig(),
        ]);
    }

    public function store(StorePostMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $species = (string) ($validated['species'] ?? '');
        unset($validated['observations']);

        $validated['result'] = $this->computeResult($species, $observations);

        DB::transaction(function () use ($validated, $observations, $species) {
            $inspection = PostMortemInspection::create($validated);
            $inspection->observations()->createMany($this->mapObservationPayload($observations, $species));
        });

        return redirect()->route('post-mortem-inspections.index')
            ->with('status', __('Post-mortem inspection recorded successfully.'));
    }

    public function show(Request $request, PostMortemInspection $postMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $postMortemInspection->load(['batch.slaughterExecution.slaughterPlan.facility', 'inspector', 'observations']);

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
                'label' => $b->batch_code.' — '.$b->slaughterExecution->slaughterPlan->facility->facility_name,
                'facility_id' => $b->slaughterExecution->slaughterPlan->facility_id,
                'species' => $b->species,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $postMortemInspection->load('observations');

        return view('post-mortem-inspections.edit', [
            'inspection' => $postMortemInspection,
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
        ]);
    }

    public function update(UpdatePostMortemInspectionRequest $request, PostMortemInspection $postMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $species = (string) ($validated['species'] ?? '');
        unset($validated['observations']);

        $validated['result'] = $this->computeResult($species, $observations);

        DB::transaction(function () use ($postMortemInspection, $validated, $observations, $species) {
            $postMortemInspection->update($validated);
            $postMortemInspection->observations()->delete();
            $postMortemInspection->observations()->createMany($this->mapObservationPayload($observations, $species));
        });

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
