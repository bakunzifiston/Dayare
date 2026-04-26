<?php

namespace App\Http\Controllers\mobileapi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInspectorRequest;
use App\Http\Requests\UpdateInspectorRequest;
use App\Http\Responses\ApiJson;
use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InspectorController extends Controller
{
    private function userFacilityIds(Request $request)
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())->pluck('id');
    }

    public function index(Request $request): JsonResponse
    {
        $facilityIds = $this->userFacilityIds($request);
        $inspectors = Inspector::whereIn('facility_id', $facilityIds)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($inspectors);
    }

    public function byBusiness(Request $request, Business $business): JsonResponse
    {
        if (! $request->user()->accessibleBusinessIds()->contains($business->id)) {
            return ApiJson::failure(__('Access denied to this business.'), [], 403);
        }

        $inspectors = Inspector::whereIn('facility_id', $business->facilities()->pluck('id'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($inspectors);
    }

    public function byFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $inspectors = Inspector::where('facility_id', $facility->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($inspectors);
    }

    public function show(Request $request, Inspector $inspector): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return ApiJson::success($inspector->load('facility.business'));
    }

    public function update(UpdateInspectorRequest $request, Inspector $inspector): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $data = $this->syncInspectorLocationFromDivisions($request->validated());

        if (isset($data['species_allowed']) && is_array($data['species_allowed'])) {
            $data['species_allowed'] = implode(', ', $data['species_allowed']);
        }

        $inspector->update($data);

        return ApiJson::success($inspector->fresh(), __('Inspector updated successfully.'));
    }

    public function store(StoreInspectorRequest $request): JsonResponse
    {
        $facilityIds = $this->userFacilityIds($request);
        
        if (!$facilityIds->contains((int) $request->facility_id)) {
            return ApiJson::failure(__('Facility not found or access denied.'), [], 403);
        }

        return DB::transaction(function () use ($request) {
            $data = $this->syncInspectorLocationFromDivisions($request->validated());
            
            // Handle species_allowed array to string conversion
            if (isset($data['species_allowed']) && is_array($data['species_allowed'])) {
                $data['species_allowed'] = implode(', ', $data['species_allowed']);
            }

            // 1. Create the Inspector record
            $inspector = Inspector::create($data);

            // 2. Create or find the User account
            $user = User::where('email', $inspector->email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $inspector->first_name . ' ' . $inspector->last_name,
                    'email' => $inspector->email,
                    'password' => Hash::make($request->password ?? 'password123'),
                ]);
            }

            // 3. Link user to the business with the inspector role
            $facility = Facility::find($inspector->facility_id);
            if ($facility) {
                $user->memberBusinesses()->syncWithoutDetaching([
                    $facility->business_id => ['role' => BusinessUser::ROLE_INSPECTOR]
                ]);
                
                // Also assign Spatie role for consistency if the role exists
                if (\Spatie\Permission\Models\Role::where('name', BusinessUser::ROLE_INSPECTOR)->exists()) {
                    $user->assignRole(BusinessUser::ROLE_INSPECTOR);
                }
            }

            return ApiJson::success($inspector, __('Inspector created successfully with a user account.'), 201);
        });
    }

    public function slaughterPlans(Request $request, Inspector $inspector): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $plans = \App\Models\SlaughterPlan::with(['facility:id,facility_name', 'inspector:id,first_name,last_name'])
            ->where('inspector_id', $inspector->id)
            ->latest('slaughter_date')
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($plans);
    }

    public function postMortemInspections(Request $request, Inspector $inspector): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $inspections = \App\Models\PostMortemInspection::with(['batch:id,batch_code,species', 'inspector:id,first_name,last_name'])
            ->where('inspector_id', $inspector->id)
            ->latest('inspection_date')
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($inspections);
    }

    public function anteMortemInspections(Request $request, Inspector $inspector): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $inspections = \App\Models\AnteMortemInspection::with(['slaughterPlan.facility:id,facility_name', 'inspector:id,first_name,last_name'])
            ->where('inspector_id', $inspector->id)
            ->latest('inspection_date')
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($inspections);
    }

    private function syncInspectorLocationFromDivisions(array $data): array
    {
        if (! empty($data['country_id']) && is_numeric($data['country_id'])) {
            $data['country'] = AdministrativeDivision::find($data['country_id'])?->name ?? $data['country'] ?? '';
            $data['district'] = isset($data['district_id']) && is_numeric($data['district_id']) ? (AdministrativeDivision::find($data['district_id'])?->name ?? $data['district'] ?? '') : ($data['district'] ?? '');
            $data['sector'] = isset($data['sector_id']) && is_numeric($data['sector_id']) ? (AdministrativeDivision::find($data['sector_id'])?->name ?? $data['sector'] ?? '') : ($data['sector'] ?? '');
            $data['cell'] = isset($data['cell_id']) && is_numeric($data['cell_id']) ? (AdministrativeDivision::find($data['cell_id'])?->name ?? $data['cell'] ?? null) : ($data['cell'] ?? null);
            $data['village'] = isset($data['village_id']) && is_numeric($data['village_id']) ? (AdministrativeDivision::find($data['village_id'])?->name ?? $data['village'] ?? null) : ($data['village'] ?? null);
        }

        return $data;
    }
}
