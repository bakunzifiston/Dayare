<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostMortemInspectionRequest;
use App\Http\Requests\UpdatePostMortemInspectionRequest;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Support\PostMortemChecklist;
use App\Support\PostMortemMeatTotals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    private function mapObservationPayload(array $observations, string $species, ?int $animalIntakeItemId = null): array
    {
        $items = PostMortemChecklist::itemsForSpecies($species);

        return collect($observations)
            ->filter(fn ($row, $item) => array_key_exists($item, $items))
            ->map(function ($row, $item) use ($items, $animalIntakeItemId) {
                return [
                    'animal_intake_item_id' => $animalIntakeItemId,
                    'category' => (string) ($items[$item]['category'] ?? 'carcass'),
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $legacyObservations
     * @param  array<int, array<string, mixed>>  $itemOutcomes
     */
    private function syncObservations(
        PostMortemInspection $inspection,
        array $legacyObservations,
        array $itemOutcomes,
        bool $perAnimal,
        string $species,
    ): void {
        $inspection->observations()->delete();

        if ($perAnimal) {
            $rows = [];
            foreach ($itemOutcomes as $outcome) {
                $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
                if ($animalId === 0) {
                    continue;
                }

                $rows = array_merge(
                    $rows,
                    $this->mapObservationPayload($outcome['observations'] ?? [], $species, $animalId),
                );
            }

            if ($rows !== []) {
                $inspection->observations()->createMany($rows);
            }

            return;
        }

        if ($legacyObservations !== []) {
            $inspection->observations()->createMany(
                $this->mapObservationPayload($legacyObservations, $species),
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemOutcomes
     * @return array<int, array<string, mixed>>
     */
    private function ensureBatchItems(Batch $batch, array $itemOutcomes): array
    {
        $reference = $batch->slaughterExecution;
        $executionIds = $reference
            ? SlaughterExecution::query()->sameDayAndFacility($reference)->pluck('id')
            : collect([$batch->slaughter_execution_id]);

        $prepared = [];

        foreach ($itemOutcomes as $outcome) {
            $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
            if ($animalId === 0) {
                continue;
            }

            if (! empty($outcome['batch_item_id'])) {
                $prepared[] = $outcome;
                continue;
            }

            $batchItem = $batch->items()->where('animal_intake_item_id', $animalId)->first();
            if ($batchItem === null) {
                $executionItem = SlaughterExecutionItem::query()
                    ->where('animal_intake_item_id', $animalId)
                    ->whereIn('slaughter_execution_id', $executionIds)
                    ->first();

                if ($executionItem === null) {
                    continue;
                }

                $batchItem = $batch->items()->create([
                    'slaughter_execution_item_id' => $executionItem->id,
                    'animal_intake_item_id' => $animalId,
                    'meat_quantity_kg' => $executionItem->meat_quantity_kg,
                ]);
            }

            $outcome['batch_item_id'] = $batchItem->id;
            $prepared[] = $outcome;
        }

        return $prepared;
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemOutcomes
     */
    private function syncInspectionItems(PostMortemInspection $inspection, Batch $batch, array $itemOutcomes): void
    {
        $inspection->inspectionItems()->delete();

        if ($itemOutcomes === []) {
            $inspection->update([
                'total_examined' => 0,
                'approved_quantity' => 0,
                'condemned_quantity' => 0,
            ]);

            return;
        }

        foreach ($itemOutcomes as $outcome) {
            $inspection->inspectionItems()->create([
                'batch_item_id' => $outcome['batch_item_id'],
                'animal_intake_item_id' => $outcome['animal_intake_item_id'],
                'outcome' => $outcome['outcome'],
                'outcome_notes' => $outcome['outcome_notes'] ?? null,
                'carcass_weight_kg' => $outcome['carcass_weight_kg'] ?? null,
            ]);
        }

        $animalsById = $batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');
        $inspection->update(PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBatchAnimalsByBatchId(Collection $batchIds): array
    {
        return Batch::query()
            ->whereIn('id', $batchIds)
            ->with(['items.intakeItem', 'slaughterExecution.executionItems.intakeItem', 'slaughterExecution.slaughterPlan'])
            ->get()
            ->mapWithKeys(function (Batch $batch) {
                $animals = $batch->inspectableAnimalsForPostMortem()->values()->all();

                return [
                    $batch->id => [
                        'facility_id' => $batch->slaughterExecution->slaughterPlan->facility_id,
                        'species' => $batch->species,
                        'animal_count' => count($animals),
                        'has_per_animal' => count($animals) > 0,
                        'source' => $batch->hasPerAnimalData() ? 'batch' : 'execution',
                        'animals' => $animals,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     * @return array<int, array{outcome: string, outcome_notes: string, carcass_weight_kg: string|null, observations: array<string, array{value: string, notes: string|null}>}>
     */
    private function mapOldItemOutcomes(?array $rows): array
    {
        if ($rows === null || $rows === []) {
            return [];
        }

        $mapped = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $animalId = (int) ($row['animal_intake_item_id'] ?? 0);
            if ($animalId === 0) {
                continue;
            }

            $mapped[$animalId] = [
                'batch_item_id' => $row['batch_item_id'] ?? null,
                'outcome' => (string) ($row['outcome'] ?? ''),
                'outcome_notes' => (string) ($row['outcome_notes'] ?? ''),
                'carcass_weight_kg' => $row['carcass_weight_kg'] ?? null,
                'observations' => is_array($row['observations'] ?? null) ? $row['observations'] : [],
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{outcome: string, outcome_notes: string, carcass_weight_kg: string|null, observations: array<string, array{value: string, notes: string|null}>}>
     */
    private function mapExistingInspectionOutcomes(PostMortemInspection $inspection): array
    {
        $obsByAnimal = $inspection->observations
            ->whereNotNull('animal_intake_item_id')
            ->groupBy('animal_intake_item_id');

        return $inspection->inspectionItems
            ->mapWithKeys(function (PostMortemInspectionItem $item) use ($obsByAnimal) {
                return [
                    $item->animal_intake_item_id => [
                        'batch_item_id' => $item->batch_item_id,
                        'outcome' => $item->outcome,
                        'outcome_notes' => $item->outcome_notes ?? '',
                        'carcass_weight_kg' => $item->carcass_weight_kg,
                        'observations' => ($obsByAnimal->get($item->animal_intake_item_id) ?? collect())
                            ->mapWithKeys(fn ($obs) => [
                                $obs->item => [
                                    'value' => $obs->value,
                                    'notes' => $obs->notes,
                                ],
                            ])
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    private function computeResultFromItems(array $itemOutcomes): string
    {
        $hasCondemned = false;
        $hasDeferred = false;

        foreach ($itemOutcomes as $outcome) {
            if (($outcome['outcome'] ?? '') === PostMortemInspectionItem::OUTCOME_CONDEMNED) {
                $hasCondemned = true;
            }
            if (($outcome['outcome'] ?? '') === PostMortemInspectionItem::OUTCOME_DEFERRED) {
                $hasDeferred = true;
            }
        }

        if ($hasCondemned) {
            return PostMortemInspection::RESULT_PARTIAL;
        }

        if ($hasDeferred) {
            return PostMortemInspection::RESULT_PARTIAL;
        }

        return PostMortemInspection::RESULT_APPROVED;
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

    /**
     * @param  Collection<int, int|string>  $batchIds
     * @return array<string, int|float>
     */
    private function buildPmHubStats(Collection $batchIds): array
    {
        return [
            'total_inspections' => PostMortemInspection::whereIn('batch_id', $batchIds)->count(),
            'animals_examined' => (int) PostMortemInspectionItem::whereHas(
                'inspection',
                fn ($q) => $q->whereIn('batch_id', $batchIds)
            )->count(),
            'condemned_this_week' => PostMortemInspectionItem::whereHas(
                'inspection',
                fn ($q) => $q->whereIn('batch_id', $batchIds)
            )->condemned()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'batches_without_pm' => Batch::whereIn('id', $batchIds)
                ->whereDoesntHave('postMortemInspection')
                ->where('status', '!=', 'rejected')
                ->count(),
            'ready_for_cert' => PostMortemInspection::whereIn('batch_id', $batchIds)
                ->where('approved_quantity', '>', 0)
                ->whereHas('batch', fn ($q) => $q->doesntHave('certificate'))
                ->count(),
        ];
    }

    public function hub(Request $request): View
    {
        // --- Hub ---
        $batchIds = $this->userBatchIds($request);

        $hubStats = $this->buildPmHubStats($batchIds);

        $byResult = PostMortemInspection::whereIn('batch_id', $batchIds)
            ->with(['batch.slaughterExecution.slaughterPlan.facility', 'batch.certificate', 'inspector', 'inspectionItems'])
            ->orderByDesc('inspection_date')
            ->get()
            ->groupBy('result');

        $recentInspections = PostMortemInspection::whereIn('batch_id', $batchIds)
            ->with(['batch.slaughterExecution.slaughterPlan.facility', 'inspectionItems'])
            ->orderByDesc('inspection_date')
            ->limit(10)
            ->get();

        return view('post-mortem-inspections.hub', compact(
            'hubStats',
            'byResult',
            'recentInspections',
        ));
    }

    public function index(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);

        $hubStats = $this->buildPmHubStats($batchIds);

        $query = PostMortemInspection::query()
            ->with([
                'batch.slaughterExecution.slaughterPlan.facility',
                'batch.certificate',
                'inspector',
                'inspectionItems.intakeItem',
                'inspectionItems.batchItem',
            ])
            ->whereIn('batch_id', $batchIds);

        if ($request->filled('batch_id')) {
            $batchId = (int) $request->query('batch_id');
            if ($batchIds->contains($batchId)) {
                $query->where('batch_id', $batchId);
            }
        }

        if ($request->filled('result')) {
            $result = (string) $request->query('result');
            if (in_array($result, [
                PostMortemInspection::RESULT_APPROVED,
                PostMortemInspection::RESULT_PARTIAL,
                PostMortemInspection::RESULT_REJECTED,
            ], true)) {
                $query->where('result', $result);
            }
        }

        if ($request->query('has_per_animal') === '1') {
            $query->has('inspectionItems');
        } elseif ($request->query('has_per_animal') === '0') {
            $query->doesntHave('inspectionItems');
        }

        if ($request->query('has_cert') === '1') {
            $query->whereHas('batch', fn ($q) => $q->has('certificate'));
        } elseif ($request->query('has_cert') === '0') {
            $query->whereHas('batch', fn ($q) => $q->doesntHave('certificate'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('inspection_date', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('inspection_date', '<=', $request->query('date_to'));
        }

        $inspections = $query
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $batches = Batch::query()
            ->whereIn('id', $batchIds)
            ->whereHas('postMortemInspection')
            ->orderByDesc('created_at')
            ->get(['id', 'batch_code']);

        return view('post-mortem-inspections.index', compact('hubStats', 'inspections', 'batches'));
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

        $batchAnimalsByBatchId = $this->buildBatchAnimalsByBatchId(
            $batches->pluck('id'),
        );

        $oldItemOutcomes = $this->mapOldItemOutcomes(old('item_outcomes'));

        $selectedBatchId = $request->query('batch_id') ?? old('batch_id');
        $selectedBatchId = $selectedBatchId && $batchIds->contains((int) $selectedBatchId)
            ? (int) $selectedBatchId
            : null;
        $selectedBatchData = $selectedBatchId
            ? ($batchAnimalsByBatchId[$selectedBatchId] ?? null)
            : null;

        return view('post-mortem-inspections.create', [
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
            'batchAnimalsByBatchId' => $batchAnimalsByBatchId,
            'selectedBatchId' => $selectedBatchId,
            'selectedBatchData' => $selectedBatchData,
            'defaultTotalExamined' => is_array($selectedBatchData) ? ($selectedBatchData['animal_count'] ?? 0) : 0,
            'existingInspectionOutcomes' => $oldItemOutcomes,
            'preserveExistingOutcomes' => $oldItemOutcomes !== [],
        ]);
    }

    public function store(StorePostMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $itemOutcomes = $validated['item_outcomes'] ?? [];
        $species = (string) ($validated['species'] ?? '');
        unset($validated['observations'], $validated['item_outcomes']);

        $batch = Batch::with('slaughterExecution.slaughterPlan')->findOrFail($validated['batch_id']);
        $perAnimal = $batch->inspectableAnimalsForPostMortem()->isNotEmpty();

        if ($perAnimal) {
            $itemOutcomes = $this->ensureBatchItems($batch, $itemOutcomes);
            $validated['result'] = $this->computeResultFromItems($itemOutcomes);
        } else {
            $validated['result'] = $this->computeResult($species, $observations);
        }

        DB::transaction(function () use ($batch, $validated, $observations, $species, $itemOutcomes, $perAnimal) {
            $inspection = PostMortemInspection::create($validated);
            $this->syncObservations($inspection, $observations, $itemOutcomes, $perAnimal, $species);

            if ($perAnimal) {
                $this->syncInspectionItems($inspection, $batch, $itemOutcomes);
            }
        });

        return redirect()->route('post-mortem-inspections.index')
            ->with('status', __('Post-mortem inspection recorded successfully.'));
    }

    public function show(Request $request, PostMortemInspection $postMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $postMortemInspection->load([
            'batch.slaughterExecution.slaughterPlan.facility',
            'batch.items.intakeItem',
            'inspector',
            'observations',
            'inspectionItems.batchItem.intakeItem',
        ]);

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

        $postMortemInspection->load(['observations', 'inspectionItems', 'batch.items.intakeItem']);

        $batchAnimalsByBatchId = $this->buildBatchAnimalsByBatchId(
            collect([$postMortemInspection->batch_id]),
        );
        $selectedBatchData = $batchAnimalsByBatchId[$postMortemInspection->batch_id] ?? null;

        return view('post-mortem-inspections.edit', [
            'inspection' => $postMortemInspection,
            'batches' => $batches,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
            'batchAnimalsByBatchId' => $batchAnimalsByBatchId,
            'selectedBatchData' => $selectedBatchData,
            'existingInspectionOutcomes' => $this->mapExistingInspectionOutcomes($postMortemInspection),
            'preserveExistingOutcomes' => true,
        ]);
    }

    public function update(UpdatePostMortemInspectionRequest $request, PostMortemInspection $postMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $this->authorizeBatchId($request, (int) $request->validated('batch_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $itemOutcomes = $validated['item_outcomes'] ?? [];
        $species = (string) ($validated['species'] ?? '');
        unset($validated['observations'], $validated['item_outcomes']);

        $batch = Batch::with('slaughterExecution.slaughterPlan')->findOrFail($validated['batch_id']);
        $perAnimal = $batch->inspectableAnimalsForPostMortem()->isNotEmpty();

        if ($perAnimal) {
            $itemOutcomes = $this->ensureBatchItems($batch, $itemOutcomes);
            $validated['result'] = $this->computeResultFromItems($itemOutcomes);
        } else {
            $validated['result'] = $this->computeResult($species, $observations);
        }

        DB::transaction(function () use ($batch, $postMortemInspection, $validated, $observations, $itemOutcomes, $species, $perAnimal) {
            $postMortemInspection->update($validated);
            $this->syncObservations($postMortemInspection, $observations, $itemOutcomes, $perAnimal, $species);

            if ($perAnimal) {
                $this->syncInspectionItems($postMortemInspection, $batch, $itemOutcomes);
            } else {
                $postMortemInspection->inspectionItems()->delete();
            }
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
