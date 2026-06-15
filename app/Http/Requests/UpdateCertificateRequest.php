<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Support\CertificatePdfDetails;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateRequest extends FormRequest
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
            $batch = Batch::with(['postMortemInspection', 'slaughterExecution.slaughterPlan'])->find($batchId);
            if (! $batch) {
                return;
            }
            if ($batch->hasPerAnimalData() && ! $batch->isPostMortemComplete()) {
                if (! $batch->hasReleasedColdRoomStorage()) {
                    $validator->errors()->add(
                        'batch_id',
                        __('All animals in this batch must have a post-mortem outcome recorded before a certificate can be issued.')
                    );
                }
            } elseif (
                ! $batch->postMortemInspection
                || (
                    $batch->postMortemInspection->approved_quantity <= 0
                    && $batch->postMortemInspection->approved_from_items <= 0
                )
            ) {
                $validator->errors()->add(
                    'batch_id',
                    __('Certificate is only allowed when the batch has a post-mortem inspection with approved quantity greater than zero.')
                );
            } elseif (! $batch->hasReleasedColdRoomStorage()) {
                $validator->errors()->add(
                    'batch_id',
                    __('Certificate can only be issued after cold room storage has been released for this batch.')
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
