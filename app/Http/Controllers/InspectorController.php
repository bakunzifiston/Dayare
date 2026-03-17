<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectorRequest;
use App\Http\Requests\UpdateInspectorRequest;
use App\Models\AdministrativeDivision;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\Species;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectorController extends Controller
{
    /**
     * Get facility IDs that the current user can assign inspectors to (their businesses' facilities).
     */
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function authorizeInspector(Request $request, Inspector $inspector): void
    {
        $facilityIds = $this->userFacilityIds($request);
        if (! $facilityIds->contains($inspector->facility_id)) {
            abort(404);
        }
    }

    private function authorizeFacilityId(Request $request, int $facilityId): void
    {
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $inspectors = Inspector::with('facility.business')
            ->whereIn('facility_id', $facilityIds)
            ->latest()
            ->paginate(10);

        $kpis = [
            'total' => Inspector::whereIn('facility_id', $facilityIds)->count(),
            'active' => Inspector::whereIn('facility_id', $facilityIds)->where('status', Inspector::STATUS_ACTIVE)->count(),
        ];

        return view('inspectors.index', compact('inspectors', 'kpis'));
    }

    public function create(Request $request): View
    {
        $facilities = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type', 'business_id'])
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'label' => $f->facility_name . ' (' . $f->facility_type . ')',
            ]);

        $species = Species::active()->get();

        return view('inspectors.create', compact('facilities', 'species'));
    }

    public function store(StoreInspectorRequest $request): RedirectResponse
    {
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        $data = $this->syncInspectorLocationFromDivisions($request->validated());
        if (isset($data['species_allowed'])) {
            $data['species_allowed'] = $this->speciesAllowedToString($data['species_allowed']);
        }
        Inspector::create($data);

        return redirect()->route('inspectors.index')
            ->with('status', __('Inspector registered successfully.'));
    }

    public function show(Request $request, Inspector $inspector): View|RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $inspector->load([
            'facility.business',
            'countryDivision',
            'province',
            'districtDivision',
            'sectorDivision',
            'cellDivision',
            'villageDivision',
        ]);

        return view('inspectors.show', compact('inspector'));
    }

    public function edit(Request $request, Inspector $inspector): View|RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);

        $facilities = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type'])
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'label' => $f->facility_name . ' (' . $f->facility_type . ')',
            ]);

        $species = Species::active()->get();

        return view('inspectors.edit', compact('inspector', 'facilities', 'species'));
    }

    public function update(UpdateInspectorRequest $request, Inspector $inspector): RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        $data = $this->syncInspectorLocationFromDivisions($request->validated(), $inspector);
        if (array_key_exists('species_allowed', $data)) {
            $data['species_allowed'] = $this->speciesAllowedToString($data['species_allowed'] ?? []);
        } else {
            unset($data['species_allowed']);
        }
        $inspector->update($data);

        return redirect()->route('inspectors.index')
            ->with('status', __('Inspector updated successfully.'));
    }

    public function destroy(Request $request, Inspector $inspector): RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $inspector->delete();

        return redirect()->route('inspectors.index')
            ->with('status', __('Inspector removed.'));
    }

    private function syncInspectorLocationFromDivisions(array $data, ?Inspector $inspector = null): array
    {
        if (! empty($data['country_id'])) {
            $data['country'] = AdministrativeDivision::find($data['country_id'])?->name ?? $data['country'] ?? '';
            $data['district'] = isset($data['district_id']) ? (AdministrativeDivision::find($data['district_id'])?->name ?? $data['district'] ?? '') : ($data['district'] ?? '');
            $data['sector'] = isset($data['sector_id']) ? (AdministrativeDivision::find($data['sector_id'])?->name ?? $data['sector'] ?? '') : ($data['sector'] ?? '');
            $data['cell'] = isset($data['cell_id']) ? (AdministrativeDivision::find($data['cell_id'])?->name ?? $data['cell'] ?? null) : ($data['cell'] ?? null);
            $data['village'] = isset($data['village_id']) ? (AdministrativeDivision::find($data['village_id'])?->name ?? $data['village'] ?? null) : ($data['village'] ?? null);
        } elseif ($inspector) {
            $data['country'] = $data['country'] ?? $inspector->country;
            $data['district'] = $data['district'] ?? $inspector->district;
            $data['sector'] = $data['sector'] ?? $inspector->sector;
            $data['cell'] = $data['cell'] ?? $inspector->cell;
            $data['village'] = $data['village'] ?? $inspector->village;
        }
        return $data;
    }

    private function speciesAllowedToString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_filter($value));
        }
        return is_string($value) ? $value : '';
    }
}
