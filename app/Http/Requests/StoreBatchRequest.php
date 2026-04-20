<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\SlaughterExecution;
use Illuminate\Foundation\Http\FormRequest;
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
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'quantity' => ['required', 'integer', 'min:1'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'status' => ['required', 'string', Rule::in(Batch::STATUSES)],
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
        });
    }
}
