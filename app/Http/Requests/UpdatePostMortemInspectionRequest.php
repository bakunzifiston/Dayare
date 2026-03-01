<?php

namespace App\Http\Requests;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostMortemInspectionRequest extends FormRequest
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
            'batch_id' => ['required', 'exists:batches,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'total_examined' => ['required', 'integer', 'min:0'],
            'approved_quantity' => ['required', 'integer', 'min:0'],
            'condemned_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'inspection_date' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $approved = (int) $this->input('approved_quantity');
            $condemned = (int) $this->input('condemned_quantity');
            $examined = (int) $this->input('total_examined');
            if ($approved + $condemned > $examined) {
                $validator->errors()->add(
                    'approved_quantity',
                    __('Approved + Condemned cannot exceed Total Examined.')
                );
            }
            $batch = Batch::with('slaughterExecution.slaughterPlan')->find($this->input('batch_id'));
            $inspectorId = $this->input('inspector_id');
            if ($batch && $inspectorId) {
                $inspector = \App\Models\Inspector::find($inspectorId);
                $facilityId = $batch->slaughterExecution->slaughterPlan->facility_id ?? null;
                if ($inspector && $facilityId !== null && $inspector->facility_id !== $facilityId) {
                    $validator->errors()->add('inspector_id', __('Inspector must be assigned to the batch facility.'));
                }
            }
        });
    }
}
