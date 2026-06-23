<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Models\AnteMortemInspectionItem;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * @return array{
     *     plans: Collection<int, array{id: int, label: string}>,
     *     approvedItemsByPlan: Collection<int, array<int, array<string, mixed>>>,
     *     slaughteredItemIdsByPlan: Collection<int, list<int>>,
     *     amDateByPlan: Collection<int, string|null>
     * }
     */
    // --- Section 3 ---
    private function buildPlanSelectData(Collection $planIds, bool $includeScheduledCount = true): array
    {
        $planModels = SlaughterPlan::query()
            ->with([
                'facility',
                'anteMortemInspections' => fn ($query) => $query->latest('inspection_date')->limit(1),
            ])
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get();

        $planIdList = $planModels->pluck('id')->all();

        $approvedItemsByPlanRaw = AnteMortemInspectionItem::query()
            ->whereHas('inspection', fn ($query) => $query->whereIn('slaughter_plan_id', $planIdList))
            ->approved()
            ->with(['intakeItem', 'inspection'])
            ->get()
            ->groupBy(fn (AnteMortemInspectionItem $item) => $item->inspection->slaughter_plan_id);

        $approvedItemsByPlan = $planModels->mapWithKeys(fn (SlaughterPlan $plan) => [
            $plan->id => ($approvedItemsByPlanRaw->get($plan->id) ?? collect())
                ->map(fn (AnteMortemInspectionItem $ai) => [
                    'id' => $ai->intakeItem->id,
                    'ear_tag' => $ai->intakeItem->ear_tag,
                    'species' => $ai->intakeItem->species,
                    'sex' => ucfirst($ai->intakeItem->sex),
                    'live_weight_kg' => $ai->intakeItem->live_weight_kg,
                    'am_outcome' => $ai->outcome,
                ])->values()->toArray(),
        ]);

        $amDateByPlan = $planModels->mapWithKeys(fn (SlaughterPlan $plan) => [
            $plan->id => $plan->anteMortemInspections->last()?->inspection_date?->toDateString(),
        ]);

        $slaughteredDetailsByPlanRaw = SlaughterExecutionItem::query()
            ->whereHas('execution', fn ($query) => $query->whereIn('slaughter_plan_id', $planIdList))
            ->with([
                'intakeItem:id,ear_tag,species,sex',
                'execution:id,slaughter_plan_id,slaughter_time',
            ])
            ->get()
            ->groupBy(fn (SlaughterExecutionItem $item) => $item->execution->slaughter_plan_id);

        $slaughteredDetailsByPlan = $planModels->mapWithKeys(function (SlaughterPlan $plan) use ($slaughteredDetailsByPlanRaw) {
            $rows = ($slaughteredDetailsByPlanRaw->get($plan->id) ?? collect())
                ->map(fn (SlaughterExecutionItem $item) => [
                    'animal_intake_item_id' => (int) $item->animal_intake_item_id,
                    'ear_tag' => $item->intakeItem->ear_tag,
                    'species' => $item->intakeItem->species,
                    'sex' => ucfirst($item->intakeItem->sex),
                    'meat_quantity_kg' => $item->meat_quantity_kg,
                    'slaughter_time' => $item->execution->slaughter_time?->format('d M Y H:i'),
                ])
                ->unique('animal_intake_item_id')
                ->values()
                ->all();

            return [$plan->id => $rows];
        });

        $slaughteredItemIdsByPlan = $slaughteredDetailsByPlan->map(
            fn (array $rows) => collect($rows)->pluck('animal_intake_item_id')->all(),
        );

        $plans = $planModels->map(function (SlaughterPlan $plan) use (
            $includeScheduledCount,
            $approvedItemsByPlan,
            $slaughteredItemIdsByPlan,
        ) {
            $approvedCount = count($approvedItemsByPlan->get($plan->id) ?? []);
            $slaughteredCount = count($slaughteredItemIdsByPlan->get($plan->id) ?? []);
            $remainingCount = max(0, $approvedCount - $slaughteredCount);

            $label = $plan->slaughter_date->format('d M Y')
                .' — '.$plan->facility->facility_name
                .' ('.$plan->species
                .($includeScheduledCount ? ', '.$plan->number_of_animals_scheduled.' scheduled' : '')
                .')';

            if ($approvedCount > 0) {
                $label .= ' — '.__(':slaughtered/:approved slaughtered, :remaining remaining', [
                    'slaughtered' => $slaughteredCount,
                    'approved' => $approvedCount,
                    'remaining' => $remainingCount,
                ]);
            }

            return [
                'id' => $plan->id,
                'label' => $label,
            ];
        });

        return [
            'plans' => $plans,
            'approvedItemsByPlan' => $approvedItemsByPlan,
            'slaughteredItemIdsByPlan' => $slaughteredItemIdsByPlan,
            'slaughteredDetailsByPlan' => $slaughteredDetailsByPlan,
            'amDateByPlan' => $amDateByPlan,
        ];
    }

    public function hub(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);
        $filters = $this->resolveHubFilters($request);

        $scopeExecutions = function ($query) use ($planIds, $filters): void {
            $query->whereIn('slaughter_plan_id', $planIds);
            if ($filters['is_filtered']) {
                $query->whereDate('slaughter_time', '>=', $filters['start']->toDateString())
                    ->whereDate('slaughter_time', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = [
            'executions_label' => $filters['executions_label'],
            'total_executions' => SlaughterExecution::query()
                ->where($scopeExecutions)
                ->count(),
            'total_slaughtered' => (int) SlaughterExecution::query()
                ->where($scopeExecutions)
                ->sum('actual_animals_slaughtered'),
            'cattle_kg' => $this->speciesMeatQuantityKg($scopeExecutions, SlaughterPlan::SPECIES_CATTLE),
            'goat_kg' => $this->speciesMeatQuantityKg($scopeExecutions, SlaughterPlan::SPECIES_GOAT),
            'sheep_kg' => $this->speciesMeatQuantityKg($scopeExecutions, SlaughterPlan::SPECIES_SHEEP),
            'total_meat_kg' => (float) SlaughterExecutionItem::query()
                ->whereHas('execution', $scopeExecutions)
                ->sum('meat_quantity_kg'),
            'executions_today' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->whereDate('slaughter_time', today())
                ->count(),
            'plans_without_execution' => SlaughterPlan::query()
                ->whereIn('id', $planIds)
                ->whereDoesntHave('slaughterExecutions')
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'pending_batches' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->where('status', SlaughterExecution::STATUS_COMPLETED)
                ->whereDoesntHave('batches')
                ->count(),
        ];

        $executions = SlaughterExecution::query()
            ->where($scopeExecutions)
            ->with(['slaughterPlan.facility', 'slaughterPlan.intake', 'executionItems', 'batches'])
            ->orderByDesc('slaughter_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('slaughter-executions.hub', compact(
            'hubStats',
            'executions',
            'filters',
        ));
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $scopeExecutions
     */
    private function speciesMeatQuantityKg(callable $scopeExecutions, string $species): float
    {
        return (float) SlaughterExecutionItem::query()
            ->whereHas('intakeItem', fn ($query) => $query->where('species', $species))
            ->whereHas('execution', $scopeExecutions)
            ->sum('meat_quantity_kg');
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('slaughter-executions.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     executions_label: string,
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
            'executions_label' => __('Total executions'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, executions_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $executionsLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Executions today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Executions this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Executions this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'executions_label' => $executionsLabel,
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
     *     executions_label: string,
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
                'executions_label' => __('Executions in range'),
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
                'executions_label' => $preset['executions_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    public function create(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        $planData = $this->buildPlanSelectData($planIds, includeScheduledCount: true);
        $plans = $planData['plans'];
        $approvedItemsByPlan = $planData['approvedItemsByPlan'];
        $slaughteredItemIdsByPlan = $planData['slaughteredItemIdsByPlan'];
        $slaughteredDetailsByPlan = $planData['slaughteredDetailsByPlan'];
        $amDateByPlan = $planData['amDateByPlan'];

        // --- Section 3 ---
        $selectedPlanId = $request->query('slaughter_plan_id');
        $selectedPlan = $selectedPlanId
            ? SlaughterPlan::query()
                ->whereIn('id', $this->userSlaughterPlanIds($request))
                ->with([
                    'assignedItems',
                    'anteMortemInspections' => fn ($query) => $query->latest('inspection_date')->limit(1),
                ])
                ->find($selectedPlanId)
            : null;

        $approvedItems = $selectedPlan
            ? AnteMortemInspectionItem::query()
                ->whereHas('inspection', fn ($query) => $query->where('slaughter_plan_id', $selectedPlan->id))
                ->approved()
                ->with('intakeItem')
                ->get()
            : collect();

        $slaughteredItemIds = $selectedPlan
            ? ($slaughteredItemIdsByPlan->get($selectedPlan->id) ?? [])
            : [];
        $slaughteredDetails = $selectedPlan
            ? ($slaughteredDetailsByPlan->get($selectedPlan->id) ?? [])
            : [];

        return view('slaughter-executions.create', compact(
            'plans',
            'selectedPlan',
            'approvedItems',
            'approvedItemsByPlan',
            'slaughteredItemIdsByPlan',
            'slaughteredDetailsByPlan',
            'slaughteredItemIds',
            'slaughteredDetails',
            'amDateByPlan',
        ));
    }

    public function store(StoreSlaughterExecutionRequest $request): RedirectResponse
    {
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        $validated = $request->validated();
        $itemSlaughters = $validated['item_slaughters'] ?? [];
        unset($validated['item_slaughters']);

        DB::transaction(function () use ($validated, $itemSlaughters): void {
            $execution = SlaughterExecution::create($validated);

            // --- Section 3 --- Per-animal slaughter items (optional)
            if ($itemSlaughters !== []) {
                foreach ($itemSlaughters as $item) {
                    $execution->executionItems()->create([
                        'animal_intake_item_id' => $item['animal_intake_item_id'],
                        'meat_quantity_kg' => $item['meat_quantity_kg'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                $execution->update([
                    'actual_animals_slaughtered' => $execution->slaughtered_count_from_items,
                    'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
                ]);
            }
        });

        return redirect()->route('slaughter-executions.hub')
            ->with('status', __('Slaughter execution recorded successfully.'));
    }

    public function show(Request $request, SlaughterExecution $slaughterExecution): View|RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);

        // --- Section 3 ---
        $slaughterExecution->load([
            'slaughterPlan.facility.business',
            'slaughterPlan.inspector',
            'slaughterPlan.anteMortemInspections.inspectionItems.intakeItem',
            'executionItems.intakeItem',
            'batches.items',
        ]);

        return view('slaughter-executions.show', ['execution' => $slaughterExecution]);
    }

    public function edit(Request $request, SlaughterExecution $slaughterExecution): View|RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);

        $planIds = $this->userSlaughterPlanIds($request);

        $planData = $this->buildPlanSelectData($planIds, includeScheduledCount: false);
        $plans = $planData['plans'];
        $approvedItemsByPlan = $planData['approvedItemsByPlan'];
        $slaughteredItemIdsByPlan = $planData['slaughteredItemIdsByPlan'];
        $slaughteredDetailsByPlan = $planData['slaughteredDetailsByPlan'];
        $amDateByPlan = $planData['amDateByPlan'];

        // --- Section 3 ---
        $slaughterExecution->load([
            'executionItems.intakeItem',
            'slaughterPlan.anteMortemInspections',
        ]);

        $approvedItems = AnteMortemInspectionItem::query()
            ->whereHas('inspection', fn ($query) => $query->where('slaughter_plan_id', $slaughterExecution->slaughter_plan_id))
            ->approved()
            ->with('intakeItem')
            ->get();

        $executionItems = $slaughterExecution->executionItems;
        $currentExecutionItemIds = $executionItems->pluck('animal_intake_item_id')->map(fn ($id) => (int) $id)->all();
        $planSlaughteredIds = $slaughteredItemIdsByPlan->get($slaughterExecution->slaughter_plan_id) ?? [];
        $slaughteredItemIds = array_values(array_diff($planSlaughteredIds, $currentExecutionItemIds));
        $slaughteredDetails = collect($slaughteredDetailsByPlan->get($slaughterExecution->slaughter_plan_id) ?? [])
            ->reject(fn (array $row) => in_array($row['animal_intake_item_id'], $currentExecutionItemIds, true))
            ->values()
            ->all();

        return view('slaughter-executions.edit', [
            'execution' => $slaughterExecution,
            'plans' => $plans,
            'approvedItems' => $approvedItems,
            'executionItems' => $executionItems,
            'approvedItemsByPlan' => $approvedItemsByPlan,
            'slaughteredItemIdsByPlan' => $slaughteredItemIdsByPlan,
            'slaughteredDetailsByPlan' => $slaughteredDetailsByPlan,
            'slaughteredItemIds' => $slaughteredItemIds,
            'slaughteredDetails' => $slaughteredDetails,
            'currentExecutionItemIds' => $currentExecutionItemIds,
            'amDateByPlan' => $amDateByPlan,
        ]);
    }

    public function update(UpdateSlaughterExecutionRequest $request, SlaughterExecution $slaughterExecution): RedirectResponse
    {
        $this->authorizeExecution($request, $slaughterExecution);

        $validated = $request->validated();
        $planId = (int) ($validated['slaughter_plan_id'] ?? $slaughterExecution->slaughter_plan_id);
        $this->authorizePlanId($request, $planId);

        $hasItemSlaughters = array_key_exists('item_slaughters', $validated);
        $itemSlaughters = $validated['item_slaughters'] ?? null;
        unset($validated['item_slaughters']);

        DB::transaction(function () use ($slaughterExecution, $validated, $hasItemSlaughters, $itemSlaughters): void {
            $slaughterExecution->update($validated);

            // --- Section 3 ---
            if ($hasItemSlaughters) {
                $slaughterExecution->executionItems()->delete();

                if (! empty($itemSlaughters)) {
                    foreach ($itemSlaughters as $item) {
                        $slaughterExecution->executionItems()->create([
                            'animal_intake_item_id' => $item['animal_intake_item_id'],
                            'meat_quantity_kg' => $item['meat_quantity_kg'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }

                    $slaughterExecution->update([
                        'actual_animals_slaughtered' => $slaughterExecution->slaughtered_count_from_items,
                        'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
                    ]);
                } else {
                    $slaughterExecution->update([
                        'slaughter_count_source' => SlaughterExecution::SOURCE_MANUAL,
                    ]);
                }
            }
        });

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
