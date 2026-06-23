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
use Carbon\Carbon;
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
     * @return array<string, int|float|string>
     */
    private function buildBatchHubStats(Collection $executionIds, array $filters): array
    {
        $scopeBatches = function ($query) use ($executionIds, $filters): void {
            $query->whereIn('slaughter_execution_id', $executionIds);
            if ($filters['is_filtered']) {
                $query->whereDate('created_at', '>=', $filters['start']->toDateString())
                    ->whereDate('created_at', '<=', $filters['end']->toDateString());
            }
        };

        return [
            'batches_label' => $filters['batches_label'],
            'total_batches' => Batch::query()
                ->where($scopeBatches)
                ->count(),
            'total_quantity' => (float) Batch::query()
                ->where($scopeBatches)
                ->sum('quantity'),
            'pending_pm' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->whereDoesntHave('postMortemInspection')
                ->where('status', '!=', 'rejected')
                ->count(),
            'ready_for_cert' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->eligibleForCertificate()
                ->count(),
            'cold_chain_issues' => Batch::whereIn('slaughter_execution_id', $executionIds)
                ->whereIn('cold_chain_status', ['at_risk', 'compromised'])
                ->count(),
        ];
    }

    public function hub(Request $request): View
    {
        $executionIds = $this->userExecutionIds($request);
        $filters = $this->resolveHubFilters($request);

        $scopeBatches = function ($query) use ($executionIds, $filters): void {
            $query->whereIn('slaughter_execution_id', $executionIds);
            if ($filters['is_filtered']) {
                $query->whereDate('created_at', '>=', $filters['start']->toDateString())
                    ->whereDate('created_at', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = $this->buildBatchHubStats($executionIds, $filters);

        $batches = Batch::query()
            ->where($scopeBatches)
            ->with([
                'slaughterExecution.slaughterPlan.facility',
                'inspector',
                'postMortemInspection',
                'certificate',
                'items',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('batches.hub', compact(
            'hubStats',
            'batches',
            'filters',
        ));
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('batches.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     batches_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function hubFiltersAllTime(): array
    {
        return [
            'period' => 'all',
            'date_from' => '',
            'date_to' => '',
            'start' => null,
            'end' => null,
            'range_label' => __('All time'),
            'batches_label' => __('Total batches'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, batches_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $batchesLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Batches today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Batches this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Batches this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'batches_label' => $batchesLabel,
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     range_label: string,
     *     batches_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function resolveHubFilters(Request $request): array
    {
        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            return $this->hubFiltersAllTime();
        }

        $period = (string) $request->query('period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $rawFrom = trim((string) $request->query('date_from', ''));
        $rawTo = trim((string) $request->query('date_to', ''));

        if ($period === 'all' && $rawFrom === '' && $rawTo === '') {
            return $this->hubFiltersAllTime();
        }

        if ($rawFrom !== '' && $rawTo !== '') {
            $start = Carbon::parse($rawFrom)->startOfDay();
            $end = Carbon::parse($rawTo)->endOfDay();
            if ($start->gt($end)) {
                $start = Carbon::parse($rawTo)->startOfDay();
                $end = Carbon::parse($rawFrom)->endOfDay();
                [$rawFrom, $rawTo] = [$start->toDateString(), $end->toDateString()];
            }

            return [
                'period' => $period,
                'date_from' => $rawFrom,
                'date_to' => $rawTo,
                'start' => $start,
                'end' => $end,
                'range_label' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
                'batches_label' => __('Batches in range'),
                'has_custom_range' => true,
                'is_filtered' => true,
            ];
        }

        if (in_array($period, ['day', 'month', 'year'], true)) {
            $preset = $this->presetRangeForPeriod($period);

            return [
                'period' => $period,
                'date_from' => $preset['date_from'],
                'date_to' => $preset['date_to'],
                'start' => $preset['start'],
                'end' => $preset['end'],
                'range_label' => $preset['range_label'],
                'batches_label' => $preset['batches_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
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

        return redirect()->route('batches.hub')
            ->with('status', __('Batch updated successfully.'));
    }

    public function destroy(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $batch->delete();

        return redirect()->route('batches.hub')
            ->with('status', __('Batch removed.'));
    }
}
