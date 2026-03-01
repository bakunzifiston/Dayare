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
        if ($business->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    public function index(Request $request, Business $business): View|RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $facilities = $business->facilities()->latest()->paginate(10);

        $kpis = [
            'total' => $business->facilities()->count(),
            'active' => $business->facilities()->where('status', Facility::STATUS_ACTIVE)->count(),
        ];

        return view('facilities.index', compact('business', 'facilities', 'kpis'));
    }

    public function create(Request $request, Business $business): View|RedirectResponse
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

    public function show(Request $request, Business $business, Facility $facility): View|RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        return view('facilities.show', compact('business', 'facility'));
    }

    public function edit(Request $request, Business $business, Facility $facility): View|RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        return view('facilities.edit', compact('business', 'facility'));
    }

    public function update(UpdateFacilityRequest $request, Business $business, Facility $facility): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        $facility->update($request->validated());

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility updated successfully.'));
    }

    public function destroy(Request $request, Business $business, Facility $facility): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        if ($facility->business_id !== $business->id) {
            abort(404);
        }

        $facility->delete();

        return redirect()->route('businesses.facilities.index', $business)
            ->with('status', __('Facility removed.'));
    }
}
