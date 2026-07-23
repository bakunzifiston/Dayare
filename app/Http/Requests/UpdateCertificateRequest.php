<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCertificateIssue;
use App\Models\Certificate;
use App\Support\CertificateAnimalSelection;
use App\Support\CertificatePdfDetails;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateRequest extends FormRequest
{
    use ValidatesCertificateIssue;

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
            ...$this->certificateIssueRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'pdf_details' => CertificatePdfDetails::normalize($this->input('pdf_details')),
        ];

        $selectedIds = collect($this->input('animal_intake_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        // Edit form does not expose animal checkboxes; keep the certificate's animals.
        if ($selectedIds->isEmpty()) {
            $certificate = $this->route('certificate');
            if ($certificate instanceof Certificate) {
                $selectedIds = CertificateAnimalSelection::certificateAnimalIds($certificate);
            }
        }

        if ($selectedIds->isNotEmpty()) {
            $merge['animal_intake_item_ids'] = $selectedIds->all();
        }

        $this->merge($merge);
    }

    public function withValidator($validator): void
    {
        $this->registerCertificateIssueValidation($validator, isCreate: false);
    }
}
