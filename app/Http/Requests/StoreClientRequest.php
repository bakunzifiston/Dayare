<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'business_id' => ['required', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'business_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(\App\Models\Client::BUSINESS_TYPES))],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'preferred_facility_id' => ['nullable', 'exists:facilities,id'],
            'preferred_species' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
