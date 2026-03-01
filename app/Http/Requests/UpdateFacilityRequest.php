<?php

namespace App\Http\Requests;

use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityRequest extends FormRequest
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
            'facility_name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'string', 'max:100', Rule::in(Facility::TYPES)],
            'district' => ['required', 'string', 'max:100'],
            'sector' => ['required', 'string', 'max:100'],
            'gps' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_issue_date' => ['nullable', 'date'],
            'license_expiry_date' => ['nullable', 'date', 'after_or_equal:license_issue_date'],
            'daily_capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(Facility::STATUSES)],
        ];
    }
}
