<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', Rule::exists('businesses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:120', 'unique:logistics_companies,registration_number'],
            'tax_id' => ['nullable', 'string', 'max:120'],
            'license_type' => ['required', 'string', 'max:120'],
            'license_expiry_date' => ['required', 'date'],
            'operating_regions' => ['nullable', 'array'],
            'operating_regions.country_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'operating_regions.province_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'operating_regions.district_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'operating_regions.sector_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'operating_regions.cell_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'operating_regions.village_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'contact_person' => ['nullable', 'string', 'max:150'],
        ];
    }
}

