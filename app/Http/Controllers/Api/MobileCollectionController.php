<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalIntakeRequest;
use App\Http\Requests\StoreAnteMortemInspectionRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\StoreDeliveryConfirmationRequest;
use App\Http\Requests\StorePostMortemInspectionRequest;
use App\Http\Requests\StoreSlaughterExecutionRequest;
use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\StoreWarehouseStorageRequest;
use App\Http\Requests\UpdateAnimalIntakeRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
use App\Http\Responses\ApiJson;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Demand;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileCollectionController extends Controller
{
    private function facilityIds(Request $request)
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())->pluck('id');
    }

    private function planIds(Request $request)
    {
        return SlaughterPlan::whereIn('facility_id', $this->facilityIds($request))->pluck('id');
    }

    private function executionIds(Request $request)
    {
        return SlaughterExecution::whereIn('slaughter_plan_id', $this->planIds($request))->pluck('id');
    }

    private function batchIds(Request $request)
    {
        return Batch::whereIn('slaughter_execution_id', $this->executionIds($request))->pluck('id');
    }

    private function certificateIds(Request $request)
    {
        $batchIds = $this->batchIds($request);
        $facilityIds = $this->facilityIds($request);

        return Certificate::query()
            ->where(function ($query) use ($batchIds, $facilityIds) {
                $query->whereIn('batch_id', $batchIds)
                    ->orWhere(function ($q2) use ($facilityIds) {
                        $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds);
                    });
            })
            ->pluck('id');
    }

    private function transportTripIds(Request $request)
    {
        return TransportTrip::query()
            ->whereIn('certificate_id', $this->certificateIds($request))
            ->pluck('id');
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->integer('per_page', 20)));
    }

    /**
     * @param  array<int, string>  $allowedKeys
     * @return array<string, mixed>
     */
    private function requestedFilters(Request $request, array $allowedKeys): array
    {
        $filters = [];
        foreach ($allowedKeys as $key) {
            $value = $request->query($key);
            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    private function denyIfFacilityOutOfScope(Request $request, int $facilityId): ?JsonResponse
    {
        if (! $this->facilityIds($request)->contains($facilityId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
    }

    private function denyIfPlanOutOfScope(Request $request, int $planId): ?JsonResponse
    {
        if (! $this->planIds($request)->contains($planId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
    }

    private function denyIfIntakeOutOfScope(Request $request, AnimalIntake $animalIntake): ?JsonResponse
    {
        return $this->denyIfFacilityOutOfScope($request, (int) $animalIntake->facility_id);
    }

    private function denyIfSlaughterPlanOutOfScope(Request $request, SlaughterPlan $slaughterPlan): ?JsonResponse
    {
        return $this->denyIfFacilityOutOfScope($request, (int) $slaughterPlan->facility_id);
    }

    private function denyIfExecutionOutOfScope(Request $request, SlaughterExecution $slaughterExecution): ?JsonResponse
    {
        return $this->denyIfPlanOutOfScope($request, (int) $slaughterExecution->slaughter_plan_id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hydrateSupplierFields(Request $request, array $data): array
    {
        $facilityId = (int) ($data['facility_id'] ?? 0);
        $facilityBusinessId = (int) Facility::query()->whereKey($facilityId)->value('business_id');

        if (! empty($data['supplier_id'])) {
            $supplier = Supplier::find((int) $data['supplier_id']);
            if (! $supplier || ! $supplier->isApproved() || (int) $supplier->business_id !== $facilityBusinessId) {
                abort(404);
            }

            $first = $supplier->first_name ?? '';
            $last = $supplier->last_name ?? '';
            if ($first === '' && $last === '' && ! empty($supplier->name)) {
                $parts = explode(' ', (string) $supplier->name, 2);
                $first = $parts[0] ?? '';
                $last = $parts[1] ?? '';
            }

            $data['supplier_firstname'] = $data['supplier_firstname'] ?? $first;
            $data['supplier_lastname'] = $data['supplier_lastname'] ?? $last;
            $data['supplier_contact'] = $data['supplier_contact'] ?? $supplier->phone;
            $data['farm_registration_number'] = $data['farm_registration_number'] ?? $supplier->registration_number;
            $data['country_id'] = $data['country_id'] ?? $supplier->country_id;
            $data['province_id'] = $data['province_id'] ?? $supplier->province_id;
            $data['district_id'] = $data['district_id'] ?? $supplier->district_id;
            $data['sector_id'] = $data['sector_id'] ?? $supplier->sector_id;
            $data['cell_id'] = $data['cell_id'] ?? $supplier->cell_id;
            $data['village_id'] = $data['village_id'] ?? $supplier->village_id;

            $data['country'] = $data['country'] ?? $supplier->country;
            $data['province'] = $data['province'] ?? $supplier->province;
            $data['district'] = $data['district'] ?? $supplier->district;
            $data['sector'] = $data['sector'] ?? $supplier->sector;
            $data['cell'] = $data['cell'] ?? $supplier->cell;
            $data['village'] = $data['village'] ?? $supplier->village;
        }

        if (! empty($data['contract_id'])) {
            $contract = Contract::find((int) $data['contract_id']);
            if (! $contract || ! $contract->isActiveSupplierContract() || ! $request->user()->accessibleBusinessIds()->contains($contract->business_id)) {
                abort(404);
            }
        }

        return $data;
    }

    public function lookups(Request $request): JsonResponse
    {
        $facilityIds = $this->facilityIds($request);

        return ApiJson::success([
            'facilities' => Facility::whereIn('id', $facilityIds)->get(['id', 'facility_name', 'facility_type']),
            'inspectors' => Inspector::whereIn('facility_id', $facilityIds)->where('status', 'active')->get(['id', 'facility_id', 'first_name', 'last_name', 'status']),
            'species' => $request->user()->configuredSpeciesForBusinessIds($request->user()->accessibleBusinessIds())->map(function ($species) {
                return [
                    'id' => $species->id,
                    'name' => $species->name,
                    'code' => $species->code,
                ];
            })->values(),
            'statuses' => [
                'animal_intake' => AnimalIntake::STATUSES,
                'slaughter_plan' => SlaughterPlan::STATUSES,
                'slaughter_execution' => SlaughterExecution::STATUSES,
            ],
        ]);
    }

    public function suppliersIndex(Request $request): JsonResponse
    {
        $businessIds = $request->user()->accessibleBusinessIds();
        $query = Supplier::query()->whereIn('business_id', $businessIds);

        $filters = $this->requestedFilters($request, ['business_id', 'is_active', 'supplier_status', 'type', 'search']);
        if (isset($filters['business_id'])) {
            $filteredBusinessId = (int) $filters['business_id'];
            if (! $businessIds->contains($filteredBusinessId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('business_id', $filteredBusinessId);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['supplier_status'])) {
            $query->where('supplier_status', (string) $filters['supplier_status']);
        }
        if (isset($filters['type'])) {
            $query->where('type', (string) $filters['type']);
        }
        if (isset($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('last_name')->orderBy('first_name')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function suppliersStore(Request $request): JsonResponse
    {
        $businessIds = $request->user()->accessibleBusinessIds()->all();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'in:'.implode(',', $businessIds)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country_id' => ['nullable', 'integer'],
            'province_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'sector_id' => ['nullable', 'integer'],
            'cell_id' => ['nullable', 'integer'],
            'village_id' => ['nullable', 'integer'],
            'country' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:255'],
            'cell' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_status' => ['nullable', 'string', 'in:'.implode(',', array_keys(Supplier::STATUSES))],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['supplier_status'] = $validated['supplier_status'] ?? Supplier::STATUS_APPROVED;

        $item = Supplier::create($validated);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function animalIntakesIndex(Request $request): JsonResponse
    {
        $facilityIds = $this->facilityIds($request);
        $query = AnimalIntake::query()->whereIn('facility_id', $facilityIds);

        $filters = $this->requestedFilters($request, ['facility_id', 'species', 'status', 'intake_date_from', 'intake_date_to']);
        if (isset($filters['facility_id'])) {
            $filteredFacilityId = (int) $filters['facility_id'];
            if (! $facilityIds->contains($filteredFacilityId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('facility_id', $filteredFacilityId);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['intake_date_from'])) {
            $query->whereDate('intake_date', '>=', (string) $filters['intake_date_from']);
        }
        if (isset($filters['intake_date_to'])) {
            $query->whereDate('intake_date', '<=', (string) $filters['intake_date_to']);
        }

        $items = $query->latest('intake_date')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function animalIntakesByFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->facilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $query = AnimalIntake::query()->where('facility_id', $facility->id);
        $items = $query->latest('intake_date')->paginate($this->perPage($request));

        return ApiJson::paginated($items);
    }

    public function slaughterPlansByFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->facilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $query = SlaughterPlan::with(['inspector:id,first_name,last_name'])
            ->where('facility_id', $facility->id);
        
        $items = $query->latest('slaughter_date')->paginate($this->perPage($request));

        return ApiJson::paginated($items);
    }

    public function animalIntakesStore(StoreAnimalIntakeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $item = AnimalIntake::create($this->hydrateSupplierFields($request, $data));

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function animalIntakesShow(Request $request, AnimalIntake $animalIntake): JsonResponse
    {
        $denied = $this->denyIfIntakeOutOfScope($request, $animalIntake);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($animalIntake->load(['facility:id,facility_name']));
    }

    public function animalIntakesUpdate(UpdateAnimalIntakeRequest $request, AnimalIntake $animalIntake): JsonResponse
    {
        $denied = $this->denyIfIntakeOutOfScope($request, $animalIntake);
        if ($denied !== null) {
            return $denied;
        }

        $data = $request->validated();
        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $animalIntake->update($this->hydrateSupplierFields($request, $data));

        return ApiJson::success($animalIntake->fresh(), __('Updated.'));
    }

    public function animalIntakesDestroy(Request $request, AnimalIntake $animalIntake): JsonResponse
    {
        $denied = $this->denyIfIntakeOutOfScope($request, $animalIntake);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $animalIntake->delete();
        } catch (QueryException $exception) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Deleted.'));
    }

    public function slaughterPlansIndex(Request $request): JsonResponse
    {
        $facilityIds = $this->facilityIds($request);
        $query = SlaughterPlan::with(['facility:id,facility_name', 'inspector:id,first_name,last_name'])
            ->whereIn('facility_id', $facilityIds);

        $filters = $this->requestedFilters($request, ['facility_id', 'inspector_id', 'species', 'status', 'slaughter_date_from', 'slaughter_date_to']);
        if (isset($filters['facility_id'])) {
            $filteredFacilityId = (int) $filters['facility_id'];
            if (! $facilityIds->contains($filteredFacilityId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('facility_id', $filteredFacilityId);
        }
        if (isset($filters['inspector_id'])) {
            $query->where('inspector_id', (int) $filters['inspector_id']);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['slaughter_date_from'])) {
            $query->whereDate('slaughter_date', '>=', (string) $filters['slaughter_date_from']);
        }
        if (isset($filters['slaughter_date_to'])) {
            $query->whereDate('slaughter_date', '<=', (string) $filters['slaughter_date_to']);
        }

        $items = $query->latest('slaughter_date')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function slaughterPlansStore(StoreSlaughterPlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->facilityIds($request)->contains((int) $data['facility_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $item = SlaughterPlan::create($data);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function slaughterPlansShow(Request $request, SlaughterPlan $slaughterPlan): JsonResponse
    {
        $denied = $this->denyIfSlaughterPlanOutOfScope($request, $slaughterPlan);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($slaughterPlan->load([
            'facility:id,facility_name',
            'inspector:id,first_name,last_name',
            'animalIntake:id,species,number_of_animals',
        ]));
    }

    public function slaughterPlansUpdate(UpdateSlaughterPlanRequest $request, SlaughterPlan $slaughterPlan): JsonResponse
    {
        $denied = $this->denyIfSlaughterPlanOutOfScope($request, $slaughterPlan);
        if ($denied !== null) {
            return $denied;
        }

        $data = $request->validated();
        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $slaughterPlan->update($data);

        return ApiJson::success($slaughterPlan->fresh(), __('Updated.'));
    }

    public function slaughterPlansDestroy(Request $request, SlaughterPlan $slaughterPlan): JsonResponse
    {
        $denied = $this->denyIfSlaughterPlanOutOfScope($request, $slaughterPlan);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $slaughterPlan->delete();
        } catch (QueryException $exception) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Deleted.'));
    }

    public function slaughterExecutionsIndex(Request $request): JsonResponse
    {
        $planIds = $this->planIds($request);
        $query = SlaughterExecution::with(['slaughterPlan.facility:id,facility_name'])
            ->whereIn('slaughter_plan_id', $planIds);

        $filters = $this->requestedFilters($request, ['slaughter_plan_id', 'status', 'slaughter_time_from', 'slaughter_time_to']);
        if (isset($filters['slaughter_plan_id'])) {
            $filteredPlanId = (int) $filters['slaughter_plan_id'];
            if (! $planIds->contains($filteredPlanId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('slaughter_plan_id', $filteredPlanId);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['slaughter_time_from'])) {
            $query->whereDate('slaughter_time', '>=', (string) $filters['slaughter_time_from']);
        }
        if (isset($filters['slaughter_time_to'])) {
            $query->whereDate('slaughter_time', '<=', (string) $filters['slaughter_time_to']);
        }

        $items = $query->latest('slaughter_time')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function slaughterExecutionsByFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->facilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');
        $items = SlaughterExecution::with(['slaughterPlan:id,slaughter_date,species'])
            ->whereIn('slaughter_plan_id', $planIds)
            ->latest('slaughter_time')
            ->paginate($this->perPage($request));

        return ApiJson::paginated($items);
    }

    public function slaughterExecutionsStore(StoreSlaughterExecutionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $denied = $this->denyIfPlanOutOfScope($request, (int) $data['slaughter_plan_id']);
        if ($denied !== null) {
            return $denied;
        }

        $item = SlaughterExecution::create($data);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function slaughterExecutionsShow(Request $request, SlaughterExecution $slaughterExecution): JsonResponse
    {
        $denied = $this->denyIfExecutionOutOfScope($request, $slaughterExecution);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($slaughterExecution->load([
            'slaughterPlan:id,facility_id,slaughter_date,species',
            'slaughterPlan.facility:id,facility_name',
        ]));
    }

    public function slaughterExecutionsUpdate(UpdateSlaughterExecutionRequest $request, SlaughterExecution $slaughterExecution): JsonResponse
    {
        $denied = $this->denyIfExecutionOutOfScope($request, $slaughterExecution);
        if ($denied !== null) {
            return $denied;
        }

        $data = $request->validated();
        $denied = $this->denyIfPlanOutOfScope($request, (int) $data['slaughter_plan_id']);
        if ($denied !== null) {
            return $denied;
        }

        $slaughterExecution->update($data);

        return ApiJson::success($slaughterExecution->fresh(), __('Updated.'));
    }

    public function slaughterExecutionsDestroy(Request $request, SlaughterExecution $slaughterExecution): JsonResponse
    {
        $denied = $this->denyIfExecutionOutOfScope($request, $slaughterExecution);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $slaughterExecution->delete();
        } catch (QueryException $exception) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Deleted.'));
    }

    public function batchesIndex(Request $request): JsonResponse
    {
        $batchIds = $this->batchIds($request);
        $query = Batch::with(['slaughterExecution.slaughterPlan.facility:id,facility_name', 'inspector:id,first_name,last_name'])
            ->whereIn('id', $batchIds);

        $filters = $this->requestedFilters($request, ['slaughter_execution_id', 'status', 'species']);
        if (isset($filters['slaughter_execution_id'])) {
            $query->where('slaughter_execution_id', (int) $filters['slaughter_execution_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }

        $items = $query->latest()->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function batchesByFacility(Request $request, Facility $facility): JsonResponse
    {
        if (! $this->facilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $planIds = SlaughterPlan::where('facility_id', $facility->id)->pluck('id');
        $executionIds = SlaughterExecution::whereIn('slaughter_plan_id', $planIds)->pluck('id');
        
        $items = Batch::with(['slaughterExecution.slaughterPlan:id,slaughter_date,species'])
            ->whereIn('slaughter_execution_id', $executionIds)
            ->latest()
            ->paginate($this->perPage($request));

        return ApiJson::paginated($items);
    }

    public function batchesStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slaughter_execution_id' => ['required', 'integer', 'exists:slaughter_executions,id'],
            'inspector_id' => ['required', 'integer', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'quantity_unit' => ['required', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Batch::STATUSES)],
            'batch_code' => ['nullable', 'string', 'max:50'],
        ]);

        $execution = SlaughterExecution::find($validated['slaughter_execution_id']);
        if (! $this->planIds($request)->contains($execution->slaughter_plan_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $validated['status'] = $validated['status'] ?? Batch::STATUS_PENDING;
        $item = Batch::create($validated);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function anteMortemStore(StoreAnteMortemInspectionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $denied = $this->denyIfPlanOutOfScope($request, (int) $data['slaughter_plan_id']);
        if ($denied !== null) {
            return $denied;
        }

        $plan = SlaughterPlan::query()->find($data['slaughter_plan_id']);
        if ($plan === null) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        if (! Inspector::query()
            ->whereKey((int) $data['inspector_id'])
            ->where('facility_id', $plan->facility_id)
            ->where('status', 'active')
            ->exists()) {
            return ApiJson::failure(
                __('Inspector is not valid for this facility.'),
                ['inspector_id' => [__('The selected inspector must be active and assigned to this slaughter plan\'s facility.')]],
                422
            );
        }

        $items = AnteMortemChecklist::itemsForSpecies($data['species']);
        foreach ($items as $itemKey => $meta) {
            $value = $data['observations'][$itemKey]['value'] ?? null;
            $allowed = AnteMortemChecklist::allowedValuesForItem($data['species'], (string) $itemKey);
            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                return ApiJson::failure(__('Invalid or missing checklist data.'), [], 422);
            }
        }

        $inspection = null;
        DB::transaction(function () use (&$inspection, $data) {
            $observations = $data['observations'];
            unset($data['observations']);

            $inspection = AnteMortemInspection::create($data);
            $inspection->observations()->createMany(
                collect($observations)->map(fn ($row, $item) => [
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ])->values()->all()
            );
        });

        return ApiJson::success($inspection->load('observations'), __('Created.'), 201);
    }

    public function postMortemStore(StorePostMortemInspectionRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->batchIds($request)->contains((int) $data['batch_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $batch = Batch::query()
            ->with(['slaughterExecution.slaughterPlan'])
            ->find($data['batch_id']);
        $facilityId = $batch?->slaughterExecution?->slaughterPlan?->facility_id;
        if ($facilityId === null) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        if (! Inspector::query()
            ->whereKey((int) $data['inspector_id'])
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->exists()) {
            return ApiJson::failure(
                __('Inspector is not valid for this facility.'),
                ['inspector_id' => [__('The selected inspector must be active and assigned to this batch\'s facility.')]],
                422
            );
        }

        $items = PostMortemChecklist::itemsForSpecies($data['species']);
        foreach ($items as $itemKey => $meta) {
            $value = $data['observations'][$itemKey]['value'] ?? null;
            $allowed = PostMortemChecklist::allowedValuesForItem($data['species'], (string) $itemKey);
            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                return ApiJson::failure(__('Invalid or missing checklist data.'), [], 422);
            }
        }

        $result = PostMortemInspection::RESULT_APPROVED;
        foreach ($data['observations'] as $itemKey => $row) {
            $value = (string) ($row['value'] ?? '');
            if (! PostMortemChecklist::isAbnormalValue($value)) {
                continue;
            }
            if (PostMortemChecklist::isCriticalItem($data['species'], (string) $itemKey)) {
                $result = PostMortemInspection::RESULT_REJECTED;
                break;
            }
            $result = PostMortemInspection::RESULT_PARTIAL;
        }

        $inspection = null;
        DB::transaction(function () use (&$inspection, $data, $result) {
            $observations = $data['observations'];
            unset($data['observations']);
            $data['result'] = $result;

            $inspection = PostMortemInspection::create($data);
            $checkItems = PostMortemChecklist::itemsForSpecies($data['species']);
            $inspection->observations()->createMany(
                collect($observations)->map(fn ($row, $item) => [
                    'category' => (string) ($checkItems[$item]['category'] ?? 'carcass'),
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ])->values()->all()
            );
        });

        return ApiJson::success($inspection->load('observations'), __('Created.'), 201);
    }

    public function certificatesStore(StoreCertificateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $batchId = (int) $data['batch_id'];

        if (! $this->batchIds($request)->contains($batchId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $certificate = Certificate::create($data);

        return ApiJson::success(
            $certificate->load(['batch:id,batch_code,species', 'inspector:id,first_name,last_name', 'facility:id,facility_name']),
            __('Created.'),
            201
        );
    }

    public function transportTripsStore(StoreTransportTripRequest $request): JsonResponse
    {
        $data = $request->validated();
        $certificateId = (int) $data['certificate_id'];

        if (! $this->certificateIds($request)->contains($certificateId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }
        $facilityIds = $this->facilityIds($request);
        if (! $facilityIds->contains((int) $data['origin_facility_id']) ||
            ! $facilityIds->contains((int) $data['destination_facility_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $warehouseStorageId = (int) ($data['warehouse_storage_id'] ?? 0);
        if ($warehouseStorageId > 0) {
            $warehouseStorage = WarehouseStorage::query()->find($warehouseStorageId);
            if (! $warehouseStorage || ! $this->certificateIds($request)->contains((int) $warehouseStorage->certificate_id)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            if ($warehouseStorage->status !== WarehouseStorage::STATUS_RELEASED) {
                return ApiJson::failure(
                    __('Cannot transport: storage must be released first.'),
                    ['warehouse_storage_id' => [__('Cannot transport: storage must be released first.')]],
                    422
                );
            }
        }

        $trip = TransportTrip::query()->create($data);

        return ApiJson::success(
            $trip->load(['certificate:id,certificate_number,status', 'originFacility:id,facility_name', 'destinationFacility:id,facility_name']),
            __('Created.'),
            201
        );
    }

    public function deliveryConfirmationsStore(StoreDeliveryConfirmationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $transportTripId = (int) $data['transport_trip_id'];
        if (! $this->transportTripIds($request)->contains($transportTripId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $receivingFacilityId = (int) ($data['receiving_facility_id'] ?? 0);
        if ($receivingFacilityId > 0 && ! $this->facilityIds($request)->contains($receivingFacilityId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $clientId = (int) ($data['client_id'] ?? 0);
        if ($clientId > 0) {
            $client = Client::query()->find($clientId);
            if (! $client || ! $request->user()->accessibleBusinessIds()->contains((int) $client->business_id)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            if (! (bool) $client->is_active) {
                return ApiJson::failure(
                    __('Deliveries can only be created for active customers.'),
                    ['client_id' => [__('Deliveries can only be created for active customers.')]],
                    422
                );
            }
        }

        $contractId = (int) ($data['contract_id'] ?? 0);
        if ($contractId > 0) {
            $contract = Contract::query()->find($contractId);
            if (! $contract || ! $request->user()->accessibleBusinessIds()->contains((int) $contract->business_id)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
        }

        $confirmation = DeliveryConfirmation::query()->create($data);

        return ApiJson::success(
            $confirmation->load(['transportTrip:id,vehicle_plate_number,status', 'receivingFacility:id,facility_name', 'client:id,name']),
            __('Created.'),
            201
        );
    }

    public function warehouseStoragesStore(StoreWarehouseStorageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $certificateId = (int) $data['certificate_id'];
        $warehouseFacilityId = (int) $data['warehouse_facility_id'];

        $storageFacilityIds = Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_COLD_ROOM)
            ->pluck('id');

        if (! $storageFacilityIds->contains($warehouseFacilityId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $certificateIds = WarehouseStorage::accessibleCertificateIds($request);
        if (! $certificateIds->contains($certificateId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $certificate = Certificate::query()->find($certificateId);
        if ($certificate === null) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }
        if ($certificate->status !== Certificate::STATUS_ACTIVE) {
            return ApiJson::failure(
                __('Cannot store: certificate must be active.'),
                ['certificate_id' => [__('Cannot store: certificate must be active.')]],
                422
            );
        }
        if (WarehouseStorage::query()
            ->where('certificate_id', $certificateId)
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->exists()) {
            return ApiJson::failure(
                __('This batch is already in storage.'),
                ['certificate_id' => [__('This batch is already in storage.')]],
                422
            );
        }

        $allowedUnits = $request->user()
            ->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds())
            ->pluck('code')
            ->all();
        $allowedUnits = empty($allowedUnits)
            ? array_keys(Demand::QUANTITY_UNITS)
            : array_values(array_unique(array_merge($allowedUnits, array_keys(Demand::QUANTITY_UNITS))));
        if (! in_array((string) $data['quantity_unit'], $allowedUnits, true)) {
            return ApiJson::failure(
                __('Invalid quantity unit.'),
                ['quantity_unit' => [__('The selected quantity unit is invalid.')]],
                422
            );
        }

        $data['batch_id'] = $certificate->batch_id;
        $data['status'] = WarehouseStorage::STATUS_IN_STORAGE;

        $storage = WarehouseStorage::query()->create($data);

        return ApiJson::success(
            $storage->load(['warehouseFacility:id,facility_name', 'batch:id,batch_code,species', 'certificate:id,certificate_number,status']),
            __('Created.'),
            201
        );
    }
}
