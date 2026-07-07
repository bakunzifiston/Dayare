<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyInspectionReportRequest extends FormRequest
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
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'challenges' => ['nullable', 'string', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
            'inspector_signatures' => ['nullable', 'array', 'max:6'],
            'inspector_signatures.*.name' => ['nullable', 'string', 'max:255'],
            'inspector_signatures.*.attest' => ['nullable', 'boolean'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'operator_attest' => ['nullable', 'boolean'],
            'stamp_acknowledged' => ['nullable', 'boolean'],
            'submit_to_rica' => ['nullable', 'boolean'],
        ];
    }
}
