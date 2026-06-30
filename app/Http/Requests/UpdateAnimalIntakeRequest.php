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

    protected function prepareForValidation(): void
    {
        $intake = $this->route('animalIntake');

        if ($intake instanceof AnimalIntake && $intake->isSupplierSource()) {
            return;
        }

        $this->merge([
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'supplier_id' => null,
            'contract_id' => null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $facilityId = (int) $this->input('facility_id');
        $businessId = (int) \App\Models\Facility::query()->whereKey($facilityId)->value('business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];
        $hasExistingClient = (int) $this->input('client_id') > 0;
        $intake = $this->route('animalIntake');
        $isLegacySupplier = $intake instanceof AnimalIntake && $intake->isSupplierSource();

        return [
            'facility_id' => ['required', 'exists:facilities,id'],
            'source_type' => [
                'required',
                'string',
                Rule::in($isLegacySupplier ? [AnimalIntake::SOURCE_TYPE_SUPPLIER] : AnimalIntake::SOURCE_TYPES),
            ],
            'supplier_id' => $isLegacySupplier
                ? ['required', Rule::exists('suppliers', 'id')->where('supplier_status', Supplier::STATUS_APPROVED)]
                : ['prohibited'],
            'client_id' => [
                'nullable',
                'prohibited_if:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
                Rule::exists('clients', 'id')->where('is_active', true),
            ],
            'contract_id' => $isLegacySupplier
                ? ['nullable', 'exists:contracts,id']
                : ['prohibited'],
            'intake_date' => ['required', 'date', 'before_or_equal:now'],
            'supplier_firstname' => ['nullable', 'string', 'max:255'],
            'supplier_lastname' => ['nullable', 'string', 'max:255'],
            'supplier_contact' => ['nullable', 'string', 'max:100'],
            'manual_client_firstname' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => ! $isLegacySupplier && ! $hasExistingClient),
            ],
            'manual_client_lastname' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => ! $isLegacySupplier && ! $hasExistingClient),
            ],
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
            'species_ear_tag' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', 'string', Rule::in(AnimalIntake::SEX_OPTIONS)],
            'age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'number_of_animals' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'animal_identification_numbers' => ['nullable', 'string'],
            'observation' => ['nullable', 'string'],
            'meat_inspector_name' => ['nullable', 'string', 'max:255'],
            'movement_permit_no' => ['nullable', 'string', 'max:100'],
            'movement_permit_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'transport_vehicle_plate' => [
                'nullable',
                'string',
                'max:50',
                'prohibited_unless:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
            ],
            'driver_name' => [
                'nullable',
                'string',
                'max:255',
                'prohibited_unless:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
            ],
            'animal_health_certificate_number' => ['nullable', 'string', 'max:100'],
            'health_certificate_issue_date' => ['nullable', 'date'],
            'health_certificate_expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:'.implode(',', AnimalIntake::STATUSES)],
        ];
    }
}
