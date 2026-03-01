<?php

namespace App\Http\Requests;

use App\Models\SlaughterPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnteMortemInspectionRequest extends FormRequest
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
            'slaughter_plan_id' => ['required', 'exists:slaughter_plans,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in(SlaughterPlan::SPECIES_OPTIONS)],
            'number_examined' => ['required', 'integer', 'min:0'],
            'number_approved' => ['required', 'integer', 'min:0'],
            'number_rejected' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'inspection_date' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $approved = (int) $this->input('number_approved');
            $rejected = (int) $this->input('number_rejected');
            $examined = (int) $this->input('number_examined');
            if ($approved + $rejected > $examined) {
                $validator->errors()->add(
                    'number_approved',
                    __('Approved + Rejected cannot exceed Number Examined.')
                );
            }
            $plan = SlaughterPlan::find($this->input('slaughter_plan_id'));
            $inspectorId = $this->input('inspector_id');
            if ($plan && $inspectorId) {
                $inspector = \App\Models\Inspector::find($inspectorId);
                if ($inspector && $inspector->facility_id !== $plan->facility_id) {
                    $validator->errors()->add('inspector_id', __('Inspector must be assigned to the slaughter session facility.'));
                }
            }
        });
    }
}
