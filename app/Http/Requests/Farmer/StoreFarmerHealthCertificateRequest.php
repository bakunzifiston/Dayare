<?php

namespace App\Http\Requests\Farmer;

use App\Models\FarmerHealthCertificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmerHealthCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificate_number' => ['required', 'string', 'max:120', 'unique:farmer_health_certificates,certificate_number'],
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'livestock_id' => ['nullable', 'integer', 'exists:livestock,id', 'required_without:batch_reference'],
            'batch_reference' => ['nullable', 'string', 'max:100', 'required_without:livestock_id'],
            'source_health_record_id' => ['nullable', 'integer', 'exists:animal_health_records,id'],
            'certificate_type' => ['required', 'string', Rule::in(FarmerHealthCertificate::TYPES)],
            'issued_by' => ['required', 'string', 'max:150'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', 'string', Rule::in(FarmerHealthCertificate::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}

