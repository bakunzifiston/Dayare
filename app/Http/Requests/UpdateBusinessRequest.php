<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
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
        /** @var Business $business */
        $business = $this->route('business');

        return [
            // Business info
            'business_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique('businesses', 'registration_number')->ignore($business->id)],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', 'string', Rule::in(Business::STATUSES)],
            // Ownership info
            'owner_first_name' => ['required', 'string', 'max:255'],
            'owner_last_name' => ['required', 'string', 'max:255'],
            'owner_dob' => ['nullable', 'date', 'before:today'],
            'owner_gender' => ['nullable', 'string', Rule::in(Business::OWNER_GENDERS)],
            'owner_pwd_status' => ['nullable', 'string', Rule::in(Business::OWNER_PWD_STATUSES)],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'ownership_type' => ['nullable', 'string', 'max:100', Rule::in(Business::OWNERSHIP_TYPES)],
            // Business profile extensions for processor workspace onboarding.
            'business_size' => ['nullable', 'string', Rule::in(Business::BUSINESS_SIZES)],
            'baseline_revenue' => ['nullable', 'integer', 'min:0'],
            // VIBE metadata
            'vibe_unique_id' => ['nullable', 'string', 'max:100'],
            'vibe_commencement_date' => ['nullable', 'date'],
            'pathway_status' => ['nullable', 'string', Rule::in(Business::PATHWAY_STATUSES)],
            'vibe_comments' => ['nullable', 'string', 'max:1000'],
            'members' => ['nullable', 'array'],
            'members.*.first_name' => ['nullable', 'string', 'max:255'],
            'members.*.last_name' => ['nullable', 'string', 'max:255'],
            'members.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'members.*.gender' => ['nullable', 'string', Rule::in(Business::OWNER_GENDERS)],
            'members.*.pwd_status' => ['nullable', 'string', Rule::in(Business::OWNER_PWD_STATUSES)],
            'members.*.phone' => ['nullable', 'string', 'max:50'],
            'members.*.email' => ['nullable', 'email', 'max:255'],
            // Location info (optional – allow editing without forcing location)
            'country_id' => ['nullable', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'exists:administrative_divisions,id'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $registrationNumber = $this->input('registration_number');
        if ($registrationNumber !== null) {
            $normalized = preg_replace('/\s+/', ' ', trim((string) $registrationNumber)) ?? '';
            $this->merge([
                'registration_number' => mb_strtoupper($normalized),
            ]);
        }
    }
}
