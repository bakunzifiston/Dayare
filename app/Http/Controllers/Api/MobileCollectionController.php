<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSlaughterPlanRequest;
use App\Http\Responses\ApiJson;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
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
        $items = AnimalIntake::whereIn('facility_id', $this->facilityIds($request))
            ->latest('intake_date')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiJson::paginated($items);
    }

    public function animalIntakesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'exists:facilities,id'],
            'intake_date' => ['required', 'date'],
            'species' => ['required', 'string', 'max:50'],
            'number_of_animals' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:received,approved,rejected'],
            'supplier_firstname' => ['required', 'string', 'max:100'],
            'supplier_lastname' => ['required', 'string', 'max:100'],
            'supplier_contact' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $this->facilityIds($request)->contains((int) $data['facility_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $item = AnimalIntake::create($data + [
            'farm_name' => $request->input('farm_name'),
            'animal_identification_numbers' => $request->input('animal_identification_numbers'),
        ]);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function slaughterPlansIndex(Request $request): JsonResponse
    {
        $items = SlaughterPlan::with(['facility:id,facility_name', 'inspector:id,first_name,last_name'])
            ->whereIn('facility_id', $this->facilityIds($request))
            ->latest('slaughter_date')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiJson::paginated($items);
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

    public function slaughterExecutionsIndex(Request $request): JsonResponse
    {
        $items = SlaughterExecution::with(['slaughterPlan.facility:id,facility_name'])
            ->whereIn('slaughter_plan_id', $this->planIds($request))
            ->latest('slaughter_time')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiJson::paginated($items);
    }

    public function slaughterExecutionsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slaughter_plan_id' => ['required', 'exists:slaughter_plans,id'],
            'actual_animals_slaughtered' => ['required', 'integer', 'min:0'],
            'slaughter_time' => ['required', 'date'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
        ]);

        if (! $this->planIds($request)->contains((int) $data['slaughter_plan_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
        }

        $item = SlaughterExecution::create($data);

        return ApiJson::success($item, __('Created.'), 201);
    }

    public function anteMortemStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slaughter_plan_id' => ['required', 'exists:slaughter_plans,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'inspection_date' => ['required', 'date'],
            'species' => ['required', 'string', 'max:50'],
            'number_examined' => ['required', 'integer', 'min:0'],
            'number_approved' => ['required', 'integer', 'min:0'],
            'number_rejected' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'observations' => ['required', 'array'],
        ]);

        if (($data['number_approved'] + $data['number_rejected']) > $data['number_examined']) {
            return ApiJson::failure(__('Approved + Rejected cannot exceed Number Examined.'), [], 422);
        }

        if (! $this->planIds($request)->contains((int) $data['slaughter_plan_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
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

    public function postMortemStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50'],
            'inspection_date' => ['required', 'date'],
            'total_examined' => ['required', 'integer', 'min:0'],
            'approved_quantity' => ['required', 'integer', 'min:0'],
            'condemned_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'observations' => ['required', 'array'],
        ]);

        if (($data['approved_quantity'] + $data['condemned_quantity']) > $data['total_examined']) {
            return ApiJson::failure(__('Approved + Condemned cannot exceed Total Examined.'), [], 422);
        }

        if (! $this->batchIds($request)->contains((int) $data['batch_id'])) {
            return ApiJson::failure(__('Not found.'), [], 404);
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
}
