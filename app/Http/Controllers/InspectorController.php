<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectorRequest;
use App\Http\Requests\UpdateInspectorRequest;
use App\Models\AdministrativeDivision;
use App\Models\Facility;
use App\Models\Inspector;
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

    public function hub(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $base = Inspector::query()->whereIn('facility_id', $facilityIds);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'facility_id' => (string) $request->query('facility_id', ''),
            'status' => (string) $request->query('status', ''),
            'auth_expired' => $request->boolean('auth_expired'),
        ];

        $filtered = (clone $base)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('national_id', 'like', $search)
                        ->orWhere('authorization_number', 'like', $search);
                });
            })
            ->when($filters['facility_id'] !== '', fn ($query) => $query->where('facility_id', (int) $filters['facility_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['auth_expired'], fn ($query) => $query->whereDate('authorization_expiry_date', '<', today()));

        $inspectors = (clone $filtered)
            ->with('facility.business')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $kpis = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', Inspector::STATUS_ACTIVE)->count(),
            'expired' => (clone $base)->where('status', Inspector::STATUS_EXPIRED)->count(),
        ];

        $facilities = Facility::query()
            ->whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name']);

        return view('inspectors.hub', compact('inspectors', 'kpis', 'filters', 'facilities'));
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('inspectors.hub');
    }

    public function create(Request $request): View
    {
        $facilities = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type', 'business_id'])
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'label' => $f->facility_name.' ('.$f->facility_type.')',
            ]);

        $species = $request->user()->configuredSpeciesForBusinessIds($request->user()->accessibleBusinessIds());

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

        return redirect()->route('inspectors.hub')
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
                'label' => $f->facility_name.' ('.$f->facility_type.')',
            ]);

        $species = $request->user()->configuredSpeciesForBusinessIds($request->user()->accessibleBusinessIds());

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

        return redirect()->route('inspectors.hub')
            ->with('status', __('Inspector updated successfully.'));
    }

    public function destroy(Request $request, Inspector $inspector): RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $inspector->delete();

        return redirect()->route('inspectors.hub')
            ->with('status', __('Inspector removed.'));
    }

    private function syncInspectorLocationFromDivisions(array $data, ?Inspector $inspector = null): array
    {
        if (! empty($data['country_id'])) {
            $data['country'] = $this->divisionName($data['country_id']) ?? ($data['country'] ?? '');
            $data['district'] = ! empty($data['district_id'])
                ? ($this->divisionName($data['district_id']) ?? ($data['district'] ?? ''))
                : ($data['district'] ?? '');
            $data['sector'] = ! empty($data['sector_id'])
                ? ($this->divisionName($data['sector_id']) ?? ($data['sector'] ?? ''))
                : ($data['sector'] ?? '');
            $data['cell'] = ! empty($data['cell_id'])
                ? ($this->divisionName($data['cell_id']) ?? ($data['cell'] ?? null))
                : ($data['cell'] ?? null);
            $data['village'] = ! empty($data['village_id'])
                ? ($this->divisionName($data['village_id']) ?? ($data['village'] ?? null))
                : ($data['village'] ?? null);
        } elseif ($inspector) {
            $data['country'] = $data['country'] ?? $inspector->country;
            $data['district'] = $data['district'] ?? $inspector->district;
            $data['sector'] = $data['sector'] ?? $inspector->sector;
            $data['cell'] = $data['cell'] ?? $inspector->cell;
            $data['village'] = $data['village'] ?? $inspector->village;
        }

        // Legacy text columns are NOT NULL — ensure defaults when location is omitted on the form.
        $country = trim((string) ($data['country'] ?? $inspector?->country ?? ''));
        if ($country === '' && ! empty($data['nationality'])) {
            $country = trim((string) $data['nationality']);
        }
        $data['country'] = $country;
        $data['district'] = trim((string) ($data['district'] ?? $inspector?->district ?? ''));
        $data['sector'] = trim((string) ($data['sector'] ?? $inspector?->sector ?? ''));

        foreach (['cell', 'village'] as $field) {
            if (! array_key_exists($field, $data)) {
                $data[$field] = $inspector?->{$field};
                continue;
            }
            $value = $data[$field];
            $data[$field] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $data;
    }

    private function divisionName(?int $divisionId): ?string
    {
        if (! $divisionId) {
            return null;
        }

        return AdministrativeDivision::query()->whereKey($divisionId)->value('name');
    }

    private function speciesAllowedToString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_filter($value));
        }

        return is_string($value) ? $value : '';
    }
}
