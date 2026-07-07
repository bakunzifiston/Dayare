<?php

namespace App\Http\Requests\Concerns;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\Inspector;
use App\Services\Processor\CertificatePdfService;
use App\Support\CertificateAnimalSelection;
use App\Support\CertificatePdfDetails;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesCertificateIssue
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function certificateIssueRules(): array
    {
        $certificateId = $this->route('certificate')?->id;

        return [
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'facility_id' => ['required', 'exists:facilities,id'],
            'slaughterhouse_display_name' => ['required', 'string', 'min:3', 'max:255'],
            'certificate_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('certificates', 'certificate_number')->ignore($certificateId),
            ],
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', 'string', Rule::in(Certificate::STATUSES)],
            'animal_intake_item_ids' => ['nullable', 'array', 'min:1'],
            'animal_intake_item_ids.*' => ['integer', 'exists:animal_intake_items,id'],
            ...CertificatePdfDetails::validationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'slaughter_execution_id' => __('slaughter execution'),
            'batch_id' => __('batch'),
            'inspector_id' => __('inspector'),
            'facility_id' => __('facility'),
            'slaughterhouse_display_name' => __('slaughterhouse name'),
            'certificate_number' => __('certificate number'),
            'issued_at' => __('issue date'),
            'expiry_date' => __('expiry date'),
            'pdf_details.facility_location' => __('slaughterhouse location'),
            'pdf_details.animal_names' => __('animal names'),
            'pdf_details.selling_location' => __('selling location'),
            'pdf_details.carcass_meat_kg' => __('carcass meat weight'),
            'pdf_details.other_meat_kg' => __('other meat weight'),
            'pdf_details.temperature_celsius' => __('temperature'),
        ];
    }

    protected function registerCertificateIssueValidation(Validator $validator, bool $isCreate): void
    {
        $validator->after(function (Validator $validator) use ($isCreate) {
            $this->validateCertificateIssueBusinessRules($validator, $isCreate);
        });
    }

    protected function validateCertificateIssueBusinessRules(Validator $validator, bool $isCreate): void
    {
        $batch = $this->resolveCertificateBatch();
        if ($batch === null) {
            return;
        }

        $this->validateBatchCertificateEligibility($validator, $batch, $isCreate);

        $facility = Facility::with(['districtDivision', 'sectorDivision', 'cell', 'business'])
            ->find($this->input('facility_id'));
        $inspector = Inspector::find($this->input('inspector_id'));

        $this->validateFacilityAndInspector($validator, $batch, $facility, $inspector);
        $this->validateAnimalSelection($validator, $batch);
        $this->validatePdfDetailsForIssue($validator, $batch, $facility);
    }

    protected function validateAnimalSelection(Validator $validator, Batch $batch): void
    {
        if (! $batch->hasPerAnimalData()) {
            return;
        }

        $selectedIds = collect($this->input('animal_intake_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        $error = CertificateAnimalSelection::validateSelection($batch, $selectedIds);
        if ($error !== null) {
            $validator->errors()->add('animal_intake_item_ids', $error);
        }
    }

    protected function resolveCertificateBatch(): ?Batch
    {
        $batchId = $this->input('batch_id');
        if (! $batchId) {
            return null;
        }

        return Batch::with([
            'postMortemInspection.inspectionItems',
            'slaughterExecution.slaughterPlan',
            'warehouseStorages',
            'items',
            'certificates',
            'certificate',
        ])->find($batchId);
    }

    protected function validateBatchCertificateEligibility(Validator $validator, Batch $batch, bool $isCreate): void
    {
        if ($isCreate) {
            if (! $batch->canIssueCertificate()) {
                $field = $this->filled('slaughter_execution_id') ? 'slaughter_execution_id' : 'batch_id';
                $validator->errors()->add(
                    $field,
                    $batch->certificateIssueBlockReason() ?? __('This batch is not eligible for certification.')
                );
            }

            return;
        }

        if ($batch->hasPerAnimalData() && ! $batch->isPostMortemComplete()) {
            if (! $batch->hasReleasedColdRoomStorage()) {
                $validator->errors()->add(
                    'batch_id',
                    __('All animals in this batch must have a post-mortem outcome recorded before a certificate can be issued.')
                );
            }

            return;
        }

        if (
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

            return;
        }

        if (! $batch->hasReleasedColdRoomStorage()) {
            $validator->errors()->add(
                'batch_id',
                __('Certificate can only be issued after cold room storage has been released for this batch.')
            );
        }
    }

    protected function validateFacilityAndInspector(
        Validator $validator,
        Batch $batch,
        ?Facility $facility,
        ?Inspector $inspector,
    ): void {
        $batchFacilityId = $batch->slaughterExecution->slaughterPlan->facility_id ?? null;
        $facilityId = $this->input('facility_id');

        if ($batchFacilityId !== null && (int) $facilityId !== (int) $batchFacilityId) {
            $validator->errors()->add('facility_id', __('Facility must be the batch’s slaughter facility.'));
        }

        if ($facility !== null && $facility->facility_type !== Facility::TYPE_SLAUGHTERHOUSE) {
            $validator->errors()->add(
                'facility_id',
                __('This certificate template is only valid for slaughterhouse facilities.')
            );
        }

        if ($facility !== null && ! CertificatePdfDetails::facilityLocationIsComplete($facility)) {
            $validator->errors()->add(
                'pdf_details.facility_location',
                __('Slaughterhouse location (District, Sector, Cell) must be complete before issuing a certificate.')
            );
        }

        if ($inspector !== null && $inspector->status !== 'active') {
            $validator->errors()->add('inspector_id', __('The selected inspector must be active.'));
        }

        if ($inspector && $batchFacilityId !== null && (int) $inspector->facility_id !== (int) $batchFacilityId) {
            $validator->errors()->add('inspector_id', __('Inspector must be assigned to the batch facility.'));
        }
    }

    protected function validatePdfDetailsForIssue(Validator $validator, Batch $batch, ?Facility $facility): void
    {
        $animalNames = $this->effectivePdfString($batch, $facility, 'animal_names');
        if ($animalNames === null) {
            $validator->errors()->add(
                'pdf_details.animal_names',
                __('Animal identification (ear tags / names) is required.')
            );
        }

        $sellingLocation = $this->effectivePdfString($batch, $facility, 'selling_location');
        if ($sellingLocation === null) {
            $validator->errors()->add(
                'pdf_details.selling_location',
                __('Selling location (District, Sector, Cell) is required.')
            );
        }

        $carcassKg = $this->effectivePdfValue($batch, $facility, 'carcass_meat_kg');
        if (! is_numeric($carcassKg) || (float) $carcassKg <= 0) {
            $validator->errors()->add(
                'pdf_details.carcass_meat_kg',
                __('Carcass meat weight must be greater than zero.')
            );
        }
    }

    protected function effectivePdfValue(Batch $batch, ?Facility $facility, string $key): mixed
    {
        $submitted = data_get($this->input('pdf_details'), $key);
        if ($submitted !== null && $submitted !== '') {
            return $submitted;
        }

        $defaults = app(CertificatePdfService::class)->suggestedPdfDetails($batch, $facility);

        return $defaults[$key] ?? null;
    }

    protected function effectivePdfString(Batch $batch, ?Facility $facility, string $key): ?string
    {
        $value = $this->effectivePdfValue($batch, $facility, $key);
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || $trimmed === '—') {
            return null;
        }

        return $trimmed;
    }
}
