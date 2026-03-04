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
    private function authorizeBusiness(Request $request, Business $business): RedirectResponse|null
    {
        if ($business->user_id !== $request->user()->id) {
            return redirect()->route('login')
                ->with('error', __('You do not have access to this business, or your session may have expired. Please log in again.'));
        }
        return null;
    }

    public function index(Request $request, Business $business): View|RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        $facilities = $business->facilities()->with(['province', 'districtDivision', 'sectorDivision', 'cell', 'village'])->latest()->paginate(10);

        $kpis = [
            'total' => $business->facilities()->count(),
            'active' => $business->facilities()->where('status', Facility::STATUS_ACTIVE)->count(),
        ];

        return view('facilities.index', compact('business', 'facilities', 'kpis'));
    }

    public function create(Request $request, Business $business): View|RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        return view('facilities.create', compact('business'));
    }

    public function store(StoreFacilityRequest $request, Business $business): RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        $business->facilities()->create($request->validated());

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility added successfully.'));
    }

    public function show(Request $request, Business $business, Facility $facility): View|RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        $facility->load(['province', 'districtDivision', 'sectorDivision', 'cell', 'village']);

        return view('facilities.show', compact('business', 'facility'));
    }

    public function edit(Request $request, Business $business, Facility $facility): View|RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        return view('facilities.edit', compact('business', 'facility'));
    }

    public function update(UpdateFacilityRequest $request, Business $business, Facility $facility): RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        $facility->update($request->validated());

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility updated successfully.'));
    }

    public function destroy(Request $request, Business $business, Facility $facility): RedirectResponse
    {
        if ($redirect = $this->authorizeBusiness($request, $business)) {
            return $redirect;
        }

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        $facility->delete();

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility removed.'));
    }
}
