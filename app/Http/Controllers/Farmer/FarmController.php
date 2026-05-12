<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmRequest;
use App\Http\Requests\Farmer\UpdateFarmRequest;
use App\Models\Business;
use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farmQuery = Farm::query()->whereIn('business_id', $farmerIds);
        $farmIds = (clone $farmQuery)->pluck('id');

        $metrics = [
            'total_farms' => (int) (clone $farmQuery)->count(),
            'active_farms' => (int) (clone $farmQuery)->where('status', Farm::STATUS_ACTIVE)->count(),
            'livestock_groups' => (int) Livestock::query()->whereIn('farm_id', $farmIds)->count(),
            'total_headcount' => (int) Livestock::query()->whereIn('farm_id', $farmIds)->sum('total_quantity'),
        ];

        $farms = $farmQuery
            ->with(['business', 'district', 'sector', 'village'])
            ->withCount('livestock')
            ->withSum('livestock as total_headcount', 'total_quantity')
            ->withSum('livestock as available_headcount', 'available_quantity')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('farmer.farms.index', compact('farms', 'metrics'));
    }

    public function create(Request $request): View
    {
        $farmerBusinesses = $request->user()
            ->accessibleBusinesses()
            ->where('type', Business::TYPE_FARMER)
            ->with('ownershipMembers')
            ->orderBy('business_name')
            ->get();

        $selectedBusinessId = (int) old('business_id', $farmerBusinesses->first()?->id);
        $selectedBusiness = $farmerBusinesses->firstWhere('id', $selectedBusinessId);

        return view('farmer.farms.create', compact('farmerBusinesses', 'selectedBusiness'));
    }

    public function store(StoreFarmRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $business = Business::query()->findOrFail($data['business_id']);
        $this->syncBusinessOwnerProfile($business, $data);

        $farmData = $this->farmAttributesFromValidated($data);
        if (empty($farmData['animal_types'])) {
            $farmData['animal_types'] = null;
        }

        Farm::create($farmData);

        return redirect()->route('farmer.farms.index')
            ->with('status', __('Farm created.'));
    }

    public function show(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $farm->load(['livestock', 'business.ownershipMembers']);

        return view('farmer.farms.show', compact('farm'));
    }

    public function edit(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $farm->load(['business.ownershipMembers']);

        return view('farmer.farms.edit', compact('farm'));
    }

    public function update(UpdateFarmRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        $data = $request->validated();
        $this->syncBusinessOwnerProfile($farm->business, $data);

        $farmData = $this->farmAttributesFromValidated($data);
        unset($farmData['business_id']);
        if (empty($farmData['animal_types'])) {
            $farmData['animal_types'] = null;
        }

        $farm->update($farmData);

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

    private function syncBusinessOwnerProfile(Business $business, array $data): void
    {
        $payload = [
            'owner_first_name' => $data['owner_first_name'],
            'owner_last_name' => $data['owner_last_name'],
            'owner_national_id' => $data['owner_national_id'],
            'contact_phone' => $data['contact_phone'],
            'email' => $data['email'],
            'owner_emergency_contact' => $data['owner_emergency_contact'],
            'ownership_type' => $data['ownership_type'],
            'owner_dob' => $data['owner_dob'],
            'owner_gender' => $data['owner_gender'],
            'tax_id' => $data['tax_id'] ?? null,
        ];

        if (in_array($data['ownership_type'], ['cooperative', 'company'], true)) {
            $payload['business_name'] = $data['organization_name'];
        }

        $business->update($payload);
        $this->syncBusinessOwnershipMembers($business, $data);
    }

    private function syncBusinessOwnershipMembers(Business $business, array $data): void
    {
        $business->ownershipMembers()->delete();

        if (! in_array($data['ownership_type'], ['cooperative', 'company'], true)) {
            return;
        }

        foreach (array_values($data['members'] ?? []) as $index => $member) {
            $business->ownershipMembers()->create([
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'date_of_birth' => $member['date_of_birth'] ?? null,
                'gender' => $member['gender'] ?? null,
                'phone' => $member['phone'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function farmAttributesFromValidated(array $data): array
    {
        return [
            'business_id' => $data['business_id'] ?? null,
            'name' => $data['name'],
            'registration_number' => $data['registration_number'],
            'country_id' => $data['country_id'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'sector_id' => $data['sector_id'] ?? null,
            'cell_id' => $data['cell_id'] ?? null,
            'village_id' => $data['village_id'] ?? null,
            'gps_latitude' => $data['gps_latitude'] ?? null,
            'gps_longitude' => $data['gps_longitude'] ?? null,
            'farm_size_hectares' => $data['farm_size_hectares'],
            'land_ownership_type' => $data['land_ownership_type'],
            'registration_date' => $data['registration_date'],
            'animal_types' => $data['animal_types'] ?? null,
            'status' => $data['status'],
        ];
    }
}
