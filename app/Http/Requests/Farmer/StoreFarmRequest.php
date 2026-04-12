<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $farmerIds = $this->user()->accessibleFarmerBusinessIds()->all();

        return [
            'business_id' => ['required', 'integer', Rule::in($farmerIds)],
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'exists:administrative_divisions,id'],
            'animal_types' => ['nullable', 'array'],
            'animal_types.*' => ['string', Rule::in(\App\Support\FarmerAnimalType::ALL)],
            'status' => ['required', 'string', Rule::in(\App\Models\Farm::STATUSES)],
        ];
    }
}
