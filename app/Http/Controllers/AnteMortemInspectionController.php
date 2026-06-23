<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnteMortemInspectionRequest;
use App\Http\Requests\UpdateAnteMortemInspectionRequest;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnteMortemInspectionController extends Controller
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

    private function authorizeInspection(Request $request, AnteMortemInspection $inspection): void
    {
        if (! $this->userSlaughterPlanIds($request)->contains($inspection->slaughter_plan_id)) {
            abort(404);
        }
    }

    private function authorizePlanId(Request $request, int $planId): void
    {
        if (! $this->userSlaughterPlanIds($request)->contains($planId)) {
            abort(404);
        }
    }

    private function mapObservationPayload(array $observations, ?int $animalIntakeItemId = null): array
    {
        return collect($observations)
            ->map(function ($row, $item) use ($animalIntakeItemId) {
                return [
                    'animal_intake_item_id' => $animalIntakeItemId,
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
     * @param  array<int, array{animal_intake_item_id: int, observations?: array<string, array{value?: string|null, notes?: string|null}>}>  $itemOutcomes
     */
    private function syncObservations(
        AnteMortemInspection $inspection,
        array $legacyObservations,
        array $itemOutcomes,
        bool $perAnimal,
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
                    $this->mapObservationPayload($outcome['observations'] ?? [], $animalId),
                );
            }

            if ($rows !== []) {
                $inspection->observations()->createMany($rows);
            }

            return;
        }

        if ($legacyObservations !== []) {
            $inspection->observations()->createMany(
                $this->mapObservationPayload($legacyObservations),
            );
        }
    }

    private function planHasAssignedAnimals(?SlaughterPlan $plan, string $species): bool
    {
        return $plan !== null
            && $plan->assignedItems()->where('species', $species)->exists();
    }

    /**
     * @return array<int, array{outcome: string, outcome_notes: string, observations: array<string, array{value: string, notes: string|null}>}>
     */
    private function mapExistingInspectionOutcomes(AnteMortemInspection $inspection): array
    {
        $obsByAnimal = $inspection->observations
            ->whereNotNull('animal_intake_item_id')
            ->groupBy('animal_intake_item_id');

        return $inspection->inspectionItems
            ->mapWithKeys(function ($item) use ($obsByAnimal) {
                return [
                    $item->animal_intake_item_id => [
                        'outcome' => $item->outcome,
                        'outcome_notes' => $item->outcome_notes ?? '',
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

    private function checklistConfig(): array
    {
        return AnteMortemChecklist::all();
    }

    /**
     * @param  array<int, array{animal_intake_item_id: int, outcome: string, outcome_notes?: string|null}>  $itemOutcomes
     */
    private function syncInspectionItems(AnteMortemInspection $inspection, array $itemOutcomes): void
    {
        if ($itemOutcomes === []) {
            $inspection->update(['examined_count_source' => AnteMortemInspection::SOURCE_MANUAL]);

            return;
        }

        foreach ($itemOutcomes as $itemOutcome) {
            $inspection->inspectionItems()->create([
                'animal_intake_item_id' => $itemOutcome['animal_intake_item_id'],
                'outcome' => $itemOutcome['outcome'],
                'outcome_notes' => $itemOutcome['outcome_notes'] ?? null,
            ]);
        }

        $inspection->update([
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
            'number_examined' => $inspection->examined_from_items,
            'number_approved' => $inspection->approved_from_items,
            'number_rejected' => $inspection->rejected_from_items,
        ]);
    }

    /**
     * @param  array<int, array{animal_intake_item_id: int, outcome: string}>  $itemOutcomes
     */
    private function applyRejectedHealthStatuses(array $itemOutcomes): void
    {
        $rejectedIds = collect($itemOutcomes)
            ->where('outcome', AnteMortemInspectionItem::OUTCOME_REJECTED)
            ->pluck('animal_intake_item_id');

        if ($rejectedIds->isNotEmpty()) {
            AnimalIntakeItem::whereIn('id', $rejectedIds)
                ->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);
        }
    }

    public function index(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);
        $filters = $this->resolveHubFilters($request);

        $scopeInspections = function ($query) use ($planIds, $filters): void {
            $query->whereIn('slaughter_plan_id', $planIds);
            if ($filters['is_filtered']) {
                $query->whereDate('inspection_date', '>=', $filters['start']->toDateString())
                    ->whereDate('inspection_date', '<=', $filters['end']->toDateString());
            }
        };

        $rejectedCount = $filters['is_filtered']
            ? AnteMortemInspectionItem::query()
                ->whereHas('inspection', $scopeInspections)
                ->rejected()
                ->count()
            : AnteMortemInspectionItem::query()
                ->whereHas('inspection', fn ($query) => $query->whereIn('slaughter_plan_id', $planIds))
                ->rejected()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

        $hubStats = [
            'inspections_label' => $filters['inspections_label'],
            'total_inspections' => AnteMortemInspection::query()
                ->where($scopeInspections)
                ->count(),
            'animals_examined' => (int) AnteMortemInspection::query()
                ->where($scopeInspections)
                ->sum('number_examined'),
            'rejected_label' => $filters['is_filtered'] ? __('Rejected in period') : __('Rejected this week'),
            'rejected_count' => $rejectedCount,
            'plans_without_am' => SlaughterPlan::query()
                ->whereIn('id', $planIds)
                ->whereDoesntHave('anteMortemInspections')
                ->whereNotIn('status', ['executed'])
                ->count(),
            'cattle_count' => $this->speciesExaminedCount($planIds, $filters, SlaughterPlan::SPECIES_CATTLE),
            'goat_count' => $this->speciesExaminedCount($planIds, $filters, SlaughterPlan::SPECIES_GOAT),
            'sheep_count' => $this->speciesExaminedCount($planIds, $filters, SlaughterPlan::SPECIES_SHEEP),
        ];

        $inspections = AnteMortemInspection::query()
            ->with([
                'slaughterPlan.intake',
                'slaughterPlan.facility',
                'inspector',
                'inspectionItems.intakeItem',
            ])
            ->where($scopeInspections)
            ->latest('inspection_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('ante-mortem-inspections.index', compact(
            'hubStats',
            'inspections',
            'filters',
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $planIds
     */
    private function speciesExaminedCount(Collection $planIds, array $filters, string $species): int
    {
        $inspectionScope = function ($query) use ($planIds, $filters): void {
            $query->whereIn('slaughter_plan_id', $planIds);
            if ($filters['is_filtered']) {
                $query->whereDate('inspection_date', '>=', $filters['start']->toDateString())
                    ->whereDate('inspection_date', '<=', $filters['end']->toDateString());
            }
        };

        $fromItems = (int) AnteMortemInspectionItem::query()
            ->whereHas('intakeItem', fn ($query) => $query->where('species', $species))
            ->whereHas('inspection', $inspectionScope)
            ->count();

        $fromLegacy = (int) AnteMortemInspection::query()
            ->whereDoesntHave('inspectionItems')
            ->where($inspectionScope)
            ->where(function ($query) use ($species): void {
                $query->where('species', $species)
                    ->orWhere(function ($query) use ($species): void {
                        $query->whereNull('species')
                            ->whereHas('slaughterPlan', fn ($plan) => $plan->where('species', $species));
                    });
            })
            ->sum('number_examined');

        return $fromItems + $fromLegacy;
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

    public function create(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        // --- Section 4 ---
        $selectedPlanId = $request->query('slaughter_plan_id');
        $selectedPlan = $selectedPlanId
            ? SlaughterPlan::whereIn('id', $this->userSlaughterPlanIds($request))
                ->with('assignedItems')
                ->find($selectedPlanId)
            : null;
        $assignedItems = $selectedPlan?->assignedItems ?? collect();

        $planModels = SlaughterPlan::with(['facility', 'assignedItems', 'intake'])
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get();

        $plans = $this->mapPlansForSelect($planModels);
        $assignedItemsByPlan = $this->mapAssignedItemsByPlan($planModels);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $this->userFacilityIds($request))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        return view('ante-mortem-inspections.create', [
            'plans' => $plans,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
            'selectedPlan' => $selectedPlan,
            'assignedItems' => $assignedItems,
            'assignedItemsByPlan' => $assignedItemsByPlan,
        ]);
    }

    public function store(StoreAnteMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        $itemOutcomes = $validated['item_outcomes'] ?? [];
        unset($validated['observations'], $validated['item_outcomes']);

        $plan = SlaughterPlan::query()->find($validated['slaughter_plan_id']);
        $perAnimal = $this->planHasAssignedAnimals($plan, (string) $validated['species']);

        DB::transaction(function () use ($validated, $observations, $itemOutcomes, $perAnimal) {
            $inspection = AnteMortemInspection::create($validated);
            $this->syncObservations($inspection, $observations, $itemOutcomes, $perAnimal);
            $this->syncInspectionItems($inspection, $itemOutcomes);
            $this->applyRejectedHealthStatuses($itemOutcomes);
        });

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection recorded successfully.'));
    }

    public function show(Request $request, AnteMortemInspection $anteMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);
        // --- Section 4 ---
        $anteMortemInspection->load([
            'slaughterPlan.facility.business',
            'inspector',
            'observations',
            'inspectionItems.intakeItem',
        ]);

        return view('ante-mortem-inspections.show', ['inspection' => $anteMortemInspection]);
    }

    public function edit(Request $request, AnteMortemInspection $anteMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);

        $planIds = $this->userSlaughterPlanIds($request);

        $planModels = SlaughterPlan::with(['facility', 'assignedItems', 'intake'])
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get();

        $plans = $this->mapPlansForSelect($planModels);
        $assignedItemsByPlan = $this->mapAssignedItemsByPlan($planModels);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $this->userFacilityIds($request))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        // --- Section 4 ---
        $anteMortemInspection->load([
            'slaughterPlan.assignedItems',
            'inspectionItems.intakeItem',
            'observations',
        ]);
        $assignedItems = $anteMortemInspection->slaughterPlan?->assignedItems ?? collect();
        $inspectionItems = $anteMortemInspection->inspectionItems;
        $observationsByAnimal = $anteMortemInspection->observations
            ->whereNotNull('animal_intake_item_id')
            ->groupBy('animal_intake_item_id');

        return view('ante-mortem-inspections.edit', [
            'inspection' => $anteMortemInspection,
            'plans' => $plans,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
            'assignedItemsByPlan' => $assignedItemsByPlan,
            'assignedItems' => $assignedItems,
            'inspectionItems' => $inspectionItems,
            'observationsByAnimal' => $observationsByAnimal,
            'existingInspectionOutcomes' => $this->mapExistingInspectionOutcomes($anteMortemInspection),
        ]);
    }

    public function update(UpdateAnteMortemInspectionRequest $request, AnteMortemInspection $anteMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);

        $validated = $request->validated();
        $this->authorizePlanId(
            $request,
            (int) ($validated['slaughter_plan_id'] ?? $anteMortemInspection->slaughter_plan_id),
        );

        DB::transaction(function () use ($anteMortemInspection, $validated) {
            $previouslyRejectedItemIds = $anteMortemInspection->inspectionItems()
                ->rejected()
                ->pluck('animal_intake_item_id')
                ->toArray();

            $anteMortemInspection->update([
                'inspector_id' => $validated['inspector_id'] ?? $anteMortemInspection->inspector_id,
                'species' => $validated['species'],
                'number_examined' => $validated['number_examined'],
                'number_approved' => $validated['number_approved'],
                'number_rejected' => $validated['number_rejected'],
                'notes' => $validated['notes'] ?? null,
                'inspection_date' => $validated['inspection_date'],
                'notes_for_under_observation' => $validated['notes_for_under_observation'] ?? null,
            ]);

            $plan = SlaughterPlan::query()->find($validated['slaughter_plan_id'] ?? $anteMortemInspection->slaughter_plan_id);
            $perAnimal = $this->planHasAssignedAnimals($plan, (string) $validated['species']);
            $itemOutcomes = $validated['item_outcomes'] ?? [];

            $this->syncObservations(
                $anteMortemInspection,
                $validated['observations'] ?? [],
                $itemOutcomes,
                $perAnimal,
            );

            $anteMortemInspection->inspectionItems()->delete();
            $this->syncInspectionItems($anteMortemInspection, $itemOutcomes);

            $newlyRejectedIds = collect($validated['item_outcomes'] ?? [])
                ->where('outcome', AnteMortemInspectionItem::OUTCOME_REJECTED)
                ->pluck('animal_intake_item_id')
                ->toArray();

            $removedRejectionIds = array_diff($previouslyRejectedItemIds, $newlyRejectedIds);

            if (! empty($newlyRejectedIds)) {
                AnimalIntakeItem::whereIn('id', $newlyRejectedIds)
                    ->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);
            }

            if (! empty($removedRejectionIds)) {
                AnimalIntakeItem::whereIn('id', $removedRejectionIds)
                    ->update(['health_status' => AnimalIntakeItem::HEALTH_OBSERVATION]);
            }
        });

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection updated.'));
    }

    public function destroy(Request $request, AnteMortemInspection $anteMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);
        $anteMortemInspection->delete();

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection removed.'));
    }

    /**
     * @param  Collection<int, SlaughterPlan>  $planModels
     * @return Collection<int, array{id: int, label: string, facility_id: int}>
     */
    private function mapPlansForSelect(Collection $planModels): Collection
    {
        return $planModels->map(fn (SlaughterPlan $plan) => [
            'id' => $plan->id,
            'label' => $plan->sessionSelectLabel(),
            'facility_id' => $plan->facility_id,
            'species' => $plan->species,
            'scheduled_count' => (int) $plan->number_of_animals_scheduled,
            'assigned_count' => $plan->assigned_count,
        ])->values();
    }

    /**
     * @param  Collection<int, SlaughterPlan>  $planModels
     * @return Collection<int|string, array<int, array<string, mixed>>>
     */
    private function mapAssignedItemsByPlan(Collection $planModels): Collection
    {
        return $planModels->mapWithKeys(fn (SlaughterPlan $plan) => [
            $plan->id => $plan->assignedItems->map(fn (AnimalIntakeItem $item) => [
                'id' => $item->id,
                'ear_tag' => $item->ear_tag,
                'species' => $item->species,
                'sex' => ucfirst($item->sex),
                'age_months' => $item->age_months,
                'live_weight_kg' => $item->live_weight_kg,
                'health_status' => $item->health_status,
                'health_status_label' => $item->health_status_label,
            ])->values()->toArray(),
        ]);
    }
}
