<?php

namespace App\Http\Requests;

use App\Enums\MeatExportDocumentType;
use App\Models\MeatExportDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeatExportDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['sometimes', 'required', Rule::in(MeatExportDocumentType::values())],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'issuing_authority' => ['sometimes', 'nullable', 'string', 'max:255'],
            'issued_date' => ['sometimes', 'nullable', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:issued_date'],
            'status' => ['sometimes', 'required', Rule::in(MeatExportDocument::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string'],
            'file' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
