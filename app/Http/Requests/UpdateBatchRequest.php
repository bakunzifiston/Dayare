<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\SlaughterExecution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
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
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'status' => ['required', 'string', Rule::in(Batch::STATUSES)],
            // --- Section 2 ---
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

        $species = SlaughterExecution::query()
            ->join('slaughter_plans', 'slaughter_plans.id', '=', 'slaughter_executions.slaughter_plan_id')
            ->where('slaughter_executions.id', $executionId)
            ->value('slaughter_plans.species');

        if ($species !== null) {
            $this->merge(['species' => $species]);
        }
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

            // --- Section 2 ---
            $execution = SlaughterExecution::find(
                $this->input('slaughter_execution_id')
                ?? $this->route('batch')?->slaughter_execution_id
            );

            if ($execution && $execution->status !== 'completed') {
                $validator->errors()->add(
                    'slaughter_execution_id',
                    'Slaughter execution must be completed before a batch can be created.'
                );
            }

            if ($execution) {
                $maxQuantity = $execution->hasPerAnimalSlaughter()
                    ? $execution->total_meat_quantity_kg
                    : (float) $execution->actual_animals_slaughtered;
                $quantity = (float) $this->input('quantity');
                if ($quantity > $maxQuantity) {
                    $validator->errors()->add(
                        'quantity',
                        "Batch quantity ({$quantity}) cannot exceed the execution total ({$maxQuantity})."
                    );
                }
            }

            if (! empty($this->input('selected_animal_ids')) && $execution) {
                $validIds = $execution->executionItems()->pluck('animal_intake_item_id')->toArray();
                $invalidIds = array_diff($this->input('selected_animal_ids'), $validIds);
                if (! empty($invalidIds)) {
                    $validator->errors()->add(
                        'selected_animal_ids',
                        'One or more selected animals do not belong to this execution.'
                    );
                }
            }

            // --- Section 2 --- UpdateBatchRequest only
            if ($this->input('status') === 'approved') {
                $batch = $this->route('batch');
                if ($batch && ! $batch->hasPostMortem()) {
                    $validator->errors()->add(
                        'status',
                        'Batch cannot be approved without a completed post-mortem inspection.'
                    );
                }
            }
        });
    }
}
