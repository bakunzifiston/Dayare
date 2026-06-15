<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Support\CertificatePdfDetails;
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
            'slaughterhouse_display_name' => ['required', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', 'string', 'in:active,expired,revoked'],
            ...CertificatePdfDetails::validationRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pdf_details' => CertificatePdfDetails::normalize($this->input('pdf_details')),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $batchId = $this->input('batch_id');
            if (! $batchId) {
                return;
            }
            $batch = Batch::with([
                'postMortemInspection.inspectionItems',
                'slaughterExecution.slaughterPlan',
                'warehouseStorages',
                'items',
            ])->find($batchId);
            if (! $batch) {
                return;
            }

            if (! $batch->canIssueCertificate()) {
                $validator->errors()->add(
                    'batch_id',
                    $batch->certificateIssueBlockReason() ?? __('This batch is not eligible for certification.')
                );
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
