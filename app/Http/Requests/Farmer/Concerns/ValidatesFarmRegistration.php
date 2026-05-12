<?php

namespace App\Http\Requests\Farmer\Concerns;

use App\Models\Business;
use App\Models\Farm;
use Illuminate\Validation\Rule;

trait ValidatesFarmRegistration
{
    /** @return array<string, mixed> */
    protected function farmRegistrationRules(): array
    {
        $commercialOwnershipTypes = ['cooperative', 'company'];
        $requiresMembers = fn (): bool => in_array((string) $this->input('ownership_type'), $commercialOwnershipTypes, true);

        return [
            'owner_first_name' => ['required', 'string', 'max:255'],
            'owner_last_name' => ['required', 'string', 'max:255'],
            'owner_national_id' => ['required', 'string', 'max:50'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'owner_emergency_contact' => ['required', 'string', 'max:255'],
            'ownership_type' => ['required', 'string', Rule::in(['sole_proprietor', 'cooperative', 'company'])],
            'organization_name' => [
                Rule::requiredIf(fn () => in_array((string) $this->input('ownership_type'), ['cooperative', 'company'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'tax_id' => [
                Rule::requiredIf(fn () => in_array((string) $this->input('ownership_type'), $commercialOwnershipTypes, true)),
                'nullable',
                'string',
                'max:100',
            ],
            'owner_dob' => ['required', 'date', 'before_or_equal:today'],
            'owner_gender' => ['required', 'string', Rule::in(Business::OWNER_GENDERS)],
            'members' => [Rule::requiredIf($requiresMembers), 'nullable', 'array', 'min:1'],
            'members.*.first_name' => [Rule::requiredIf($requiresMembers), 'nullable', 'string', 'max:255'],
            'members.*.last_name' => [Rule::requiredIf($requiresMembers), 'nullable', 'string', 'max:255'],
            'members.*.date_of_birth' => [Rule::requiredIf($requiresMembers), 'nullable', 'date', 'before_or_equal:today'],
            'members.*.phone' => [Rule::requiredIf($requiresMembers), 'nullable', 'string', 'max:50'],
            'members.*.gender' => [Rule::requiredIf($requiresMembers), 'nullable', 'string', Rule::in(Business::OWNER_GENDERS)],
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'farm_size_hectares' => ['required', 'numeric', 'min:0'],
            'land_ownership_type' => ['required', 'string', Rule::in(Farm::LAND_OWNERSHIP_TYPES)],
            'registration_date' => ['required', 'date', 'before_or_equal:today'],
            'country_id' => ['nullable', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'exists:administrative_divisions,id'],
            'animal_types' => ['nullable', 'array'],
            'animal_types.*' => ['string', Rule::in(\App\Support\FarmerAnimalType::ALL)],
            'status' => ['required', 'string', Rule::in(Farm::STATUSES)],
        ];
    }
}
