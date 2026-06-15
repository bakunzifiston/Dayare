<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BatchController extends Controller
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

    /**
     * @param  Collection<int, int|string>  $executionIds
     * @return array<string, int|float>
     */
    // --- Section 3 ---
    private function buildBatchHubStats(Collection $executionIds): array
    {
        return [
            'total_batches' => Batch::whereIn('slaughter_execution_id', $executionIds)->count(),
            'pending_pm' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->whereDoesntHave('postMortemInspection')
                ->where('status', '!=', 'rejected')->count(),
            'ready_for_cert' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
                ->whereDoesntHave('certificate')->count(),
            'cold_chain_issues' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->whereIn('cold_chain_status', ['at_risk', 'compromised'])->count(),
            'total_quantity' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->sum('quantity'),
        ];
    }

    /** Batches module home: workflow summary and primary “create batch” action. */
    public function hub(Request $request): View
    {
        // --- Section 3 ---
        $executionIds = $this->userExecutionIds($request);

        $hubStats = $this->buildBatchHubStats($executionIds);

        $byStatus = Batch::whereIn('slaughter_execution_id', $executionIds)
            ->with(['slaughterExecution.slaughterPlan.facility', 'postMortemInspection', 'certificate', 'items'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('status');

        $recentBatches = Batch::whereIn('slaughter_execution_id', $executionIds)
            ->with(['slaughterExecution.slaughterPlan.facility', 'postMortemInspection', 'certificate', 'items'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('batches.hub', compact(
            'hubStats',
            'byStatus',
            'recentBatches',
        ));
    }

    public function index(Request $request): View
    {
        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        // --- Section 3 ---
        $hubStats = $this->buildBatchHubStats($executionIds);

        $query = Batch::query()
            ->with(['slaughterExecution.slaughterPlan.facility', 'inspector', 'postMortemInspection', 'certificate', 'items'])
            ->whereIn('slaughter_execution_id', $executionIds);

        if ($request->filled('facility_id')) {
            $facilityId = (int) $request->query('facility_id');
            if ($facilityIds->contains($facilityId)) {
                $query->whereHas('slaughterExecution.slaughterPlan', fn ($q) => $q->where('facility_id', $facilityId));
            }
        }

        if ($request->filled('status')) {
            $status = (string) $request->query('status');
            if (in_array($status, Batch::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('cold_chain_status')) {
            $coldChain = (string) $request->query('cold_chain_status');
            if (in_array($coldChain, Batch::COLD_CHAIN_STATUSES, true)) {
                $query->where('cold_chain_status', $coldChain);
            }
        }

        if ($request->query('has_pm') === '1') {
            $query->has('postMortemInspection');
        } elseif ($request->query('has_pm') === '0') {
            $query->doesntHave('postMortemInspection');
        }

        if ($request->query('has_cert') === '1') {
            $query->has('certificate');
        } elseif ($request->query('has_cert') === '0') {
            $query->doesntHave('certificate');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $batches = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $facilities = Facility::query()
            ->whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name']);

        return view('batches.index', compact('hubStats', 'batches', 'facilities'));
    }

    public function create(Request $request): View
    {
        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $executionModels = SlaughterExecution::with(['executionItems.intakeItem', 'slaughterPlan.facility'])
            ->whereIn('id', $executionIds)
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->latest('slaughter_time')
            ->get();

        $alreadyBatchedAnimalIds = BatchItem::query()
            ->whereHas('batch', fn ($q) => $q->whereIn('slaughter_execution_id', $executionIds))
            ->pluck('animal_intake_item_id')
            ->unique()
            ->values()
            ->all();

        $sameDayBatchData = $this->buildSameDayBatchData($executionModels, $alreadyBatchedAnimalIds);

        $facilities = Facility::query()
            ->whereIn('id', $facilityIds)
            ->whereIn('id', $executionModels->pluck('slaughterPlan.facility_id')->unique())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name']);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $units = $request->user()->configuredUnitsForBusinessIds()
            ->map(fn ($unit) => ['code' => $unit->code, 'name' => $unit->name])
            ->values();

        $selectedExecutionId = $request->query('slaughter_execution_id');
        $selectedExecutionId = $selectedExecutionId && $executionIds->contains((int) $selectedExecutionId)
            ? (int) $selectedExecutionId
            : null;

        $selectedExecution = $selectedExecutionId
            ? $executionModels->firstWhere('id', $selectedExecutionId)
            : null;

        $selectedFacilityId = old('facility_id', $selectedExecution?->slaughterPlan->facility_id);
        $selectedSlaughterDate = old(
            'slaughter_date',
            $selectedExecution?->slaughter_time->toDateString() ?? now()->toDateString(),
        );

        $dayKey = $selectedFacilityId && $selectedSlaughterDate
            ? ((int) $selectedFacilityId).'|'.$selectedSlaughterDate
            : null;
        $selectedDayData = $dayKey ? ($sameDayBatchData[$dayKey] ?? null) : null;

        return view('batches.create', [
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
            'units' => $units,
            'selectedExecutionId' => $selectedExecutionId,
            'selectedFacilityId' => $selectedFacilityId,
            'selectedSlaughterDate' => $selectedSlaughterDate,
            'selectedDayData' => $selectedDayData,
            'sameDayBatchData' => $sameDayBatchData,
            'alreadyBatchedAnimalIds' => $alreadyBatchedAnimalIds,
        ]);
    }

    /**
     * @param  Collection<int, SlaughterExecution>  $executionModels
     * @param  array<int, int>  $alreadyBatchedAnimalIds
     * @return array<string, array<string, mixed>>
     */
    private function buildSameDayBatchData(Collection $executionModels, array $alreadyBatchedAnimalIds): array
    {
        $alreadyBatchedLookup = array_fill_keys($alreadyBatchedAnimalIds, true);
        $grouped = [];

        foreach ($executionModels as $execution) {
            $facilityId = (int) $execution->slaughterPlan->facility_id;
            $date = $execution->slaughter_time->toDateString();
            $key = $facilityId.'|'.$date;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'facility_id' => $facilityId,
                    'facility_name' => $execution->slaughterPlan->facility->facility_name,
                    'slaughter_date' => $date,
                    'primary_execution_id' => $execution->id,
                    'species' => $execution->slaughterPlan->species,
                    'execution_count' => 0,
                    'executions' => [],
                    'items' => [],
                    'available_meat_kg' => 0.0,
                    'available_animal_count' => 0,
                ];
            }

            $grouped[$key]['execution_count']++;
            $grouped[$key]['executions'][] = [
                'id' => $execution->id,
                'label' => $execution->slaughter_time->format('H:i').' — '.__(':count animals', [
                    'count' => $execution->actual_animals_slaughtered,
                ]),
            ];

            foreach ($execution->executionItems as $executionItem) {
                $animalId = (int) $executionItem->animal_intake_item_id;
                $alreadyBatched = isset($alreadyBatchedLookup[$animalId]);

                $grouped[$key]['items'][] = [
                    'execution_id' => $execution->id,
                    'execution_item_id' => $executionItem->id,
                    'animal_id' => $animalId,
                    'ear_tag' => $executionItem->intakeItem->ear_tag,
                    'species' => $executionItem->intakeItem->species,
                    'sex' => ucfirst($executionItem->intakeItem->sex),
                    'live_weight_kg' => $executionItem->intakeItem->live_weight_kg,
                    'meat_quantity_kg' => $executionItem->meat_quantity_kg,
                    'session_label' => $execution->slaughter_time->format('H:i'),
                    'already_batched' => $alreadyBatched,
                ];

                if (! $alreadyBatched) {
                    $grouped[$key]['available_meat_kg'] += (float) $executionItem->meat_quantity_kg;
                    $grouped[$key]['available_animal_count']++;
                }
            }
        }

        return $grouped;
    }

    /**
     * @return Collection<int, SlaughterExecution>
     */
    private function sameDayExecutionsFor(SlaughterExecution $reference, Collection $scopedExecutionIds): Collection
    {
        return SlaughterExecution::query()
            ->whereIn('id', $scopedExecutionIds)
            ->sameDayAndFacility($reference)
            ->orderBy('slaughter_time')
            ->get();
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        $this->authorizeExecutionId($request, (int) $request->validated('slaughter_execution_id'));

        $validated = $request->validated();
        $selectedAnimalIds = $validated['selected_animal_ids'] ?? null;
        unset($validated['selected_animal_ids'], $validated['item_quantities']);

        $scopedExecutionIds = $this->userExecutionIds($request);

        DB::transaction(function () use ($validated, $selectedAnimalIds, $request, $scopedExecutionIds): void {
            $batch = Batch::create($validated);

            if (empty($selectedAnimalIds)) {
                return;
            }

            $referenceExecution = SlaughterExecution::query()
                ->with('slaughterPlan')
                ->findOrFail($batch->slaughter_execution_id);

            $sameDayExecutionIds = $this->sameDayExecutionsFor(
                $referenceExecution,
                $scopedExecutionIds,
            )->pluck('id');

            $executionItems = SlaughterExecutionItem::query()
                ->whereIn('slaughter_execution_id', $sameDayExecutionIds)
                ->whereIn('animal_intake_item_id', $selectedAnimalIds)
                ->get();

            $quantityOverrides = collect($request->validated('item_quantities') ?? [])
                ->keyBy('slaughter_execution_item_id');

            foreach ($executionItems as $execItem) {
                $override = $quantityOverrides->get($execItem->id);
                $batch->items()->create([
                    'slaughter_execution_item_id' => $execItem->id,
                    'animal_intake_item_id' => $execItem->animal_intake_item_id,
                    'meat_quantity_kg' => $override['meat_quantity_kg'] ?? $execItem->meat_quantity_kg,
                    'notes' => $override['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('batches.hub')
            ->with('status', __('Batch created successfully.'));
    }

    public function show(Request $request, Batch $batch): View|RedirectResponse
    {
        $this->authorizeBatch($request, $batch);

        // --- Section 3 ---
        $batch->load([
            'items.intakeItem',
            'items.executionItem',
            'items.postMortemOutcome',
            'slaughterExecution.slaughterPlan.facility',
            'postMortemInspection',
            'certificate',
            'warehouseStorage.coldRoom',
            'transportTrips',
            'inspector',
        ]);

        return view('batches.show', ['batch' => $batch]);
    }

    public function edit(Request $request, Batch $batch): View|RedirectResponse
    {
        $this->authorizeBatch($request, $batch);

        // --- Section 3 ---
        $batch->load(['items.intakeItem', 'items.executionItem']);

        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $executions = SlaughterExecution::with('slaughterPlan.facility')
            ->whereIn('id', $executionIds)
            ->latest('slaughter_time')
            ->get()
            ->map(fn (SlaughterExecution $e) => [
                'id' => $e->id,
                'label' => $e->slaughter_time->format('d M Y H:i').' — '.$e->slaughterPlan->facility->facility_name.' — '.$e->slaughterPlan->species,
                'facility_id' => $e->slaughterPlan->facility_id,
                'species' => $e->slaughterPlan->species,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $units = $request->user()->configuredUnitsForBusinessIds()
            ->map(fn ($unit) => ['code' => $unit->code, 'name' => $unit->name])
            ->values();

        return view('batches.edit', [
            'batch' => $batch,
            'executions' => $executions,
            'inspectorsByFacility' => $inspectorsByFacility,
            'units' => $units,
        ]);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $this->authorizeExecutionId($request, (int) $request->validated('slaughter_execution_id'));

        $validated = $request->validated();
        unset($validated['selected_animal_ids'], $validated['item_quantities']);

        DB::transaction(function () use ($batch, $validated, $request): void {
            $batch->update($validated);

            // --- Section 3 ---
            if (! empty($request->validated('item_quantities'))) {
                foreach ($request->validated('item_quantities') as $override) {
                    $batch->items()
                        ->where('slaughter_execution_item_id', $override['slaughter_execution_item_id'])
                        ->update([
                            'meat_quantity_kg' => $override['meat_quantity_kg'],
                            'notes' => $override['notes'] ?? null,
                        ]);
                }
            }
        });

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
