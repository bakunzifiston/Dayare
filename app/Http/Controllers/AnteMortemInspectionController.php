<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnteMortemInspectionRequest;
use App\Http\Requests\UpdateAnteMortemInspectionRequest;
use App\Models\AnteMortemInspection;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    private function mapObservationPayload(array $observations): array
    {
        return collect($observations)
            ->map(function ($row, $item) {
                return [
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function checklistConfig(): array
    {
        return AnteMortemChecklist::all();
    }

    public function index(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        $inspections = AnteMortemInspection::with(['slaughterPlan.facility', 'slaughterPlan.inspector', 'inspector'])
            ->whereIn('slaughter_plan_id', $planIds)
            ->latest('inspection_date')
            ->paginate(10);

        $kpis = [
            'total' => AnteMortemInspection::whereIn('slaughter_plan_id', $planIds)->count(),
        ];

        return view('ante-mortem-inspections.index', compact('inspections', 'kpis'));
    }

    public function create(Request $request): View
    {
        $planIds = $this->userSlaughterPlanIds($request);

        $plans = SlaughterPlan::with('facility')
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get()
            ->map(fn (SlaughterPlan $p) => [
                'id' => $p->id,
                'label' => $p->slaughter_date->format('d M Y').' — '.$p->facility->facility_name.' ('.$p->species.')',
                'facility_id' => $p->facility_id,
            ]);

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
        ]);
    }

    public function store(StoreAnteMortemInspectionRequest $request): RedirectResponse
    {
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        unset($validated['observations']);

        DB::transaction(function () use ($validated, $observations) {
            $inspection = AnteMortemInspection::create($validated);
            $inspection->observations()->createMany($this->mapObservationPayload($observations));
        });

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection recorded successfully.'));
    }

    public function show(Request $request, AnteMortemInspection $anteMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);
        $anteMortemInspection->load(['slaughterPlan.facility.business', 'inspector', 'observations']);

        return view('ante-mortem-inspections.show', ['inspection' => $anteMortemInspection]);
    }

    public function edit(Request $request, AnteMortemInspection $anteMortemInspection): View|RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);

        $planIds = $this->userSlaughterPlanIds($request);

        $plans = SlaughterPlan::with('facility')
            ->whereIn('id', $planIds)
            ->orderByDesc('slaughter_date')
            ->get()
            ->map(fn (SlaughterPlan $p) => [
                'id' => $p->id,
                'label' => $p->slaughter_date->format('d M Y').' — '.$p->facility->facility_name.' ('.$p->species.')',
                'facility_id' => $p->facility_id,
            ]);

        $inspectorsByFacility = Inspector::whereIn('facility_id', $this->userFacilityIds($request))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($inspectors) => $inspectors->map(fn (Inspector $i) => ['id' => $i->id, 'label' => $i->full_name])->values());

        $anteMortemInspection->load('observations');

        return view('ante-mortem-inspections.edit', [
            'inspection' => $anteMortemInspection,
            'plans' => $plans,
            'inspectorsByFacility' => $inspectorsByFacility,
            'checklists' => $this->checklistConfig(),
        ]);
    }

    public function update(UpdateAnteMortemInspectionRequest $request, AnteMortemInspection $anteMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);
        $this->authorizePlanId($request, (int) $request->validated('slaughter_plan_id'));

        $validated = $request->validated();
        $observations = $validated['observations'] ?? [];
        unset($validated['observations']);

        DB::transaction(function () use ($anteMortemInspection, $validated, $observations) {
            $anteMortemInspection->update($validated);
            $anteMortemInspection->observations()->delete();
            $anteMortemInspection->observations()->createMany($this->mapObservationPayload($observations));
        });

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection updated successfully.'));
    }

    public function destroy(Request $request, AnteMortemInspection $anteMortemInspection): RedirectResponse
    {
        $this->authorizeInspection($request, $anteMortemInspection);
        $anteMortemInspection->delete();

        return redirect()->route('ante-mortem-inspections.index')
            ->with('status', __('Ante-mortem inspection removed.'));
    }
}
