<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $executionId = (int) $this->input('slaughter_execution_id');
        $businessId = (int) \App\Models\SlaughterExecution::query()
            ->join('slaughter_plans', 'slaughter_plans.id', '=', 'slaughter_executions.slaughter_plan_id')
            ->join('facilities', 'facilities.id', '=', 'slaughter_plans.facility_id')
            ->where('slaughter_executions.id', $executionId)
            ->value('facilities.business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];
        $allowedUnits = $this->user()?->configuredUnitsForBusinessIds([$businessId])->pluck('code')->all() ?? [];

        return [
            'slaughter_execution_id' => ['required', 'exists:slaughter_executions,id'],
            'facility_id' => ['required', 'exists:facilities,id'],
            'slaughter_date' => ['required', 'date'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'status' => ['required', 'string', Rule::in(Batch::STATUSES)],
            'selected_animal_ids' => 'nullable|array',
            'selected_animal_ids.*' => 'integer|exists:animal_intake_items,id',
            'item_quantities' => 'nullable|array',
            'item_quantities.*.slaughter_execution_item_id' => 'required_with:item_quantities|integer|exists:slaughter_execution_items,id',
            'item_quantities.*.meat_quantity_kg' => 'required_with:item_quantities|numeric|min:0.01|max:9999',
            'item_quantities.*.notes' => 'nullable|string|max:500',
        ];
    }

    protected function prepareForValidation(): void
    {
        $executionId = (int) $this->input('slaughter_execution_id');
        if ($executionId <= 0) {
            return;
        }

        $execution = SlaughterExecution::query()
            ->with('slaughterPlan')
            ->find($executionId);

        if ($execution === null) {
            return;
        }

        $this->merge([
            'species' => $execution->slaughterPlan->species,
            'facility_id' => $execution->slaughterPlan->facility_id,
            'slaughter_date' => $execution->slaughter_time->toDateString(),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $execution = SlaughterExecution::with('slaughterPlan')->find($this->input('slaughter_execution_id'));
            $inspectorId = $this->input('inspector_id');
            if ($execution && $inspectorId) {
                $inspector = \App\Models\Inspector::find($inspectorId);
                if ($inspector && $inspector->facility_id !== $execution->slaughterPlan->facility_id) {
                    $validator->errors()->add('inspector_id', __('Inspector must be assigned to the facility of this slaughter execution.'));
                }
            }

            if ($execution && (int) $this->input('facility_id') !== (int) $execution->slaughterPlan->facility_id) {
                $validator->errors()->add(
                    'facility_id',
                    __('Selected facility does not match the slaughter execution facility.'),
                );
            }

            if ($execution && $this->input('slaughter_date') !== $execution->slaughter_time->toDateString()) {
                $validator->errors()->add(
                    'slaughter_date',
                    __('Selected slaughter date does not match the reference execution.'),
                );
            }

            if ($execution && $execution->status !== SlaughterExecution::STATUS_COMPLETED) {
                $validator->errors()->add(
                    'slaughter_execution_id',
                    'Slaughter execution must be completed before a batch can be created.'
                );
            }

            if (! $execution) {
                return;
            }

            $sameDayExecutions = $this->sameDayExecutions($execution);
            $sameDayExecutionIds = $sameDayExecutions->pluck('id');

            if ($sameDayExecutions->isEmpty()) {
                $validator->errors()->add(
                    'slaughter_execution_id',
                    __('No completed slaughter executions were found for the selected day and facility.'),
                );

                return;
            }

            $selectedAnimalIds = collect($this->input('selected_animal_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if ($selectedAnimalIds !== []) {
                $validIds = SlaughterExecutionItem::query()
                    ->whereIn('slaughter_execution_id', $sameDayExecutionIds)
                    ->pluck('animal_intake_item_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $invalidIds = array_diff($selectedAnimalIds, $validIds);
                if ($invalidIds !== []) {
                    $validator->errors()->add(
                        'selected_animal_ids',
                        __('One or more selected animals are not from a completed slaughter execution on this day at this facility.'),
                    );
                }

                $maxQuantity = (float) SlaughterExecutionItem::query()
                    ->whereIn('slaughter_execution_id', $sameDayExecutionIds)
                    ->whereIn('animal_intake_item_id', $selectedAnimalIds)
                    ->sum('meat_quantity_kg');
            } else {
                $maxQuantity = (float) SlaughterExecutionItem::query()
                    ->whereIn('slaughter_execution_id', $sameDayExecutionIds)
                    ->sum('meat_quantity_kg');

                if ($maxQuantity <= 0) {
                    $maxQuantity = (float) $sameDayExecutions->sum('actual_animals_slaughtered');
                }
            }

            $quantity = (float) $this->input('quantity');
            if ($quantity > $maxQuantity) {
                $validator->errors()->add(
                    'quantity',
                    __('Batch quantity (:quantity) cannot exceed the available total for this day (:max).', [
                        'quantity' => $quantity,
                        'max' => $maxQuantity,
                    ])
                );
            }

            if ($selectedAnimalIds !== []) {
                $alreadyBatchedItems = BatchItem::query()
                    ->whereIn('animal_intake_item_id', $selectedAnimalIds)
                    ->with([
                        'intakeItem:id,ear_tag',
                        'batch:id,batch_code',
                    ])
                    ->get();

                if ($alreadyBatchedItems->isNotEmpty()) {
                    $animalDescriptions = $alreadyBatchedItems
                        ->map(function (BatchItem $item) {
                            $earTag = $item->intakeItem->ear_tag ?? '#'.$item->animal_intake_item_id;
                            $batchLabel = $item->batch->batch_code ?? '#'.$item->batch_id;

                            return __(':ear_tag (batch :batch)', [
                                'ear_tag' => $earTag,
                                'batch' => $batchLabel,
                            ]);
                        })
                        ->unique()
                        ->implode(', ');

                    $validator->errors()->add(
                        'selected_animal_ids',
                        __('The following animal(s) are already in another batch: :animals.', [
                            'animals' => $animalDescriptions,
                        ])
                    );
                }
            }
        });
    }

    /**
     * @return Collection<int, SlaughterExecution>
     */
    private function sameDayExecutions(SlaughterExecution $reference): Collection
    {
        return SlaughterExecution::query()
            ->sameDayAndFacility($reference)
            ->orderBy('slaughter_time')
            ->get();
    }
}
