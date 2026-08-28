<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalIntakeRequest;
use App\Http\Requests\StoreAnteMortemInspectionRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\StoreMonthlyInspectionReportRequest;
use App\Http\Requests\UpdateCertificateRequest;
use App\Http\Requests\StoreDeliveryConfirmationRequest;
use App\Http\Requests\StorePostMortemInspectionRequest;
use App\Http\Requests\StoreSlaughterExecutionRequest;
use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Requests\ExportDeliveryConfirmationsRequest;
use App\Http\Requests\ExportTransportTripsRequest;
use App\Http\Requests\StoreTransportTripRequest;
use App\Http\Requests\StoreWarehouseStorageRequest;
use App\Http\Requests\StoreInspectorRequest;
use App\Http\Requests\UpdateAnteMortemInspectionRequest;
use App\Http\Requests\UpdateInspectorRequest;
use App\Http\Requests\UpdatePostMortemInspectionRequest;
use App\Http\Controllers\Concerns\ExportsProcessorRecords;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Models\BusinessUser;
use App\Http\Requests\UpdateAnimalIntakeRequest;
use App\Http\Requests\UpdateSlaughterExecutionRequest;
use App\Http\Requests\UpdateSlaughterPlanRequest;
use App\Http\Responses\ApiJson;
use App\Exceptions\CertificatePdfException;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Support\CertificateAnimalSelection;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\AnimalIntakeMovementPermitStorage;
use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
use App\Support\PostMortemMeatTotals;
use App\Services\Processor\CertificatePdfService;
use App\Services\Processor\ProcessorDashboardService;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportService;
use App\Services\SuperAdmin\RicaMonthlyInspectionReportSubmissionService;
use App\Models\RicaMonthlyInspectionReport;
use App\Services\Processor\ProcessorFinanceSync;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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

    private function denyIfAnteMortemOutOfScope(Request $request, AnteMortemInspection $anteMortemInspection): ?JsonResponse
    {
        return $this->denyIfPlanOutOfScope($request, (int) $anteMortemInspection->slaughter_plan_id);
    }

    private function denyIfPostMortemOutOfScope(Request $request, PostMortemInspection $postMortemInspection): ?JsonResponse
    {
        if (! $this->batchIds($request)->contains((int) $postMortemInspection->batch_id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
    }

    private function denyIfCertificateOutOfScope(Request $request, Certificate $certificate): ?JsonResponse
    {
        if (! $this->certificateIds($request)->contains((int) $certificate->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
    }

    private function denyIfBatchOutOfScope(Request $request, Batch $batch): ?JsonResponse
    {
        if (! $this->batchIds($request)->contains((int) $batch->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
    }

    private function denyIfTransportTripOutOfScope(Request $request, TransportTrip $transportTrip): ?JsonResponse
    {
        if (! $this->transportTripIds($request)->contains((int) $transportTrip->id)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        return null;
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
                'batch' => Batch::STATUSES,
            ],
            'ante_mortem_checklists' => AnteMortemChecklist::all(),
            'ante_mortem_checklist_meta' => [
                'species_aliases' => config('ante_mortem_checklist.species_aliases', []),
                'value_options' => config('ante_mortem_checklist.value_options', []),
            ],
            'post_mortem_checklists' => PostMortemChecklist::all(),
            'post_mortem_checklist_meta' => [
                'species_aliases' => config('post_mortem_checklist.species_aliases', []),
                'value_options' => config('post_mortem_checklist.value_options', []),
            ],
        ]);
    }

    public function dashboard(Request $request, ProcessorDashboardService $dashboardService): JsonResponse
    {
        $user = $request->user();
        $businessId = $user->activeProcessorBusinessId();
        $role = $user->processorRoleForBusiness($businessId);

        if ($businessId === null || $role === null) {
            return ApiJson::success([
                'business_id' => null,
                'role' => null,
                'headerBadge' => null,
                'kpiCards' => [],
                'filters' => null,
                'showPeriodFilter' => false,
            ]);
        }

        $dashboard = $dashboardService->buildForRole($businessId, $role, $user, $request);

        return ApiJson::success([
            'business_id' => $businessId,
            'role' => $role,
            'headerBadge' => $dashboard['headerBadge'] ?? null,
            'kpiCards' => $dashboard['kpiCards'] ?? [],
            'filters' => $dashboard['filters'] ?? null,
            'showPeriodFilter' => (bool) ($dashboard['showPeriodFilter'] ?? false),
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
        $permitFile = $data['movement_permit_document'] ?? null;
        $receiptFile = $data['receipt_document'] ?? null;
        unset($data['movement_permit_document'], $data['receipt_document']);

        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $data = $this->hydrateIntakeClientFields($request, $data);

        if ($permitFile) {
            $data['movement_permit_document_path'] = AnimalIntakeMovementPermitStorage::store(
                $permitFile,
                AnimalIntakeMovementPermitStorage::PERMIT_DIRECTORY,
            );
        }
        if ($receiptFile) {
            $data['receipt_document_path'] = AnimalIntakeMovementPermitStorage::store(
                $receiptFile,
                AnimalIntakeMovementPermitStorage::RECEIPT_DIRECTORY,
            );
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
        $permitFile = $data['movement_permit_document'] ?? null;
        $receiptFile = $data['receipt_document'] ?? null;
        unset($data['movement_permit_document'], $data['receipt_document']);

        $denied = $this->denyIfFacilityOutOfScope($request, (int) $data['facility_id']);
        if ($denied !== null) {
            return $denied;
        }

        $data = $this->hydrateIntakeClientFields($request, $data);

        if ($permitFile) {
            AnimalIntakeMovementPermitStorage::delete($animalIntake->movement_permit_document_path);
            $data['movement_permit_document_path'] = AnimalIntakeMovementPermitStorage::store(
                $permitFile,
                AnimalIntakeMovementPermitStorage::PERMIT_DIRECTORY,
            );
        }
        if ($receiptFile) {
            AnimalIntakeMovementPermitStorage::delete($animalIntake->receipt_document_path);
            $data['receipt_document_path'] = AnimalIntakeMovementPermitStorage::store(
                $receiptFile,
                AnimalIntakeMovementPermitStorage::RECEIPT_DIRECTORY,
            );
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

    public function animalIntakesSubmit(Request $request, AnimalIntake $animalIntake): JsonResponse
    {
        $denied = $this->denyIfIntakeOutOfScope($request, $animalIntake);
        if ($denied !== null) {
            return $denied;
        }

        if (! $animalIntake->isDraft()) {
            return ApiJson::failure(
                __('Only draft intakes can be submitted.'),
                ['is_draft' => [__('Only draft intakes can be submitted.')]],
                422,
            );
        }

        if ($animalIntake->items()->count() === 0) {
            return ApiJson::failure(
                __('Add at least one animal before submitting.'),
                ['items' => [__('Add at least one animal before submitting.')]],
                422,
            );
        }

        try {
            DB::transaction(function () use ($animalIntake): void {
                $animalIntake->update([
                    'is_draft' => false,
                    'submitted_at' => now(),
                    'status' => AnimalIntake::STATUS_APPROVED,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Animal intake submit failed', ['intake_id' => $animalIntake->id, 'exception' => $e]);

            return ApiJson::failure(__('Could not submit intake. Please try again.'), [], 500);
        }

        $animalIntake->refresh()->load(['items', 'facility:id,facility_name']);

        $eventClass = 'App\\Events\\IntakeSubmitted';
        if (class_exists($eventClass)) {
            event(new $eventClass($animalIntake));
        }

        $message = __('Submitted.');
        try {
            ProcessorFinanceSync::syncIntakePayable($animalIntake);
        } catch (\Throwable $e) {
            Log::warning('Animal intake finance sync failed', [
                'intake_id' => $animalIntake->id,
                'exception' => $e,
            ]);
            $message = __('Intake submitted but finance sync failed — please check the finance module.');
        }

        return ApiJson::success($animalIntake, $message);
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

        $slaughterExecution->load([
            'slaughterPlan:id,facility_id,slaughter_date,species',
            'slaughterPlan.facility:id,facility_name',
        ]);

        $animals = $slaughterExecution->inspectableAnimalsForPostMortem()->values()->all();
        $inspectedIds = $slaughterExecution->inspectedAnimalIntakeItemIds();
        $pendingCount = collect($animals)
            ->reject(fn (array $animal) => $inspectedIds->contains((int) $animal['animal_intake_item_id']))
            ->count();

        $payload = $slaughterExecution->toArray();
        $payload['post_mortem_inspection'] = [
            'animal_count' => count($animals),
            'pending_count' => $pendingCount,
            'has_per_animal' => count($animals) > 0,
            'source' => 'execution',
            'animals' => $animals,
            'inspected_animal_ids' => $inspectedIds->values()->all(),
            'is_complete' => $slaughterExecution->isPostMortemComplete(),
        ];

        return ApiJson::success($payload);
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
        $executionIds = $this->executionIds($request);

        $query = Batch::query()
            ->with([
                'slaughterExecution:id,slaughter_plan_id,status,slaughter_time',
                'slaughterExecution.slaughterPlan.facility:id,facility_name',
                'inspector:id,first_name,last_name',
            ])
            ->whereIn('id', $batchIds);

        $filters = $this->requestedFilters($request, [
            'slaughter_execution_id',
            'species',
            'status',
            'inspector_id',
            'cold_chain_status',
        ]);

        if (isset($filters['slaughter_execution_id'])) {
            $filteredExecutionId = (int) $filters['slaughter_execution_id'];
            if (! $executionIds->contains($filteredExecutionId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('slaughter_execution_id', $filteredExecutionId);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['inspector_id'])) {
            $query->where('inspector_id', (int) $filters['inspector_id']);
        }
        if (isset($filters['cold_chain_status'])) {
            $query->where('cold_chain_status', (string) $filters['cold_chain_status']);
        }

        $items = $query->latest('id')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function batchesShow(Request $request, Batch $batch): JsonResponse
    {
        $denied = $this->denyIfBatchOutOfScope($request, $batch);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($batch->load([
            'slaughterExecution.slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'items',
            'postMortemInspection',
            'certificates:id,batch_id,certificate_number,status,issued_at',
        ]));
    }

    public function anteMortemIndex(Request $request): JsonResponse
    {
        $planIds = $this->planIds($request);
        $query = AnteMortemInspection::query()
            ->with([
                'slaughterPlan:id,facility_id,slaughter_date,species,status',
                'slaughterPlan.facility:id,facility_name',
                'inspector:id,first_name,last_name',
            ])
            ->whereIn('slaughter_plan_id', $planIds);

        $filters = $this->requestedFilters($request, [
            'slaughter_plan_id',
            'species',
            'inspector_id',
            'inspection_date_from',
            'inspection_date_to',
        ]);

        if (isset($filters['slaughter_plan_id'])) {
            $filteredPlanId = (int) $filters['slaughter_plan_id'];
            if (! $planIds->contains($filteredPlanId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('slaughter_plan_id', $filteredPlanId);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }
        if (isset($filters['inspector_id'])) {
            $query->where('inspector_id', (int) $filters['inspector_id']);
        }
        if (isset($filters['inspection_date_from'])) {
            $query->whereDate('inspection_date', '>=', (string) $filters['inspection_date_from']);
        }
        if (isset($filters['inspection_date_to'])) {
            $query->whereDate('inspection_date', '<=', (string) $filters['inspection_date_to']);
        }

        $items = $query->latest('inspection_date')->latest('id')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function anteMortemShow(Request $request, AnteMortemInspection $anteMortemInspection): JsonResponse
    {
        $denied = $this->denyIfAnteMortemOutOfScope($request, $anteMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($anteMortemInspection->load([
            'slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'observations',
            'inspectionItems',
        ]));
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

    public function anteMortemUpdate(UpdateAnteMortemInspectionRequest $request, AnteMortemInspection $anteMortemInspection): JsonResponse
    {
        $denied = $this->denyIfAnteMortemOutOfScope($request, $anteMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        $validated = $request->validated();
        $planId = (int) ($validated['slaughter_plan_id'] ?? $anteMortemInspection->slaughter_plan_id);
        $denied = $this->denyIfPlanOutOfScope($request, $planId);
        if ($denied !== null) {
            return $denied;
        }

        $plan = SlaughterPlan::query()->find($planId);
        if ($plan === null) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $inspectorId = (int) ($validated['inspector_id'] ?? $anteMortemInspection->inspector_id);
        if (! Inspector::query()
            ->whereKey($inspectorId)
            ->where('facility_id', $plan->facility_id)
            ->where('status', 'active')
            ->exists()) {
            return ApiJson::failure(
                __('Inspector is not valid for this facility.'),
                ['inspector_id' => [__('The selected inspector must be active and assigned to this slaughter plan\'s facility.')]],
                422
            );
        }

        DB::transaction(function () use ($anteMortemInspection, $validated, $plan, $planId) {
            $previouslyRejectedItemIds = $anteMortemInspection->inspectionItems()
                ->rejected()
                ->pluck('animal_intake_item_id')
                ->toArray();

            $anteMortemInspection->update([
                'slaughter_plan_id' => $planId,
                'inspector_id' => $validated['inspector_id'] ?? $anteMortemInspection->inspector_id,
                'species' => $validated['species'],
                'number_examined' => $validated['number_examined'],
                'number_approved' => $validated['number_approved'],
                'number_rejected' => $validated['number_rejected'],
                'notes' => $validated['notes'] ?? null,
                'inspection_date' => $validated['inspection_date'],
                'notes_for_under_observation' => $validated['notes_for_under_observation'] ?? null,
            ]);

            $perAnimal = $plan->assignedItems()->where('species', $validated['species'])->exists();
            $itemOutcomes = $validated['item_outcomes'] ?? [];

            $this->syncAnteMortemObservations(
                $anteMortemInspection,
                $validated['observations'] ?? [],
                $itemOutcomes,
                $perAnimal,
            );

            $anteMortemInspection->inspectionItems()->delete();
            $this->syncAnteMortemInspectionItems($anteMortemInspection, $itemOutcomes);

            $newlyRejectedIds = collect($itemOutcomes)
                ->where('outcome', AnteMortemInspectionItem::OUTCOME_REJECTED)
                ->pluck('animal_intake_item_id')
                ->toArray();

            $removedRejectionIds = array_diff($previouslyRejectedItemIds, $newlyRejectedIds);

            if ($newlyRejectedIds !== []) {
                AnimalIntakeItem::whereIn('id', $newlyRejectedIds)
                    ->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);
            }

            if ($removedRejectionIds !== []) {
                AnimalIntakeItem::whereIn('id', $removedRejectionIds)
                    ->update(['health_status' => AnimalIntakeItem::HEALTH_OBSERVATION]);
            }
        });

        return ApiJson::success($anteMortemInspection->fresh()->load([
            'slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'observations',
            'inspectionItems',
        ]), __('Updated.'));
    }

    public function anteMortemDestroy(Request $request, AnteMortemInspection $anteMortemInspection): JsonResponse
    {
        $denied = $this->denyIfAnteMortemOutOfScope($request, $anteMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $anteMortemInspection->delete();
        } catch (QueryException $exception) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Deleted.'));
    }

    public function postMortemIndex(Request $request): JsonResponse
    {
        $batchIds = $this->batchIds($request);
        $query = PostMortemInspection::query()
            ->with([
                'batch:id,slaughter_execution_id,species,status,batch_code',
                'batch.slaughterExecution.slaughterPlan.facility:id,facility_name',
                'inspector:id,first_name,last_name',
            ])
            ->whereIn('batch_id', $batchIds);

        $filters = $this->requestedFilters($request, [
            'batch_id',
            'species',
            'inspector_id',
            'result',
            'inspection_date_from',
            'inspection_date_to',
        ]);

        if (isset($filters['batch_id'])) {
            $filteredBatchId = (int) $filters['batch_id'];
            if (! $batchIds->contains($filteredBatchId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('batch_id', $filteredBatchId);
        }
        if (isset($filters['species'])) {
            $query->where('species', (string) $filters['species']);
        }
        if (isset($filters['inspector_id'])) {
            $query->where('inspector_id', (int) $filters['inspector_id']);
        }
        if (isset($filters['result'])) {
            $query->where('result', (string) $filters['result']);
        }
        if (isset($filters['inspection_date_from'])) {
            $query->whereDate('inspection_date', '>=', (string) $filters['inspection_date_from']);
        }
        if (isset($filters['inspection_date_to'])) {
            $query->whereDate('inspection_date', '<=', (string) $filters['inspection_date_to']);
        }

        $items = $query->latest('inspection_date')->latest('id')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function postMortemShow(Request $request, PostMortemInspection $postMortemInspection): JsonResponse
    {
        $denied = $this->denyIfPostMortemOutOfScope($request, $postMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($postMortemInspection->load([
            'batch.slaughterExecution.slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'observations',
            'inspectionItems',
        ]));
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

    public function postMortemUpdate(UpdatePostMortemInspectionRequest $request, PostMortemInspection $postMortemInspection): JsonResponse
    {
        $denied = $this->denyIfPostMortemOutOfScope($request, $postMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        $validated = $request->validated();
        $batchId = (int) ($validated['batch_id'] ?? $postMortemInspection->batch_id);
        if (! $this->batchIds($request)->contains($batchId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $batch = Batch::with('slaughterExecution.slaughterPlan')->find($batchId);
        if ($batch === null) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $facilityId = $batch->slaughterExecution?->slaughterPlan?->facility_id;
        $inspectorId = (int) ($validated['inspector_id'] ?? $postMortemInspection->inspector_id);
        if ($facilityId === null || ! Inspector::query()
            ->whereKey($inspectorId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->exists()) {
            return ApiJson::failure(
                __('Inspector is not valid for this facility.'),
                ['inspector_id' => [__('The selected inspector must be active and assigned to this batch\'s facility.')]],
                422
            );
        }

        $observations = $validated['observations'] ?? [];
        $itemOutcomes = $validated['item_outcomes'] ?? [];
        $species = (string) $validated['species'];
        unset($validated['observations'], $validated['item_outcomes']);

        $perAnimal = $batch->inspectableAnimalsForPostMortem()->isNotEmpty();

        if ($perAnimal && $itemOutcomes !== []) {
            $animalsById = $batch->inspectableAnimalsForPostMortem()->keyBy('animal_intake_item_id');
            $validated = array_merge($validated, PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animalsById));
            $validated['result'] = $this->computePostMortemResultFromItems($itemOutcomes);
        } elseif (! $perAnimal && $observations !== []) {
            $validated['result'] = $this->computePostMortemResult($species, $observations);
        }

        DB::transaction(function () use ($postMortemInspection, $validated, $batch, $observations, $itemOutcomes, $species, $perAnimal) {
            $postMortemInspection->update($validated);

            if ($perAnimal && $itemOutcomes !== []) {
                $postMortemInspection->observations()->delete();
                $checkItems = PostMortemChecklist::itemsForSpecies($species);
                foreach ($itemOutcomes as $itemOutcome) {
                    $animalId = (int) ($itemOutcome['animal_intake_item_id'] ?? 0);
                    foreach (($itemOutcome['observations'] ?? []) as $itemKey => $row) {
                        $postMortemInspection->observations()->create([
                            'animal_intake_item_id' => $animalId,
                            'category' => (string) ($checkItems[$itemKey]['category'] ?? 'carcass'),
                            'item' => (string) $itemKey,
                            'value' => (string) ($row['value'] ?? ''),
                            'notes' => $row['notes'] ?? null,
                        ]);
                    }
                }

                $postMortemInspection->inspectionItems()->delete();
                foreach ($itemOutcomes as $itemOutcome) {
                    $postMortemInspection->inspectionItems()->create([
                        'batch_item_id' => $itemOutcome['batch_item_id'] ?? null,
                        'animal_intake_item_id' => $itemOutcome['animal_intake_item_id'],
                        'outcome' => $itemOutcome['outcome'],
                        'outcome_notes' => $itemOutcome['outcome_notes'] ?? null,
                        'carcass_weight_kg' => $itemOutcome['carcass_weight_kg'] ?? null,
                        'condemned_weight_kg' => $itemOutcome['condemned_weight_kg'] ?? null,
                    ]);
                }
            } elseif (! $perAnimal) {
                $postMortemInspection->observations()->delete();
                $postMortemInspection->inspectionItems()->delete();
                if ($observations !== []) {
                    $checkItems = PostMortemChecklist::itemsForSpecies($species);
                    $postMortemInspection->observations()->createMany(
                        collect($observations)->map(fn ($row, $item) => [
                            'category' => (string) ($checkItems[$item]['category'] ?? 'carcass'),
                            'item' => (string) $item,
                            'value' => (string) ($row['value'] ?? ''),
                            'notes' => $row['notes'] ?? null,
                        ])->values()->all()
                    );
                }
            }
        });

        return ApiJson::success($postMortemInspection->fresh()->load([
            'batch.slaughterExecution.slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'observations',
            'inspectionItems',
        ]), __('Updated.'));
    }

    public function postMortemDestroy(Request $request, PostMortemInspection $postMortemInspection): JsonResponse
    {
        $denied = $this->denyIfPostMortemOutOfScope($request, $postMortemInspection);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $postMortemInspection->delete();
        } catch (QueryException $exception) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Deleted.'));
    }

    public function certificatesIndex(Request $request): JsonResponse
    {
        $accessibleIds = $this->certificateIds($request);
        $batchIds = $this->batchIds($request);
        $facilityIds = $this->facilityIds($request);

        $query = Certificate::query()
            ->with([
                'batch:id,batch_code,species,status',
                'inspector:id,first_name,last_name',
                'facility:id,facility_name',
            ])
            ->whereIn('id', $accessibleIds);

        $filters = $this->requestedFilters($request, [
            'batch_id',
            'facility_id',
            'inspector_id',
            'status',
            'issued_at_from',
            'issued_at_to',
            'certificate_number',
        ]);

        if (isset($filters['batch_id'])) {
            $filteredBatchId = (int) $filters['batch_id'];
            if (! $batchIds->contains($filteredBatchId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('batch_id', $filteredBatchId);
        }
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
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['issued_at_from'])) {
            $query->whereDate('issued_at', '>=', (string) $filters['issued_at_from']);
        }
        if (isset($filters['issued_at_to'])) {
            $query->whereDate('issued_at', '<=', (string) $filters['issued_at_to']);
        }
        if (isset($filters['certificate_number'])) {
            $query->where('certificate_number', (string) $filters['certificate_number']);
        }

        $items = $query->latest('issued_at')->latest('id')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function certificatesShow(Request $request, Certificate $certificate): JsonResponse
    {
        $denied = $this->denyIfCertificateOutOfScope($request, $certificate);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($certificate->load([
            'batch.slaughterExecution.slaughterPlan.facility:id,facility_name',
            'inspector:id,first_name,last_name,facility_id',
            'facility:id,facility_name,facility_type',
            'certificateQr',
        ]));
    }

    public function certificatesQr(Request $request, Certificate $certificate): JsonResponse
    {
        $denied = $this->denyIfCertificateOutOfScope($request, $certificate);
        if ($denied !== null) {
            return $denied;
        }

        $qr = $certificate->certificateQr ?? $certificate->certificateQr()->create([
            'slug' => CertificateQr::generateSlug(),
        ]);

        $svg = (string) QrCode::format('svg')->size(200)->margin(1)->generate($qr->trace_url);

        return ApiJson::success([
            'slug' => $qr->slug,
            'trace_url' => $qr->trace_url,
            'qr_svg' => $svg,
        ]);
    }

    public function certificatesPdf(Request $request, Certificate $certificate, CertificatePdfService $certificatePdfService): JsonResponse|BaseResponse
    {
        $denied = $this->denyIfCertificateOutOfScope($request, $certificate);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $pdf = $certificatePdfService->generate($certificate);
            $fileName = $certificatePdfService->downloadFilename($certificate);
        } catch (CertificatePdfException $e) {
            return ApiJson::failure($e->getMessage(), ['certificate_pdf' => [$e->getMessage()]], 422);
        }

        return $pdf->download($fileName);
    }

    public function certificatesStore(StoreCertificateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $batchId = (int) $data['batch_id'];

        if (! $this->batchIds($request)->contains($batchId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $certificate = Certificate::create($data);

        $animalIds = collect($data['animal_intake_item_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();
        CertificateAnimalSelection::attachStoragesToCertificate($certificate, $animalIds);

        return ApiJson::success(
            $certificate->load(['batch:id,batch_code,species', 'inspector:id,first_name,last_name', 'facility:id,facility_name']),
            __('Created.'),
            201
        );
    }

    public function certificatesUpdate(UpdateCertificateRequest $request, Certificate $certificate): JsonResponse
    {
        $denied = $this->denyIfCertificateOutOfScope($request, $certificate);
        if ($denied !== null) {
            return $denied;
        }

        $batchId = (int) $request->validated('batch_id');
        if (! $this->batchIds($request)->contains($batchId)) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $certificate->update($request->validated());

        return ApiJson::success(
            $certificate->fresh()->load([
                'batch.slaughterExecution.slaughterPlan.facility:id,facility_name',
                'inspector:id,first_name,last_name,facility_id',
                'facility:id,facility_name,facility_type',
                'certificateQr',
            ]),
            __('Certificate updated successfully.'),
        );
    }

    public function certificatesDestroy(Request $request, Certificate $certificate): JsonResponse
    {
        $denied = $this->denyIfCertificateOutOfScope($request, $certificate);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $certificate->delete();
        } catch (QueryException) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Certificate removed.'));
    }

    public function transportTripsIndex(Request $request): JsonResponse
    {
        $accessibleIds = $this->transportTripIds($request);
        $certificateIds = $this->certificateIds($request);
        $facilityIds = $this->facilityIds($request);

        $query = TransportTrip::query()
            ->with([
                'certificate:id,certificate_number,status,batch_id',
                'originFacility:id,facility_name',
                'destinationFacility:id,facility_name',
            ])
            ->whereIn('id', $accessibleIds);

        $filters = $this->requestedFilters($request, [
            'certificate_id',
            'status',
            'origin_facility_id',
            'destination_facility_id',
            'departure_date_from',
            'departure_date_to',
        ]);

        if (isset($filters['certificate_id'])) {
            $filteredCertificateId = (int) $filters['certificate_id'];
            if (! $certificateIds->contains($filteredCertificateId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('certificate_id', $filteredCertificateId);
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['origin_facility_id'])) {
            $filteredOriginId = (int) $filters['origin_facility_id'];
            if (! $facilityIds->contains($filteredOriginId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('origin_facility_id', $filteredOriginId);
        }
        if (isset($filters['destination_facility_id'])) {
            $filteredDestinationId = (int) $filters['destination_facility_id'];
            if (! $facilityIds->contains($filteredDestinationId)) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }
            $query->where('destination_facility_id', $filteredDestinationId);
        }
        if (isset($filters['departure_date_from'])) {
            $query->whereDate('departure_date', '>=', (string) $filters['departure_date_from']);
        }
        if (isset($filters['departure_date_to'])) {
            $query->whereDate('departure_date', '<=', (string) $filters['departure_date_to']);
        }

        $items = $query->latest('departure_date')->latest('id')->paginate($this->perPage($request));

        return ApiJson::paginated($items, 'OK', $filters);
    }

    public function transportTripsShow(Request $request, TransportTrip $transportTrip): JsonResponse
    {
        $denied = $this->denyIfTransportTripOutOfScope($request, $transportTrip);
        if ($denied !== null) {
            return $denied;
        }

        return ApiJson::success($transportTrip->load([
            'certificate:id,certificate_number,status,batch_id,facility_id',
            'certificate.batch:id,batch_code,species',
            'originFacility:id,facility_name,facility_type',
            'destinationFacility:id,facility_name,facility_type',
            'warehouseStorage:id,warehouse_facility_id,status',
        ]));
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

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $legacyObservations
     * @param  array<int, array{animal_intake_item_id: int, observations?: array<string, array{value?: string|null, notes?: string|null}>}>  $itemOutcomes
     */
    private function syncAnteMortemObservations(
        AnteMortemInspection $inspection,
        array $legacyObservations,
        array $itemOutcomes,
        bool $perAnimal,
    ): void {
        $inspection->observations()->delete();

        if ($perAnimal) {
            $rows = [];
            foreach ($itemOutcomes as $outcome) {
                $animalId = (int) ($outcome['animal_intake_item_id'] ?? 0);
                if ($animalId === 0) {
                    continue;
                }

                $rows = array_merge(
                    $rows,
                    $this->mapAnteMortemObservationPayload($outcome['observations'] ?? [], $animalId),
                );
            }

            if ($rows !== []) {
                $inspection->observations()->createMany($rows);
            }

            return;
        }

        if ($legacyObservations !== []) {
            $inspection->observations()->createMany(
                $this->mapAnteMortemObservationPayload($legacyObservations),
            );
        }
    }

    /**
     * @param  array<int, array{animal_intake_item_id: int, outcome: string, outcome_notes?: string|null}>  $itemOutcomes
     */
    private function syncAnteMortemInspectionItems(AnteMortemInspection $inspection, array $itemOutcomes): void
    {
        if ($itemOutcomes === []) {
            $inspection->update(['examined_count_source' => AnteMortemInspection::SOURCE_MANUAL]);

            return;
        }

        foreach ($itemOutcomes as $itemOutcome) {
            $inspection->inspectionItems()->create([
                'animal_intake_item_id' => $itemOutcome['animal_intake_item_id'],
                'outcome' => $itemOutcome['outcome'],
                'outcome_notes' => $itemOutcome['outcome_notes'] ?? null,
                'conditions' => $itemOutcome['conditions'] ?? null,
                'action_taken' => $itemOutcome['action_taken'] ?? null,
            ]);
        }

        $inspection->update([
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
            'number_examined' => $inspection->examined_from_items,
            'number_approved' => $inspection->approved_from_items,
            'number_rejected' => $inspection->rejected_from_items,
        ]);
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $observations
     * @return array<int, array<string, mixed>>
     */
    private function mapAnteMortemObservationPayload(array $observations, ?int $animalIntakeItemId = null): array
    {
        return collect($observations)
            ->map(function ($row, $item) use ($animalIntakeItemId) {
                return [
                    'animal_intake_item_id' => $animalIntakeItemId,
                    'item' => (string) $item,
                    'value' => (string) ($row['value'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{outcome?: string}>  $itemOutcomes
     */
    private function computePostMortemResultFromItems(array $itemOutcomes): string
    {
        foreach ($itemOutcomes as $outcome) {
            if (($outcome['outcome'] ?? '') === PostMortemInspectionItem::OUTCOME_CONDEMNED) {
                return PostMortemInspection::RESULT_PARTIAL;
            }
        }

        foreach ($itemOutcomes as $outcome) {
            if (($outcome['outcome'] ?? '') === PostMortemInspectionItem::OUTCOME_DEFERRED) {
                return PostMortemInspection::RESULT_PARTIAL;
            }
        }

        return PostMortemInspection::RESULT_APPROVED;
    }

    /**
     * @param  array<string, array{value?: string|null}>  $observations
     */
    private function computePostMortemResult(string $species, array $observations): string
    {
        $result = PostMortemInspection::RESULT_APPROVED;

        foreach ($observations as $itemKey => $row) {
            $value = (string) ($row['value'] ?? '');
            if (! PostMortemChecklist::isAbnormalValue($value)) {
                continue;
            }
            if (PostMortemChecklist::isCriticalItem($species, (string) $itemKey)) {
                return PostMortemInspection::RESULT_REJECTED;
            }
            $result = PostMortemInspection::RESULT_PARTIAL;
        }

        return $result;
    }

    // ─── Monthly Inspection Reports ──────────────────────────────────────────────

    public function monthlyInspectionReportsIndex(Request $request): JsonResponse
    {
        $facilityIds = $this->accessibleFacilityIds($request);

        $query = RicaMonthlyInspectionReport::query()
            ->whereIn('facility_id', $facilityIds)
            ->with(['facility:id,facility_name,business_id', 'facility.business:id,business_name', 'submittedBy:id,name,email']);

        if ($facilityId = $request->integer('facility_id')) {
            if (! $facilityIds->contains($facilityId)) {
                return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
            }
            $query->where('facility_id', $facilityId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->integer('year')) {
            $query->where('period_year', $year);
        }

        if ($month = $request->integer('month')) {
            $query->where('period_month', $month);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginator = $query->orderByDesc('period_year')->orderByDesc('period_month')->paginate($perPage);

        return ApiJson::success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'filters' => [
                'facility_id' => $request->input('facility_id'),
                'status'      => $request->input('status'),
                'year'        => $request->input('year'),
                'month'       => $request->input('month'),
            ],
        ]);
    }

    public function monthlyInspectionReportsShow(
        Request $request,
        Facility $facility,
        RicaMonthlyInspectionReportService $reportService,
    ): JsonResponse {
        $denied = $this->denyIfMonthlyReportFacilityOutOfScope($request, $facility);
        if ($denied !== null) {
            return $denied;
        }

        $year  = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $report = $reportService->build($facility, $periodStart, $periodEnd);

        return ApiJson::success([
            'facility_id' => $facility->id,
            'year'        => $year,
            'month'       => $month,
            'report'      => $report,
        ]);
    }

    public function monthlyInspectionReportsClosure(
        StoreMonthlyInspectionReportRequest $request,
        Facility $facility,
        RicaMonthlyInspectionReportService $reportService,
        RicaMonthlyInspectionReportSubmissionService $submissionService,
    ): JsonResponse {
        $denied = $this->denyIfMonthlyReportFacilityOutOfScope($request, $facility);
        if ($denied !== null) {
            return $denied;
        }

        $period = $reportService->resolvePeriod($request);
        $payload = $request->validated();

        if ($request->boolean('submit_to_rica')) {
            $submission = $submissionService->submit(
                $facility,
                $period['periodStart'],
                $payload,
                $request->user(),
            );

            return ApiJson::success($submission, __('Report submitted to RICA.'));
        }

        $submission = $submissionService->saveDraft(
            $facility,
            $period['periodStart'],
            $payload,
        );

        return ApiJson::success($submission, __('Report draft saved.'));
    }

    private function denyIfMonthlyReportFacilityOutOfScope(Request $request, Facility $facility): ?JsonResponse
    {
        if (! $this->accessibleFacilityIds($request)->contains($facility->id)) {
            return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
        }

        if (
            $facility->facility_type !== Facility::TYPE_SLAUGHTERHOUSE
            && ! $facility->slaughterPlans()->exists()
        ) {
            return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
        }

        return null;
    }

    // ─── Inspectors ──────────────────────────────────────────────────────────────

    private function accessibleFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function denyIfInspectorOutOfScope(Request $request, Inspector $inspector): ?JsonResponse
    {
        if (! $this->accessibleFacilityIds($request)->contains($inspector->facility_id)) {
            return ApiJson::failure(__('Inspector not found or outside current workspace scope.'), [], 404);
        }

        return null;
    }

    public function inspectorsIndex(Request $request): JsonResponse
    {
        $facilityIds = $this->accessibleFacilityIds($request);

        $query = Inspector::query()->whereIn('facility_id', $facilityIds)->with('facility');

        if ($facilityId = $request->integer('facility_id')) {
            if (! $facilityIds->contains($facilityId)) {
                return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
            }
            $query->where('facility_id', $facilityId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return ApiJson::success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'filters' => [
                'facility_id' => $request->input('facility_id'),
                'status'      => $request->input('status'),
            ],
        ]);
    }

    public function inspectorsShow(Request $request, Inspector $inspector): JsonResponse
    {
        $denied = $this->denyIfInspectorOutOfScope($request, $inspector);
        if ($denied !== null) {
            return $denied;
        }

        $inspector->load(['facility.business']);

        return ApiJson::success($inspector);
    }

    public function inspectorsStore(StoreInspectorRequest $request): JsonResponse
    {
        $facilityId = (int) $request->validated('facility_id');
        if (! $this->accessibleFacilityIds($request)->contains($facilityId)) {
            return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
        }

        $data = $request->validated();
        if (isset($data['species_allowed']) && is_array($data['species_allowed'])) {
            $data['species_allowed'] = implode(', ', array_filter($data['species_allowed']));
        }

        $inspector = Inspector::create($data);
        $inspector->load(['facility.business']);

        return ApiJson::success($inspector, __('Inspector registered successfully.'), 201);
    }

    public function inspectorsUpdate(UpdateInspectorRequest $request, Inspector $inspector): JsonResponse
    {
        $denied = $this->denyIfInspectorOutOfScope($request, $inspector);
        if ($denied !== null) {
            return $denied;
        }

        $facilityId = (int) $request->validated('facility_id');
        if (! $this->accessibleFacilityIds($request)->contains($facilityId)) {
            return ApiJson::failure(__('Facility not found or outside current workspace scope.'), [], 404);
        }

        $data = $request->validated();
        if (array_key_exists('species_allowed', $data)) {
            $data['species_allowed'] = is_array($data['species_allowed'])
                ? implode(', ', array_filter($data['species_allowed']))
                : ($data['species_allowed'] ?? '');
        }

        $inspector->update($data);
        $inspector->load(['facility.business']);

        return ApiJson::success($inspector, __('Inspector updated successfully.'));
    }

    public function inspectorsDestroy(Request $request, Inspector $inspector): JsonResponse
    {
        $denied = $this->denyIfInspectorOutOfScope($request, $inspector);
        if ($denied !== null) {
            return $denied;
        }

        try {
            $inspector->delete();
        } catch (QueryException) {
            return ApiJson::failure(__('This record cannot be deleted because related records exist.'), [], 422);
        }

        return ApiJson::success(null, __('Inspector removed.'));
    }
}
