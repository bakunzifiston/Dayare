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
use App\Http\Requests\ExportDeliveryConfirmationsRequest;
use App\Http\Requests\ExportTransportTripsRequest;
use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\StoreWarehouseStorageRequest;
use App\Http\Controllers\Concerns\ExportsProcessorRecords;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Models\BusinessUser;
use App\Http\Requests\UpdateAnimalIntakeRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
use App\Http\Responses\ApiJson;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\AnimalIntakeMovementPermitStorage;
use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileCollectionController extends Controller
{
    use ExportsProcessorRecords;
    use ScopesProcessorData;

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
    private function hydrateIntakeClientFields(Request $request, array $data): array
    {
        $facilityId = (int) ($data['facility_id'] ?? 0);
        $facilityBusinessId = (int) Facility::query()->whereKey($facilityId)->value('business_id');

        if (($data['source_type'] ?? null) !== AnimalIntake::SOURCE_TYPE_CLIENT) {
            if ($request->route('animalIntake') instanceof AnimalIntake && $request->route('animalIntake')->isSupplierSource()) {
                return $data;
            }

            abort(422, __('Supplier-sourced intakes are no longer supported.'));
        }

        $client = Client::query()
            ->whereKey((int) ($data['client_id'] ?? 0))
            ->where('is_active', true)
            ->first();
        if ((int) ($data['client_id'] ?? 0) > 0) {
            if (! $client || (int) $client->business_id !== $facilityBusinessId) {
                abort(404);
            }
            $parts = preg_split('/\s+/', trim((string) $client->name), 2) ?: [];
            $data['supplier_firstname'] = $data['supplier_firstname'] ?? ($parts[0] ?? '');
            $data['supplier_lastname'] = $data['supplier_lastname'] ?? ($parts[1] ?? '');
            $data['supplier_contact'] = $data['supplier_contact'] ?? $client->phone;
            $data['country_id'] = $data['country_id'] ?? $client->country_id;
            $data['province_id'] = $data['province_id'] ?? $client->province_id;
            $data['district_id'] = $data['district_id'] ?? $client->district_id;
            $data['sector_id'] = $data['sector_id'] ?? $client->sector_id;
            $data['cell_id'] = $data['cell_id'] ?? $client->cell_id;
            $data['village_id'] = $data['village_id'] ?? $client->village_id;
        } else {
            $data['client_id'] = null;
            $data['supplier_firstname'] = $data['manual_client_firstname'] ?? $data['supplier_firstname'] ?? null;
            $data['supplier_lastname'] = $data['manual_client_lastname'] ?? $data['supplier_lastname'] ?? null;
            $data['supplier_contact'] = $data['manual_client_contact'] ?? $data['supplier_contact'] ?? null;
        }

        $data['supplier_id'] = null;
        $data['contract_id'] = null;
        $data['farm_registration_number'] = null;
        $data['transport_vehicle_plate'] = null;
        $data['driver_name'] = null;
        $data['movement_permit_no'] = null;

        unset($data['manual_client_firstname'], $data['manual_client_lastname'], $data['manual_client_contact']);

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

    public function animalIntakesStore(StoreAnimalIntakeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $uploadedFile = $data['movement_permit_document'] ?? null;
        unset($data['movement_permit_document']);

        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $data = $this->hydrateIntakeClientFields($request, $data);

        if ($uploadedFile) {
            $data['movement_permit_document_path'] = AnimalIntakeMovementPermitStorage::store($uploadedFile);
        }

        $item = AnimalIntake::create($data);

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
        $uploadedFile = $data['movement_permit_document'] ?? null;
        unset($data['movement_permit_document']);

        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $data = $this->hydrateIntakeClientFields($request, $data);

        if ($uploadedFile) {
            AnimalIntakeMovementPermitStorage::delete($animalIntake->movement_permit_document_path);
            $data['movement_permit_document_path'] = AnimalIntakeMovementPermitStorage::store($uploadedFile);
        }

        $animalIntake->update($data);

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

        $filters = $this->requestedFilters($request, ['facility_id', 'species', 'status', 'slaughter_date_from', 'slaughter_date_to']);
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

        $hasAssignedAnimals = $plan->assignedItems()
            ->where('species', $data['species'])
            ->exists();

        $inspection = null;
        DB::transaction(function () use (&$inspection, $data, $hasAssignedAnimals) {
            $observations = $data['observations'] ?? [];
            $itemOutcomes = $data['item_outcomes'] ?? [];
            unset($data['observations'], $data['item_outcomes']);

            $inspection = AnteMortemInspection::create($data);

            if ($hasAssignedAnimals) {
                $rows = [];
                foreach ($itemOutcomes as $itemOutcome) {
                    $animalId = (int) ($itemOutcome['animal_intake_item_id'] ?? 0);
                    if ($animalId === 0) {
                        continue;
                    }

                    foreach (($itemOutcome['observations'] ?? []) as $itemKey => $row) {
                        $rows[] = [
                            'animal_intake_item_id' => $animalId,
                            'item' => (string) $itemKey,
                            'value' => (string) ($row['value'] ?? ''),
                            'notes' => $row['notes'] ?? null,
                        ];
                    }
                }

                if ($rows !== []) {
                    $inspection->observations()->createMany($rows);
                }
            } elseif ($observations !== []) {
                $inspection->observations()->createMany(
                    collect($observations)->map(fn ($row, $item) => [
                        'item' => (string) $item,
                        'value' => (string) ($row['value'] ?? ''),
                        'notes' => $row['notes'] ?? null,
                    ])->values()->all()
                );
            }

            if ($itemOutcomes !== []) {
                foreach ($itemOutcomes as $itemOutcome) {
                    $inspection->inspectionItems()->create([
                        'animal_intake_item_id' => $itemOutcome['animal_intake_item_id'],
                        'outcome' => $itemOutcome['outcome'],
                        'outcome_notes' => $itemOutcome['outcome_notes'] ?? null,
                    ]);
                }

                $inspection->update([
                    'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
                    'number_examined' => $inspection->examined_from_items,
                    'number_approved' => $inspection->approved_from_items,
                    'number_rejected' => $inspection->rejected_from_items,
                ]);

                $rejectedIds = collect($itemOutcomes)
                    ->where('outcome', AnteMortemInspectionItem::OUTCOME_REJECTED)
                    ->pluck('animal_intake_item_id');

                if ($rejectedIds->isNotEmpty()) {
                    AnimalIntakeItem::whereIn('id', $rejectedIds)
                        ->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);
                }
            }
        });

        return ApiJson::success(
            $inspection->load(['observations', 'inspectionItems']),
            __('Created.'),
            201,
        );
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
            if (! is_string($value) || trim($value) === '') {
                return ApiJson::failure(__('Invalid or missing checklist data.'), [], 422);
            }
            if (! empty($allowed) && ! in_array($value, $allowed, true)) {
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
        $data = TransportTrip::normalizeDestinationAttributes($request->validated());
        $certificateId = (int) $data['certificate_id'];

        if (! $this->certificateIds($request)->contains($certificateId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }
        $facilityIds = $this->facilityIds($request);
        if (! $facilityIds->contains((int) $data['origin_facility_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }
        if (! empty($data['destination_facility_id'])
            && ! $facilityIds->contains((int) $data['destination_facility_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
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

    public function transportTripsExport(ExportTransportTripsRequest $request): JsonResponse
    {
        if (! $request->user()->canProcessorPermission(BusinessUser::PERMISSION_EXPORT_RECORDS)) {
            return ApiJson::failure(__('Forbidden.'), [], 403);
        }

        $trips = $this->applyMobileTripFilters($request)->orderByDesc('departure_date')->get();

        return ApiJson::success($trips->toArray());
    }

    public function deliveryConfirmationsExport(ExportDeliveryConfirmationsRequest $request): JsonResponse
    {
        if (! $request->user()->canProcessorPermission(BusinessUser::PERMISSION_EXPORT_RECORDS)) {
            return ApiJson::failure(__('Forbidden.'), [], 403);
        }

        $facilityIds = $this->accessibleFacilityIds($request);
        $confirmations = $this->applyMobileConfirmationFilters(
            $this->scopedConfirmationsQuery($request)->with([
                'transportTrip.certificate',
                'receivingFacility',
                'client',
                'contract',
                'fulfillingDemand',
            ]),
            $request,
            $facilityIds
        )->orderByDesc('received_date')->get();

        return ApiJson::success($confirmations->toArray());
    }

    protected function applyMobileTripFilters(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $facilityIds = $this->accessibleFacilityIds($request);

        return $this->scopedTripsQuery($request)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('departure_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('departure_date', '<=', $request->date('to')))
            ->when(
                $request->filled('origin_facility_id') && $facilityIds->contains((int) $request->origin_facility_id),
                fn ($q) => $q->where('origin_facility_id', $request->integer('origin_facility_id'))
            )
            ->when(
                $request->filled('destination_facility_id') && $facilityIds->contains((int) $request->destination_facility_id),
                fn ($q) => $q->where('destination_facility_id', $request->integer('destination_facility_id'))
            );
    }

    protected function applyMobileConfirmationFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        Request $request,
        \Illuminate\Support\Collection $facilityIds
    ): \Illuminate\Database\Eloquent\Builder {
        $clientIds = $this->accessibleClientIds($request);

        return $query
            ->when($request->filled('confirmation_status'), fn ($q) => $q->where('confirmation_status', $request->string('confirmation_status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('received_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('received_date', '<=', $request->date('to')))
            ->when(
                $request->filled('receiving_facility_id') && $facilityIds->contains((int) $request->receiving_facility_id),
                fn ($q) => $q->where('receiving_facility_id', $request->integer('receiving_facility_id'))
            )
            ->when(
                $request->filled('client_id') && $clientIds->contains((int) $request->client_id),
                fn ($q) => $q->where('client_id', $request->integer('client_id'))
            );
    }

    public function warehouseStoragesStore(StoreWarehouseStorageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $certificateId = (int) $data['certificate_id'];
        $warehouseFacilityId = (int) $data['warehouse_facility_id'];

        $storageFacilityIds = Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
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
