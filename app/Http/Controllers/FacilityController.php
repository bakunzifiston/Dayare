<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Business;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends Controller
{
    private function authorizeBusiness(Request $request, Business $business): void
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'Facility access denied: this business does not belong to your account.');
        }
    }

    public function index(Request $request, Business $business): View
    {
        $this->authorizeBusiness($request, $business);

        $facilities = $business->facilities()->with(['province', 'districtDivision', 'sectorDivision', 'cell', 'village'])->latest()->paginate(10);

        $kpis = [
            'total' => $business->facilities()->count(),
            'active' => $business->facilities()->where('status', Facility::STATUS_ACTIVE)->count(),
        ];

        return view('facilities.index', compact('business', 'facilities', 'kpis'));
    }

    public function create(Request $request, Business $business): View
    {
        $this->authorizeBusiness($request, $business);

        return view('facilities.create', compact('business'));
    }

    public function store(StoreFacilityRequest $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $business->facilities()->create($request->validated());

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility added successfully.'));
    }

    public function show(Request $request, Business $business, Facility $facility): View
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404, 'Facility does not belong to this business.');
        }

        $facility->load(['province', 'districtDivision', 'sectorDivision', 'cell', 'village', 'inspectors', 'employees']);

        return view('facilities.show', compact('business', 'facility'));
    }

    public function edit(Request $request, Business $business, Facility $facility): View
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404, 'Facility does not belong to this business.');
        }

        return view('facilities.edit', compact('business', 'facility'));
    }

    public function update(UpdateFacilityRequest $request, Business $business, Facility $facility): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404, 'Facility does not belong to this business.');
        }

        $facility->update($request->validated());

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility updated successfully.'));
    }

    public function destroy(Request $request, Business $business, Facility $facility): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404, 'Facility does not belong to this business.');
        }

        $facility->delete();

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility removed.'));
    }
}
