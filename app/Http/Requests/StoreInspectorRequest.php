<?php

namespace App\Http\Requests;

use App\Models\Inspector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInspectorRequest extends FormRequest
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
        $facilityId = (int) $this->input('facility_id');
        $businessId = (int) \App\Models\Facility::query()->whereKey($facilityId)->value('business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];

        return [
            'facility_id' => ['required', 'exists:facilities,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'max:50', 'unique:inspectors,national_id'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'cell' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'authorization_number' => ['required', 'string', 'max:100'],
            'authorization_issue_date' => ['required', 'date'],
            'authorization_expiry_date' => ['required', 'date', 'after_or_equal:authorization_issue_date'],
            'species_allowed' => ['required', 'array', 'min:1'],
            'species_allowed.*' => ['required', 'string', 'max:100', Rule::in($allowedSpecies)],
            'daily_capacity' => ['nullable', 'integer', 'min:0'],
            'stamp_serial_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(Inspector::STATUSES)],
        ];
    }
}
