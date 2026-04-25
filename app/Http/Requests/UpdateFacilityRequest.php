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
            'facility_name' => ['sometimes', 'required', 'string', 'max:255'],
            'facility_type' => ['sometimes', 'required', 'string', 'max:100', Rule::in(Facility::TYPES)],
            'country_id' => ['nullable', 'integer'],
            'province_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'sector_id' => ['nullable', 'integer'],
            'cell_id' => ['nullable', 'integer'],
            'village_id' => ['nullable', 'integer'],
            // Allow names as well
            'country' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'cell' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'gps' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_issue_date' => ['nullable', 'date'],
            'license_expiry_date' => ['nullable', 'date', 'after_or_equal:license_issue_date'],
            'daily_capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'string', Rule::in(Facility::STATUSES)],
        ];
    }
}
