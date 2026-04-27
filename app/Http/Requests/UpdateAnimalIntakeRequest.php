<?php

namespace App\Http\Requests;

use App\Models\AnimalIntake;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnimalIntakeRequest extends FormRequest
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
        $isClientSource = $this->input('source_type') === AnimalIntake::SOURCE_TYPE_CLIENT;
        $hasExistingClient = (int) $this->input('client_id') > 0;

        return [
            'facility_id' => ['required', 'exists:facilities,id'],
            'source_type' => ['required', 'string', Rule::in(AnimalIntake::SOURCE_TYPES)],
            'supplier_id' => [
                'nullable',
                'required_if:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
                'prohibited_if:source_type,'.AnimalIntake::SOURCE_TYPE_CLIENT,
                Rule::exists('suppliers', 'id')->where('supplier_status', Supplier::STATUS_APPROVED),
            ],
            'client_id' => [
                'nullable',
                'required_if:source_type,'.AnimalIntake::SOURCE_TYPE_CLIENT,
                'prohibited_if:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
                Rule::exists('clients', 'id')->where('is_active', true),
            ],
            'contract_id' => ['nullable', 'prohibited_if:source_type,'.AnimalIntake::SOURCE_TYPE_CLIENT, 'exists:contracts,id'],
            'intake_date' => ['required', 'date'],
            'supplier_firstname' => [
                'nullable',
                'string',
                'max:255',
            ],
            'supplier_lastname' => [
                'nullable',
                'string',
                'max:255',
            ],
            'supplier_contact' => ['nullable', 'string', 'max:100'],
            'manual_client_firstname' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $isClientSource && ! $hasExistingClient)],
            'manual_client_lastname' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $isClientSource && ! $hasExistingClient)],
            'manual_client_contact' => ['nullable', 'string', 'max:100'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'farm_registration_number' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'exists:administrative_divisions,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'number_of_animals' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'animal_identification_numbers' => ['nullable', 'string'],
            'transport_vehicle_plate' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'animal_health_certificate_number' => ['nullable', 'string', 'max:100'],
            'health_certificate_issue_date' => ['nullable', 'date'],
            'health_certificate_expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:'.implode(',', AnimalIntake::STATUSES)],
        ];
    }
}
