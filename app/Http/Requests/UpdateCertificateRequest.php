<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCertificateIssue;
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
        $this->merge([
            'pdf_details' => CertificatePdfDetails::normalize($this->input('pdf_details')),
        ]);
    }

    public function withValidator($validator): void
    {
        $this->registerCertificateIssueValidation($validator, isCreate: false);
    }
}
