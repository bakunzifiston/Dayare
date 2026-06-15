<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientAnimalsException;
use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Services\Processor\SlaughterPlanAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SlaughterPlanController extends Controller
{
    public function __construct(
        private readonly SlaughterPlanAssignmentService $assignmentService,
    ) {}

    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function authorizePlan(Request $request, SlaughterPlan $plan): void
    {
        if (! $this->userFacilityIds($request)->contains($plan->facility_id)) {
            abort(404);
        }
    }

    private function authorizeFacilityId(Request $request, int $facilityId): void
    {
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }
    }

    /**
     * @return array{
     *   intakes: Collection<int, AnimalIntake>,
     *   intakeSpeciesMix: Collection<int|string, string>,
     *   intakeAnimals: Collection<int|string, Collection<int, array<string, mixed>>>,
     *   eligibleIntakes: Collection<int, array{id: int, facility_id: int, label: string}>
     * }
     */
    private function intakeFormData(\Illuminate\Support\Collection $facilityIds, ?int $alwaysIncludeIntakeId = null): array
    {
        $intakes = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->plannableForSlaughter()
            ->where(function ($query) use ($alwaysIncludeIntakeId): void {
                $query->where(function ($q): void {
                    $q->whereHas('items', fn ($q) => $q->available())
                        ->orWhereDoesntHave('items');
                });
                if ($alwaysIncludeIntakeId) {
                    $query->orWhere('id', $alwaysIncludeIntakeId);
                }
            })
            ->with(['items' => fn ($q) => $q->available()])
            ->get();

        $intakeSpeciesMix = $intakes->mapWithKeys(fn (AnimalIntake $i) => [
            $i->id => trim(($i->species_mix_label ?: ($i->species ?? '—'))
                .($i->isHealthCertificateExpired() ? ' · cert expired' : '')
                .(blank($i->health_certificate_expiry_date) ? ' · no cert' : '')),
        ]);

        $intakeAnimals = $intakes->mapWithKeys(fn (AnimalIntake $i) => [
            $i->id => $i->items->map(fn (AnimalIntakeItem $item) => [
                'id' => $item->id,
                'ear_tag' => $item->ear_tag,
                'species' => $item->species,
                'sex' => ucfirst($item->sex),
                'age_months' => $item->age_months,
                'live_weight_kg' => $item->live_weight_kg,
                'body_condition_score' => $item->body_condition_label,
                'health_status' => $item->health_status,
                'health_status_label' => $item->health_status_label,
            ])->values(),
        ]);

        $eligibleIntakes = $intakes->map(fn (AnimalIntake $i) => [
            'id' => $i->id,
            'facility_id' => $i->facility_id,
            'reference' => $i->reference,
            'label' => ($i->reference ?? 'INT-'.$i->id).' — '.($intakeSpeciesMix[$i->id] ?? '—'),
        ])->values();

        return compact('intakes', 'intakeSpeciesMix', 'intakeAnimals', 'eligibleIntakes');
    }

    public function hub(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $base = SlaughterPlan::query()->whereIn('facility_id', $facilityIds);

        $totalPlans = (clone $base)->count();
        $plannedCount = (clone $base)->where('status', SlaughterPlan::STATUS_PLANNED)->count();
        $approvedCount = (clone $base)->where('status', SlaughterPlan::STATUS_APPROVED)->count();
        $plansWithExecutionsCount = (clone $base)->has('slaughterExecutions')->count();
        $totalAnimalsScheduled = (int) (clone $base)->sum('number_of_animals_scheduled');

        $approvedAnimalIntakesCount = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->plannableForSlaughter()
            ->count();

        $hubStats = [
            'animals_scheduled' => (int) SlaughterPlan::whereIn('facility_id', $facilityIds)
                ->whereNotIn('status', ['executed'])
                ->sum('number_of_animals_scheduled'),
            'assignment_gap_count' => SlaughterPlan::whereIn('facility_id', $facilityIds)
                ->withAssignmentGap()
                ->count(),
        ];

        $plans = SlaughterPlan::query()
            ->whereIn('facility_id', $facilityIds)
            ->with([
                'facility',
                'inspector',
                'intake.items',
                'assignedItems' => fn ($q) => $q->orderBy('id'),
            ])
            ->latest('slaughter_date')
            ->paginate(15);

        return view('slaughter-plans.hub', compact(
            'totalPlans',
            'plannedCount',
            'approvedCount',
            'plansWithExecutionsCount',
            'totalAnimalsScheduled',
            'approvedAnimalIntakesCount',
            'hubStats',
            'plans',
        ));
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $plans = SlaughterPlan::with(['facility.business', 'inspector'])
            ->whereIn('facility_id', $facilityIds)
            ->latest('slaughter_date')
            ->paginate(10);

        $kpis = [
            'total' => SlaughterPlan::whereIn('facility_id', $facilityIds)->count(),
            'planned' => SlaughterPlan::whereIn('facility_id', $facilityIds)->where('status', SlaughterPlan::STATUS_PLANNED)->count(),
            'approved' => SlaughterPlan::whereIn('facility_id', $facilityIds)->where('status', SlaughterPlan::STATUS_APPROVED)->count(),
        ];

        return view('slaughter-plans.index', compact('plans', 'kpis'));
    }

    // --- Section C ---

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $facilities = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $intakeForm = $this->intakeFormData($facilityIds);

        return view('slaughter-plans.create', [
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
            'eligibleIntakes' => $intakeForm['eligibleIntakes'],
            'intakeSpeciesMix' => $intakeForm['intakeSpeciesMix'],
            'intakeAnimals' => $intakeForm['intakeAnimals'],
        ]);
    }

    public function store(StoreSlaughterPlanRequest $request): RedirectResponse
    {
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        try {
            $plan = DB::transaction(function () use ($request): SlaughterPlan {
                $plan = SlaughterPlan::create($request->validated());

                if ($plan->animal_intake_id) {
                    $plan->load('animalIntake');
                    if ($plan->animalIntake && $plan->animalIntake->items()->exists()) {
                        $this->assignmentService->assignItemsToPlan(
                            $plan,
                            (int) $plan->number_of_animals_scheduled,
                            $plan->species,
                        );
                    }
                }

                return $plan;
            });
        } catch (InsufficientAnimalsException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $assignedCount = $plan->animal_intake_id && $plan->animalIntake?->items()->exists()
            ? AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count()
            : (int) $plan->number_of_animals_scheduled;

        return redirect()->route('slaughter-plans.hub')
            ->with('status', __('Slaughter plan :ref created — :count animals assigned.', [
                'ref' => $plan->id,
                'count' => $assignedCount,
            ]));
    }

    public function show(Request $request, SlaughterPlan $slaughterPlan): View|RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);
        $slaughterPlan->load(['facility.business', 'inspector', 'animalIntake', 'anteMortemInspections', 'slaughterExecutions']);

        return view('slaughter-plans.show', ['plan' => $slaughterPlan]);
    }

    // --- Section C ---

    public function edit(Request $request, SlaughterPlan $slaughterPlan): View|RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);

        $facilityIds = $this->userFacilityIds($request);

        $facilities = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $intakeForm = $this->intakeFormData($facilityIds, $slaughterPlan->animal_intake_id);

        $slaughterPlan->load(['assignedItems' => fn ($q) => $q->orderBy('id')]);

        return view('slaughter-plans.edit', [
            'plan' => $slaughterPlan,
            'facilities' => $facilities,
            'inspectorsByFacility' => $inspectorsByFacility,
            'eligibleIntakes' => $intakeForm['eligibleIntakes'],
            'intakeSpeciesMix' => $intakeForm['intakeSpeciesMix'],
            'intakeAnimals' => $intakeForm['intakeAnimals'],
            'assignedAnimals' => $slaughterPlan->assignedItems,
        ]);
    }

    public function update(UpdateSlaughterPlanRequest $request, SlaughterPlan $slaughterPlan): RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        try {
            DB::transaction(function () use ($request, $slaughterPlan): void {
                $oldIntakeId = $slaughterPlan->animal_intake_id;
                $oldCount = (int) $slaughterPlan->number_of_animals_scheduled;
                $oldSpecies = $slaughterPlan->species;
                $oldIntakeHadItems = $oldIntakeId
                    && AnimalIntake::query()->whereKey($oldIntakeId)->whereHas('items')->exists();

                $slaughterPlan->update($request->validated());
                $slaughterPlan->refresh();
                $slaughterPlan->load('animalIntake');

                $intakeChanged = $slaughterPlan->animal_intake_id !== $oldIntakeId;
                $countChanged = (int) $slaughterPlan->number_of_animals_scheduled !== $oldCount;
                $speciesChanged = $slaughterPlan->species !== $oldSpecies;

                if ($intakeChanged && $oldIntakeId && $oldIntakeHadItems) {
                    AnimalIntakeItem::query()
                        ->where('slaughter_plan_id', $slaughterPlan->id)
                        ->where('animal_intake_id', $oldIntakeId)
                        ->update(['slaughter_plan_id' => null]);
                }

                if ($slaughterPlan->animal_intake_id && $slaughterPlan->animalIntake?->items()->exists()) {
                    if ($intakeChanged || $countChanged || $speciesChanged) {
                        $this->assignmentService->rebalancePlan(
                            $slaughterPlan,
                            (int) $slaughterPlan->number_of_animals_scheduled,
                            $slaughterPlan->species,
                        );
                    } elseif ($slaughterPlan->assignedItems()->count() === 0) {
                        $this->assignmentService->assignItemsToPlan(
                            $slaughterPlan,
                            (int) $slaughterPlan->number_of_animals_scheduled,
                            $slaughterPlan->species,
                        );
                    }
                } elseif ($intakeChanged || $countChanged || $speciesChanged) {
                    $this->assignmentService->releaseItemsFromPlan($slaughterPlan);
                }
            });
        } catch (InsufficientAnimalsException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $assignedCount = AnimalIntakeItem::where('slaughter_plan_id', $slaughterPlan->id)->count();

        return redirect()->route('slaughter-plans.hub')
            ->with('status', __('Slaughter plan updated — :count animals assigned.', [
                'count' => $assignedCount > 0 ? $assignedCount : (int) $slaughterPlan->number_of_animals_scheduled,
            ]));
    }

    public function destroy(Request $request, SlaughterPlan $slaughterPlan): RedirectResponse
    {
        $this->authorizePlan($request, $slaughterPlan);

        DB::transaction(function () use ($slaughterPlan): void {
            if ($slaughterPlan->animal_intake_id) {
                $this->assignmentService->releaseItemsFromPlan($slaughterPlan);
            }
            $slaughterPlan->delete();
        });

        return redirect()->route('slaughter-plans.hub')
            ->with('status', __('Slaughter plan deleted and animals released.'));
    }
}
