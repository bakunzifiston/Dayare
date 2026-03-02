<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalIntakeRequest;
use App\Http\Requests\UpdateAnimalIntakeRequest;
use App\Models\AnimalIntake;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnimalIntakeController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->pluck('id');
    }

    private function authorizeIntake(Request $request, AnimalIntake $intake): void
    {
        if (! $this->userFacilityIds($request)->contains($intake->facility_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $intakes = AnimalIntake::with(['facility', 'country', 'province', 'district'])
            ->whereIn('facility_id', $facilityIds)
            ->latest('intake_date')
            ->paginate(10);

        $kpis = [
            'total' => AnimalIntake::whereIn('facility_id', $facilityIds)->count(),
            'received' => AnimalIntake::whereIn('facility_id', $facilityIds)->where('status', AnimalIntake::STATUS_RECEIVED)->count(),
            'approved' => AnimalIntake::whereIn('facility_id', $facilityIds)->where('status', AnimalIntake::STATUS_APPROVED)->count(),
        ];

        return view('animal-intakes.index', compact('intakes', 'kpis'));
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        return view('animal-intakes.create', compact('facilities'));
    }

    public function store(StoreAnimalIntakeRequest $request): RedirectResponse
    {
        if (! $this->userFacilityIds($request)->contains((int) $request->validated('facility_id'))) {
            abort(404);
        }

        AnimalIntake::create($request->validated());

        return redirect()->route('animal-intakes.index')
            ->with('status', __('Animal intake recorded.'));
    }

    public function show(Request $request, AnimalIntake $animalIntake): View
    {
        $this->authorizeIntake($request, $animalIntake);
        $animalIntake->load(['facility', 'country', 'province', 'district', 'sector', 'cell', 'village', 'slaughterPlans']);

        return view('animal-intakes.show', ['intake' => $animalIntake]);
    }

    public function edit(Request $request, AnimalIntake $animalIntake): View
    {
        $this->authorizeIntake($request, $animalIntake);
        $facilityIds = $this->userFacilityIds($request);
        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);

        return view('animal-intakes.edit', ['intake' => $animalIntake, 'facilities' => $facilities]);
    }

    public function update(UpdateAnimalIntakeRequest $request, AnimalIntake $animalIntake): RedirectResponse
    {
        $this->authorizeIntake($request, $animalIntake);
        if (! $this->userFacilityIds($request)->contains((int) $request->validated('facility_id'))) {
            abort(404);
        }

        $animalIntake->update($request->validated());

        return redirect()->route('animal-intakes.show', $animalIntake)
            ->with('status', __('Animal intake updated.'));
    }
}
