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
        return [
            'slaughter_execution_id' => ['required', 'exists:slaughter_executions,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in(Batch::SPECIES_OPTIONS)],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(Batch::STATUSES)],
        ];
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
