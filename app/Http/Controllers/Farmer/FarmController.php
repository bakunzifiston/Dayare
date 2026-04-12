<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmRequest;
use App\Http\Requests\Farmer\UpdateFarmRequest;
use App\Models\Farm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farms = Farm::query()
            ->with('business')
            ->whereIn('business_id', $farmerIds)
            ->orderBy('name')
            ->paginate(15);

        return view('farmer.farms.index', compact('farms'));
    }

    public function create(Request $request): View
    {
        $farmerBusinesses = $request->user()
            ->accessibleBusinesses()
            ->where('type', \App\Models\Business::TYPE_FARMER)
            ->orderBy('business_name')
            ->get();

        return view('farmer.farms.create', compact('farmerBusinesses'));
    }

    public function store(StoreFarmRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['animal_types'])) {
            $data['animal_types'] = null;
        }
        Farm::create($data);

        return redirect()->route('farmer.farms.index')
            ->with('status', __('Farm created.'));
    }

    public function show(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $farm->load(['livestock', 'business']);

        return view('farmer.farms.show', compact('farm'));
    }

    public function edit(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);

        return view('farmer.farms.edit', compact('farm'));
    }

    public function update(UpdateFarmRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        $data = $request->validated();
        if (empty($data['animal_types'])) {
            $data['animal_types'] = null;
        }
        $farm->update($data);

        return redirect()->route('farmer.farms.show', $farm)
            ->with('status', __('Farm updated.'));
    }

    public function destroy(Request $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        $farm->delete();

        return redirect()->route('farmer.farms.index')
            ->with('status', __('Farm removed.'));
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        abort_unless(
            $request->user()->accessibleFarmerBusinessIds()->contains((int) $farm->business_id),
            403
        );
    }
}
