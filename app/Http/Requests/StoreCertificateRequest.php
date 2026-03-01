<?php

namespace App\Http\Requests;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
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
            'facility_id' => ['required', 'exists:facilities,id'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', 'string', 'in:active,expired,revoked'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $batchId = $this->input('batch_id');
            if (! $batchId) {
                return;
            }
            $batch = Batch::with(['postMortemInspection', 'slaughterExecution.slaughterPlan'])->find($batchId);
            if (! $batch) {
                return;
            }
            if (! $batch->canIssueCertificate()) {
                $validator->errors()->add(
                    'batch_id',
                    __('Certificate is only allowed when the batch has a post-mortem inspection with approved quantity greater than zero.')
                );
            }
            if ($batch->certificate()->exists()) {
                $validator->errors()->add('batch_id', __('This batch already has a certificate.'));
            }
            $inspectorId = $this->input('inspector_id');
            $facilityId = $this->input('facility_id');
            $batchFacilityId = $batch->slaughterExecution->slaughterPlan->facility_id ?? null;
            if ($batchFacilityId !== null && (int) $facilityId !== (int) $batchFacilityId) {
                $validator->errors()->add('facility_id', __('Facility must be the batch’s slaughter facility.'));
            }
            $inspector = $inspectorId ? \App\Models\Inspector::find($inspectorId) : null;
            if ($inspector && $batchFacilityId !== null && $inspector->facility_id != $batchFacilityId) {
                $validator->errors()->add('inspector_id', __('Inspector must be assigned to the batch facility.'));
            }
        });
    }
}
