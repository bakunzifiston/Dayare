<?php

namespace App\Http\Requests;

use App\Enums\MeatExportDocumentType;
use App\Models\MeatExportDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeatExportDocumentRequest extends FormRequest
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
            'document_type' => ['required', Rule::in(MeatExportDocumentType::values())],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'status' => ['required', Rule::in(MeatExportDocument::STATUSES)],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
