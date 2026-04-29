<?php

namespace App\Http\Controllers\mobileapi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperatorManagerRequest;
use App\Http\Requests\UpdateOperatorManagerRequest;
use App\Http\Responses\ApiJson;
use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\OperatorManager;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperatorManagerController extends Controller
{
    private function userFacilityIds(Request $request)
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())->pluck('id');
    }

    public function index(Request $request): JsonResponse
    {
        $facilityIds = $this->userFacilityIds($request);
        $operatorManagers = OperatorManager::whereIn('facility_id', $facilityIds)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($operatorManagers);
    }

    public function byBusiness(Request $request, Business $business): JsonResponse
    {
        if (! $request->user()->accessibleBusinessIds()->contains($business->id)) {
            return ApiJson::failure(__('Access denied to this business.'), [], 403);
        }

        $operatorManagers = OperatorManager::whereIn('facility_id', $business->facilities()->pluck('id'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($operatorManagers);
    }

    public function byFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $operatorManagers = OperatorManager::where('facility_id', $facility->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($operatorManagers);
    }

    public function facility(Request $request, OperatorManager $operator_manager): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($operator_manager->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $facility = $operator_manager->facility()->with('business')->first();

        return ApiJson::success($facility);
    }

    public function slaughterExecutions(Request $request, OperatorManager $operator_manager): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($operator_manager->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $planIds = \App\Models\SlaughterPlan::where('facility_id', $operator_manager->facility_id)->pluck('id');
        $executions = \App\Models\SlaughterExecution::with(['slaughterPlan:id,slaughter_date,species,facility_id'])
            ->whereIn('slaughter_plan_id', $planIds)
            ->latest('slaughter_time')
            ->paginate($request->integer('per_page', 15));

        return ApiJson::paginated($executions);
    }

    public function show(Request $request, OperatorManager $operatorManager): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($operatorManager->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return ApiJson::success($operatorManager->load('facility.business'));
    }

    public function update(UpdateOperatorManagerRequest $request, OperatorManager $operatorManager): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($operatorManager->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $data = $this->syncLocationFromDivisions($request->validated());

        $operatorManager->update($data);

        return ApiJson::success($operatorManager->fresh(), __('Operator Manager updated successfully.'));
    }

    public function store(StoreOperatorManagerRequest $request): JsonResponse
    {
        $facilityIds = $this->userFacilityIds($request);
        
        if (!$facilityIds->contains((int) $request->facility_id)) {
            return ApiJson::failure(__('Facility not found or access denied.'), [], 403);
        }

        return DB::transaction(function () use ($request) {
            $data = $this->syncLocationFromDivisions($request->validated());
            
            // 1. Create the OperatorManager record
            $operatorManager = OperatorManager::create($data);

            // 2. Create or find the User account
            $user = User::where('email', $operatorManager->email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $operatorManager->first_name . ' ' . $operatorManager->last_name,
                    'email' => $operatorManager->email,
                    'password' => Hash::make($request->password),
                ]);
            }

            // 3. Link user to the business with the operations manager role
            $facility = Facility::find($operatorManager->facility_id);
            if ($facility) {
                $user->memberBusinesses()->syncWithoutDetaching([
                    $facility->business_id => ['role' => BusinessUser::ROLE_OPERATIONS_MANAGER]
                ]);
                
                // Also assign Spatie role for consistency if the role exists
                if (\Spatie\Permission\Models\Role::where('name', BusinessUser::ROLE_OPERATIONS_MANAGER)->exists()) {
                    $user->assignRole(BusinessUser::ROLE_OPERATIONS_MANAGER);
                }
            }

            return ApiJson::success($operatorManager, __('Operator Manager created successfully with a user account.'), 201);
        });
    }

    public function destroy(Request $request, OperatorManager $operatorManager): JsonResponse
    {
        if (! $this->userFacilityIds($request)->contains($operatorManager->facility_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $operatorManager->delete();

        return ApiJson::success(null, __('Operator Manager deleted successfully.'));
    }

    private function syncLocationFromDivisions(array $data): array
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
