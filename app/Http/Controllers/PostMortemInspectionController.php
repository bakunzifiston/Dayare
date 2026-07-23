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
use App\Models\WarehouseStorage;
use App\Support\PostMortemChecklist;
use App\Support\PostMortemMeatTotals;
use Carbon\Carbon;
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

    private function authorizeExecutionId(Request $request, int $executionId): void
    {
        if (! $this->userExecutionIds($request)->contains($executionId)) {
            abort(404);
        }
    }

    private function resolveBatchForPostMortem(SlaughterExecution $execution, int $inspectorId, string $species): Batch
    {
        $batch = Batch::query()
            ->where('slaughter_execution_id', $execution->id)
            ->whereHas('postMortemInspection')
            ->first();

        if ($batch !== null) {
            return $batch;
        }

        $batch = Batch::query()
            ->where('slaughter_execution_id', $execution->id)
            ->whereDoesntHave('postMortemInspection')
            ->first();

        if ($batch !== null) {
            if ((int) $batch->inspector_id !== $inspectorId) {
                $batch->update(['inspector_id' => $inspectorId]);
            }

            if (filled($species) && blank($batch->species)) {
                $batch->update(['species' => $species]);
            }

            return $batch;
        }

        return Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $inspectorId,
            'species' => $species,
            'quantity' => 0,
            'quantity_unit' => 'kg',
            'status' => Batch::STATUS_PENDING,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildExecutionAnimalsByExecutionId(Collection $executionIds): array
    {
        return SlaughterExecution::query()
            ->whereIn('id', $executionIds)
            ->with(['slaughterPlan', 'executionItems.intakeItem.intake'])
            ->get()
            ->mapWithKeys(function (SlaughterExecution $execution) {
                $animals = $execution->inspectableAnimalsForPostMortem()->values()->all();
                $inspectedIds = $execution->inspectedAnimalIntakeItemIds();

                return [
                    $execution->id => [
                        'facility_id' => $execution->slaughterPlan->facility_id,
                        'species' => $execution->slaughterPlan->species,
                        'animal_count' => count($animals),
                        'pending_count' => collect($animals)
                            ->reject(fn (array $animal) => $inspectedIds->contains((int) $animal['animal_intake_item_id']))
                            ->count(),
                        'has_per_animal' => count($animals) > 0,
                        'source' => 'execution',
                        'animals' => $animals,
                        'inspected_animal_ids' => $inspectedIds->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    private function checklistConfig(): array
    {
        return PostMortemChecklist::all();
    }

    /**
     * @param  array<string, mixed>|null  $selectedExecutionData
     * @return array{
     *     pmExecutionData: array<string, mixed>,
     *     hasPerAnimal: bool,
     *     executionAnimals: array<int, array<string, mixed>>,
     *     inspectedAnimalIds: array<int, int>,
     *     displayAnimals: array<int, array<string, mixed>>
     * }
     */
    private function postMortemFormContext(?array $selectedExecutionData): array
    {
        $pmExecutionData = is_array($selectedExecutionData) ? $selectedExecutionData : [];

        return [
            'pmExecutionData' => $pmExecutionData,
            'hasPerAnimal' => (bool) ($pmExecutionData['has_per_animal'] ?? false),
            'executionAnimals' => $pmExecutionData['animals'] ?? [],
            'inspectedAnimalIds' => $pmExecutionData['inspected_animal_ids'] ?? [],
            'displayAnimals' => $pmExecutionData['display_animals'] ?? [],
        ];
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
            ? SlaughterExecution::query()->sameDayAndFacilityForPostMortem($reference)->pluck('id')
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
    private function syncInspectionItems(
        PostMortemInspection $inspection,
        Batch $batch,
        array $itemOutcomes,
        bool $mergeExisting = false,
    ): void {
        if ($mergeExisting) {
            $merged = $inspection->inspectionItems()
                ->get()
                ->mapWithKeys(fn (PostMortemInspectionItem $item) => [
                    $item->animal_intake_item_id => [
                        'batch_item_id' => $item->batch_item_id,
                        'animal_intake_item_id' => $item->animal_intake_item_id,
                        'outcome' => $item->outcome,
                        'outcome_notes' => $item->outcome_notes,
                        'seized_part' => $item->seized_part,
                        'reason' => $item->reason,
                        'carcass_weight_kg' => $item->carcass_weight_kg,
                        'condemned_weight_kg' => $item->condemned_weight_kg,
                    ],
                ]);

            foreach ($itemOutcomes as $outcome) {
                $merged->put((int) $outcome['animal_intake_item_id'], $outcome);
            }

            $itemOutcomes = $merged->values()->all();
        }

        if ($itemOutcomes === []) {
            $inspection->update([
                'total_examined' => 0,
                'approved_quantity' => 0,
                'condemned_quantity' => 0,
            ]);

            return;
        }

        $animalsById = $batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');

        if ($mergeExisting) {
            $this->upsertInspectionItems($inspection, $itemOutcomes, $animalsById);
        } else {
            $inspection->inspectionItems()->delete();

            foreach ($itemOutcomes as $outcome) {
                $normalized = $this->normalizeInspectionItemPayload($outcome, $animalsById);
                $inspection->inspectionItems()->create($normalized);
            }
        }

        $inspection->update(PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById));
    }

    /**
     * @param  array<string, mixed>  $outcome
     * @param  Collection<int, array<string, mixed>>  $animalsById
     * @return array<string, mixed>
     */
    private function normalizeInspectionItemPayload(array $outcome, Collection $animalsById): array
    {
        $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
        $carcassRaw = $outcome['carcass_weight_kg'] ?? null;
        $carcassKg = ($carcassRaw !== null && $carcassRaw !== '' && (float) $carcassRaw > 0)
            ? round((float) $carcassRaw, 2)
            : null;

        if (
            $carcassKg === null
            && ($outcome['outcome'] ?? '') === PostMortemInspectionItem::OUTCOME_APPROVED
        ) {
            $beforeKg = (float) ($animalsById->get($animalId)['meat_quantity_kg'] ?? 0);
            $carcassKg = $beforeKg > 0 ? round($beforeKg, 2) : null;
        }

        $condemnedRaw = $outcome['condemned_weight_kg'] ?? null;
        $condemnedKg = ($condemnedRaw !== null && $condemnedRaw !== '' && (float) $condemnedRaw > 0)
            ? round((float) $condemnedRaw, 2)
            : null;

        return [
            'batch_item_id' => $outcome['batch_item_id'] ?? null,
            'animal_intake_item_id' => $animalId,
            'outcome' => $outcome['outcome'],
            'outcome_notes' => $outcome['outcome_notes'] ?? null,
            'seized_part' => $outcome['seized_part'] ?? null,
            'reason' => $outcome['reason'] ?? null,
            'carcass_weight_kg' => $carcassKg,
            'condemned_weight_kg' => $condemnedKg,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemOutcomes
     */
    private function upsertInspectionItems(PostMortemInspection $inspection, array $itemOutcomes, Collection $animalsById): void
    {
        $existing = $inspection->inspectionItems()
            ->get()
            ->keyBy(fn (PostMortemInspectionItem $item) => (int) $item->animal_intake_item_id);

        $submittedAnimalIds = collect($itemOutcomes)
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        foreach ($itemOutcomes as $outcome) {
            $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
            if ($animalId === 0) {
                continue;
            }

            $payload = $this->normalizeInspectionItemPayload($outcome, $animalsById);

            $item = $existing->get($animalId);
            if ($item) {
                $item->update($payload);
            } else {
                $inspection->inspectionItems()->create($payload);
            }
        }

        foreach ($existing as $animalId => $item) {
            if ($submittedAnimalIds->contains($animalId)) {
                continue;
            }

            $hasActiveStorage = $item->warehouseStorages()
                ->blockingRestorage()
                ->exists();

            if ($hasActiveStorage) {
                continue;
            }

            $item->delete();
        }
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
     * @return array<int, array{outcome: string, outcome_notes: string, seized_part: string, reason: string, carcass_weight_kg: string|null, observations: array<string, array{value: string, notes: string|null}>}>
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
                'seized_part' => (string) ($row['seized_part'] ?? ''),
                'reason' => (string) ($row['reason'] ?? ''),
                'carcass_weight_kg' => $row['carcass_weight_kg'] ?? null,
                'condemned_weight_kg' => $row['condemned_weight_kg'] ?? null,
                'observations' => is_array($row['observations'] ?? null) ? $row['observations'] : [],
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{outcome: string, outcome_notes: string, seized_part: string, reason: string, carcass_weight_kg: string|null, observations: array<string, array{value: string, notes: string|null}>}>
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
                        'seized_part' => $item->seized_part ?? '',
                        'reason' => $item->reason ?? '',
                        'carcass_weight_kg' => $item->carcass_weight_kg,
                        'condemned_weight_kg' => $item->condemned_weight_kg,
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

    /**
     * @param  array<int, array<string, mixed>>  $itemOutcomes
     * @return array<int, array<string, mixed>>
     */
    private function mergeSubmittedItemOutcomes(PostMortemInspection $inspection, array $itemOutcomes): array
    {
        $merged = $this->mapExistingInspectionOutcomes($inspection);

        foreach ($itemOutcomes as $outcome) {
            $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
            if ($animalId === 0) {
                continue;
            }

            $merged[$animalId] = array_merge($merged[$animalId] ?? [], $outcome);
        }

        return array_values($merged);
    }

    private function computeResultFromItems(array $itemOutcomes): string
    {
        $hasCondemned = false;
        $hasDeferred = false;

        foreach ($itemOutcomes as $outcome) {
            $decision = (string) ($outcome['outcome'] ?? '');
            if ($decision === PostMortemInspectionItem::OUTCOME_CONDEMNED) {
                $hasCondemned = true;
            }
            if ($decision === PostMortemInspectionItem::OUTCOME_DEFERRED) {
                $hasDeferred = true;
            }
            if (
                $decision === PostMortemInspectionItem::OUTCOME_APPROVED
                && (
                    trim((string) ($outcome['seized_part'] ?? '')) !== ''
                    || (float) ($outcome['condemned_weight_kg'] ?? 0) > 0
                )
            ) {
                $hasCondemned = true;
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
     * @return array<string, int|float|string>
     */
    private function buildPmHubStats(Collection $batchIds, array $filters): array
    {
        $scopeInspections = function ($query) use ($batchIds, $filters): void {
            $query->whereIn('batch_id', $batchIds);
            if ($filters['is_filtered']) {
                $query->whereDate('inspection_date', '>=', $filters['start']->toDateString())
                    ->whereDate('inspection_date', '<=', $filters['end']->toDateString());
            }
        };

        $condemnedCount = $filters['is_filtered']
            ? PostMortemInspectionItem::query()
                ->whereHas('inspection', $scopeInspections)
                ->condemned()
                ->count()
            : PostMortemInspectionItem::query()
                ->whereHas('inspection', fn ($query) => $query->whereIn('batch_id', $batchIds))
                ->condemned()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

        return [
            'inspections_label' => $filters['inspections_label'],
            'total_inspections' => PostMortemInspection::query()
                ->where($scopeInspections)
                ->count(),
            'animals_examined' => PostMortemInspectionItem::query()
                ->whereHas('inspection', $scopeInspections)
                ->count(),
            'cattle_count' => $this->speciesExaminedCount($batchIds, $filters, SlaughterPlan::SPECIES_CATTLE),
            'goat_count' => $this->speciesExaminedCount($batchIds, $filters, SlaughterPlan::SPECIES_GOAT),
            'sheep_count' => $this->speciesExaminedCount($batchIds, $filters, SlaughterPlan::SPECIES_SHEEP),
            'condemned_label' => $filters['is_filtered'] ? __('Condemned in period') : __('Condemned this week'),
            'condemned_count' => $condemnedCount,
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

    /**
     * @param  Collection<int, int|string>  $batchIds
     */
    private function speciesExaminedCount(Collection $batchIds, array $filters, string $species): int
    {
        $inspectionScope = function ($query) use ($batchIds, $filters): void {
            $query->whereIn('batch_id', $batchIds);
            if ($filters['is_filtered']) {
                $query->whereDate('inspection_date', '>=', $filters['start']->toDateString())
                    ->whereDate('inspection_date', '<=', $filters['end']->toDateString());
            }
        };

        $fromItems = (int) PostMortemInspectionItem::query()
            ->whereHas('intakeItem', fn ($query) => $query->where('species', $species))
            ->whereHas('inspection', $inspectionScope)
            ->count();

        $fromLegacy = (int) PostMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where($inspectionScope)
            ->where(function ($query) use ($species): void {
                $query->where('species', $species)
                    ->orWhere(function ($query) use ($species): void {
                        $query->whereNull('species')
                            ->whereHas(
                                'batch.slaughterExecution.slaughterPlan',
                                fn ($plan) => $plan->where('species', $species),
                            );
                    });
            })
            ->sum('total_examined');

        return $fromItems + $fromLegacy;
    }

    public function hub(Request $request): View
    {
        $batchIds = $this->userBatchIds($request);
        $filters = $this->resolveHubFilters($request);

        $scopeInspections = function ($query) use ($batchIds, $filters): void {
            $query->whereIn('batch_id', $batchIds);
            if ($filters['is_filtered']) {
                $query->whereDate('inspection_date', '>=', $filters['start']->toDateString())
                    ->whereDate('inspection_date', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = $this->buildPmHubStats($batchIds, $filters);

        $inspections = PostMortemInspection::query()
            ->with([
                'batch.slaughterExecution.slaughterPlan.facility',
                'batch.certificate',
                'inspector',
                'inspectionItems.intakeItem',
                'inspectionItems.batchItem',
            ])
            ->where($scopeInspections)
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $releaseLookup = \App\Support\BatchAnimalReleaseLookup::forBatches(
            $inspections->getCollection()->pluck('batch_id'),
        );

        return view('post-mortem-inspections.hub', compact(
            'hubStats',
            'inspections',
            'filters',
            'releaseLookup',
        ));
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('post-mortem-inspections.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     inspections_label: string,
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
            'inspections_label' => __('Total inspections'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, inspections_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $inspectionsLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Inspections today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Inspections this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Inspections this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'inspections_label' => $inspectionsLabel,
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
     *     inspections_label: string,
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
                'inspections_label' => __('Inspections in range'),
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
                'inspections_label' => $preset['inspections_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    public function create(Request $request): View|RedirectResponse
    {
        $executionIds = $this->userExecutionIds($request);
        $facilityIds = $this->userFacilityIds($request);

        $executions = SlaughterExecution::with('slaughterPlan.facility')
            ->whereIn('id', $executionIds)
            ->whereIn('status', [
                SlaughterExecution::STATUS_COMPLETED,
                SlaughterExecution::STATUS_IN_PROGRESS,
            ])
            ->whereHas('executionItems')
            ->latest('slaughter_time')
            ->get()
            ->filter(fn (SlaughterExecution $execution) => ! $execution->isPostMortemComplete());

        $executionAnimalsByExecutionId = $this->buildExecutionAnimalsByExecutionId(
            $executions->pluck('id'),
        );

        $executions = $executions
            ->filter(function (SlaughterExecution $execution) use ($executionAnimalsByExecutionId) {
                $pending = (int) ($executionAnimalsByExecutionId[$execution->id]['pending_count'] ?? 0);

                return $pending > 0;
            })
            ->map(function (SlaughterExecution $execution) use ($executionAnimalsByExecutionId) {
                $animalData = $executionAnimalsByExecutionId[$execution->id] ?? null;
                $pending = (int) ($animalData['pending_count'] ?? 0);
                $total = (int) ($animalData['animal_count'] ?? 0);
                $statusLabel = $execution->status === SlaughterExecution::STATUS_IN_PROGRESS
                    ? __('in progress')
                    : __('completed');

                $label = $execution->slaughter_time->format('d M Y H:i')
                    .' — '.$execution->slaughterPlan->facility->facility_name
                    .' ('.$execution->slaughterPlan->species.')'
                    .' · '.$statusLabel;

                if ($total > 0) {
                    $label .= ' · '.__(':pending/:total pending PM', [
                        'pending' => $pending,
                        'total' => $total,
                    ]);
                }

                return [
                    'id' => $execution->id,
                    'label' => $label,
                    'facility_id' => $execution->slaughterPlan->facility_id,
                    'species' => $execution->slaughterPlan->species,
                ];
            })
            ->values();

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $oldItemOutcomes = $this->mapOldItemOutcomes(old('item_outcomes'));

        $selectedExecutionId = $request->query('slaughter_execution_id') ?? old('slaughter_execution_id');
        if (! $selectedExecutionId && $executions->count() === 1) {
            $selectedExecutionId = $executions->first()['id'];
        }
        $selectedExecutionId = $selectedExecutionId && $executionIds->contains((int) $selectedExecutionId)
            ? (int) $selectedExecutionId
            : null;

        if ($selectedExecutionId) {
            $existingBatch = Batch::query()
                ->where('slaughter_execution_id', $selectedExecutionId)
                ->whereHas('postMortemInspection')
                ->with('postMortemInspection')
                ->first();

            if ($existingBatch?->postMortemInspection) {
                return redirect()->route('post-mortem-inspections.edit', $existingBatch->postMortemInspection)
                    ->with('status', __('Continue adding animals to this post-mortem inspection.'));
            }
        }

        $selectedExecutionData = $selectedExecutionId
            ? ($executionAnimalsByExecutionId[$selectedExecutionId] ?? null)
            : null;

        $selectedAnimals = [];
        if ($selectedExecutionId && is_array($executionAnimalsByExecutionId[$selectedExecutionId] ?? null)) {
            $executionData = $executionAnimalsByExecutionId[$selectedExecutionId];
            $animalPool = collect($executionData['animals']);
            $inspectedIds = collect($executionData['inspected_animal_ids'] ?? []);

            $oldOutcomes = old('item_outcomes');
            if (is_array($oldOutcomes) && $oldOutcomes !== []) {
                $selectedAnimals = collect($oldOutcomes)
                    ->pluck('animal_intake_item_id')
                    ->map(fn ($id) => $animalPool->firstWhere('animal_intake_item_id', (int) $id))
                    ->filter()
                    ->values()
                    ->all();
            } else {
                $selectedAnimals = $animalPool
                    ->reject(fn (array $animal) => $inspectedIds->contains((int) $animal['animal_intake_item_id']))
                    ->values()
                    ->all();
            }
        }

        if (is_array($selectedExecutionData)) {
            $selectedExecutionData['display_animals'] = $selectedAnimals;
        }

        return view('post-mortem-inspections.create', array_merge(
            $this->postMortemFormContext(is_array($selectedExecutionData) ? $selectedExecutionData : null),
            [
                'executions' => $executions,
                'inspectorsByFacility' => $inspectorsByFacility,
                'checklists' => $this->checklistConfig(),
                'executionAnimalsByExecutionId' => $executionAnimalsByExecutionId,
                'selectedExecutionId' => $selectedExecutionId,
                'selectedExecutionData' => $selectedExecutionData,
                'defaultTotalExamined' => 0,
                'existingInspectionOutcomes' => $oldItemOutcomes,
                'preserveExistingOutcomes' => $oldItemOutcomes !== [],
                'selectedAnimals' => $selectedAnimals,
            ],
        ));
    }

    public function store(StorePostMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizeExecutionId($request, (int) $request->validated('slaughter_execution_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $itemOutcomes = $validated['item_outcomes'] ?? [];
        $species = (string) ($validated['species'] ?? '');
        unset($validated['observations'], $validated['item_outcomes'], $validated['slaughter_execution_id']);

        $execution = SlaughterExecution::with('slaughterPlan')->findOrFail((int) $request->validated('slaughter_execution_id'));

        $existingInspection = PostMortemInspection::query()
            ->whereHas('batch', fn ($query) => $query->where('slaughter_execution_id', $execution->id))
            ->first();

        if ($existingInspection !== null) {
            return redirect()->route('post-mortem-inspections.edit', $existingInspection)
                ->with('status', __('A post-mortem inspection already exists for this slaughter execution. Add more animals there.'));
        }

        $batch = $this->resolveBatchForPostMortem($execution, (int) $validated['inspector_id'], $species);
        $validated['batch_id'] = $batch->id;
        $perAnimal = $execution->inspectableAnimalsForPostMortem()->isNotEmpty();

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

        return redirect()->route('post-mortem-inspections.hub')
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

        $itemOutcomes = $postMortemInspection->inspectionItems->map(fn (PostMortemInspectionItem $item) => [
            'animal_intake_item_id' => $item->animal_intake_item_id,
            'outcome' => $item->outcome,
            'carcass_weight_kg' => $item->carcass_weight_kg,
            'condemned_weight_kg' => $item->condemned_weight_kg,
        ])->all();
        $animalsById = $postMortemInspection->batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');
        $meatTotals = PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById);

        return view('post-mortem-inspections.show', [
            'inspection' => $postMortemInspection,
            'meatTotals' => $meatTotals,
        ]);
    }

    public function edit(Request $request, PostMortemInspection $postMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);

        $facilityIds = $this->userFacilityIds($request);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $postMortemInspection->load(['observations', 'inspectionItems', 'batch.slaughterExecution.slaughterPlan.facility']);

        $executionId = (int) $postMortemInspection->batch->slaughter_execution_id;
        $executionAnimalsByExecutionId = $this->buildExecutionAnimalsByExecutionId(collect([$executionId]));
        $selectedExecutionData = $executionAnimalsByExecutionId[$executionId] ?? null;

        if (is_array($selectedExecutionData)) {
            $animalPool = collect($selectedExecutionData['animals'] ?? []);
            $inspectedGlobally = collect($selectedExecutionData['inspected_animal_ids'] ?? []);
            $inThisInspection = $postMortemInspection->inspectionItems
                ->pluck('animal_intake_item_id')
                ->map(fn ($id) => (int) $id);

            $selectedExecutionData['display_animals'] = $animalPool
                ->filter(fn (array $animal) => $inThisInspection->contains((int) $animal['animal_intake_item_id'])
                    || ! $inspectedGlobally->contains((int) $animal['animal_intake_item_id']))
                ->values()
                ->all();
            $selectedExecutionData['has_per_animal'] = count($selectedExecutionData['display_animals']) > 0;
        }

        $itemOutcomes = $postMortemInspection->inspectionItems->map(fn (PostMortemInspectionItem $item) => [
            'animal_intake_item_id' => $item->animal_intake_item_id,
            'outcome' => $item->outcome,
            'carcass_weight_kg' => $item->carcass_weight_kg,
            'condemned_weight_kg' => $item->condemned_weight_kg,
        ])->all();
        $animalsById = $postMortemInspection->batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');
        $meatTotals = PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById);

        return view('post-mortem-inspections.edit', array_merge(
            $this->postMortemFormContext(is_array($selectedExecutionData) ? $selectedExecutionData : null),
            [
                'inspection' => $postMortemInspection,
                'executions' => collect([[
                    'id' => $executionId,
                    'label' => $postMortemInspection->batch->slaughterExecution->slaughter_time->format('d M Y H:i')
                        .' — '.$postMortemInspection->batch->slaughterExecution->slaughterPlan->facility->facility_name,
                    'facility_id' => $postMortemInspection->batch->slaughterExecution->slaughterPlan->facility_id,
                    'species' => $postMortemInspection->batch->species,
                ]]),
                'inspectorsByFacility' => $inspectorsByFacility,
                'checklists' => $this->checklistConfig(),
                'executionAnimalsByExecutionId' => $executionAnimalsByExecutionId,
                'selectedExecutionId' => $executionId,
                'selectedExecutionData' => $selectedExecutionData,
                'existingInspectionOutcomes' => $this->mapExistingInspectionOutcomes($postMortemInspection),
                'preserveExistingOutcomes' => true,
                'meatTotals' => $meatTotals,
            ],
        ));
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
            $mergedOutcomes = $this->mergeSubmittedItemOutcomes($postMortemInspection, $itemOutcomes);
            $validated['result'] = $this->computeResultFromItems($mergedOutcomes);
        } else {
            $validated['result'] = $this->computeResult($species, $observations);
            $mergedOutcomes = $itemOutcomes;
        }

        DB::transaction(function () use ($batch, $postMortemInspection, $validated, $observations, $itemOutcomes, $mergedOutcomes, $species, $perAnimal) {
            $postMortemInspection->update($validated);
            $this->syncObservations($postMortemInspection, $observations, $mergedOutcomes, $perAnimal, $species);

            if ($perAnimal) {
                $this->syncInspectionItems($postMortemInspection, $batch, $itemOutcomes, mergeExisting: true);
            } else {
                $postMortemInspection->inspectionItems()->delete();
            }
        });

        return redirect()->route('post-mortem-inspections.hub')
            ->with('status', __('Post-mortem inspection updated successfully.'));
    }

    public function destroy(Request $request, PostMortemInspection $postMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $postMortemInspection);
        $postMortemInspection->delete();

        return redirect()->route('post-mortem-inspections.hub')
            ->with('status', __('Post-mortem inspection removed.'));
    }
}
