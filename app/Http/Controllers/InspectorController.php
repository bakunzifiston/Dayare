<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectorRequest;
use App\Http\Requests\UpdateInspectorRequest;
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
        return Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
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
        $facilities = Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type', 'business_id'])
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'label' => $f->facility_name . ' (' . $f->facility_type . ')',
            ]);

        return view('inspectors.create', compact('facilities'));
    }

    public function store(StoreInspectorRequest $request): RedirectResponse
    {
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        Inspector::create($request->validated());

        return redirect()->route('inspectors.index')
            ->with('status', __('Inspector registered successfully.'));
    }

    public function show(Request $request, Inspector $inspector): View|RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $inspector->load('facility.business');

        return view('inspectors.show', compact('inspector'));
    }

    public function edit(Request $request, Inspector $inspector): View|RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);

        $facilities = Facility::whereIn('business_id', $request->user()->businesses()->pluck('id'))
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type'])
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'label' => $f->facility_name . ' (' . $f->facility_type . ')',
            ]);

        return view('inspectors.edit', compact('inspector', 'facilities'));
    }

    public function update(UpdateInspectorRequest $request, Inspector $inspector): RedirectResponse
    {
        $this->authorizeInspector($request, $inspector);
        $this->authorizeFacilityId($request, (int) $request->validated('facility_id'));

        $inspector->update($request->validated());

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
}
