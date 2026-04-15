<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsComplianceDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplianceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(LogisticsComplianceDocument::TYPES)],
            'reference_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(LogisticsComplianceDocument::STATUSES)],
        ];
    }
}

