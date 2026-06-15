<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Models\AnteMortemInspectionItem;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
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

        // --- Hub ---
        $hubStats = [
            'total_executions' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->count(),
            'total_slaughtered' => (int) SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->sum('actual_animals_slaughtered'),
            'total_meat_kg' => (float) SlaughterExecutionItem::query()
                ->whereHas('execution', fn ($query) => $query->whereIn('slaughter_plan_id', $planIds))
                ->sum('meat_quantity_kg'),
            'plans_without_execution' => SlaughterPlan::query()
                ->whereIn('id', $planIds)
                ->whereDoesntHave('slaughterExecutions')
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'executions_today' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->whereDate('slaughter_time', today())
                ->count(),
            'pending_batches' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->where('status', SlaughterExecution::STATUS_COMPLETED)
                ->whereDoesntHave('batches')
                ->count(),
        ];

        $byStatus = SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $planIds)
            ->with(['slaughterPlan.facility', 'executionItems', 'batches'])
            ->orderByDesc('slaughter_time')
            ->orderByDesc('id')
            ->get()
            ->groupBy('status');

        $recentExecutions = SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $planIds)
            ->with(['slaughterPlan.facility', 'executionItems'])
            ->orderByDesc('slaughter_time')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('slaughter-executions.hub', compact(
            'hubStats',
            'byStatus',
            'recentExecutions',
        ));
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $planIds = $this->userSlaughterPlanIds($request);

        // --- Section 3 ---
        $hubStats = [
            'total_executions' => SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->count(),
            'total_slaughtered' => (int) SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->sum('actual_animals_slaughtered'),
            'total_meat_kg' => (float) SlaughterExecutionItem::query()
                ->whereHas('execution', fn ($query) => $query->whereIn('slaughter_plan_id', $planIds))
                ->sum('meat_quantity_kg'),
            'plans_without_execution' => SlaughterPlan::query()
                ->whereIn('id', $planIds)
                ->whereDoesntHave('slaughterExecutions')
                ->whereNotIn('status', ['cancelled'])
                ->count(),
        ];

        $query = SlaughterExecution::query()
            ->with(['slaughterPlan.facility', 'slaughterPlan.anteMortemInspections', 'executionItems.intakeItem', 'batches'])
            ->whereIn('slaughter_plan_id', $planIds);

        if ($request->filled('facility_id')) {
            $facilityId = (int) $request->query('facility_id');
            if ($facilityIds->contains($facilityId)) {
                $query->whereHas('slaughterPlan', fn ($planQuery) => $planQuery->where('facility_id', $facilityId));
            }
        }

        if ($request->filled('status')) {
            $status = (string) $request->query('status');
            if (in_array($status, SlaughterExecution::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('slaughter_time', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('slaughter_time', '<=', $request->query('date_to').' 23:59:59');
        }

        if ($request->query('has_items') === '1') {
            $query->has('executionItems');
        } elseif ($request->query('has_items') === '0') {
            $query->doesntHave('executionItems');
        }

        $executions = $query
            ->latest('slaughter_time')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $facilities = Facility::query()
            ->whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name']);

        return view('slaughter-executions.index', compact(
            'hubStats',
            'executions',
            'facilities',
        ));
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
