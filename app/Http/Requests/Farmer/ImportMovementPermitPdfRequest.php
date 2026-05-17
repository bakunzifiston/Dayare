<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class ImportMovementPermitPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_farm_id' => ['required', 'integer', 'exists:farms,id'],
            'permit_pdf' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'permit_pdf.mimes' => __('Please upload the official Rwanda movement permit PDF.'),
        ];
    }
}
