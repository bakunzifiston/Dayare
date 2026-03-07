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
        return [
            'facility_id' => ['required', 'exists:facilities,id'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('supplier_status', Supplier::STATUS_APPROVED)],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'intake_date' => ['required', 'date'],
            'supplier_firstname' => ['required_unless:supplier_id,null', 'nullable', 'string', 'max:255'],
            'supplier_lastname' => ['required_unless:supplier_id,null', 'nullable', 'string', 'max:255'],
            'supplier_contact' => ['nullable', 'string', 'max:100'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'farm_registration_number' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'exists:administrative_divisions,id'],
            'species' => ['required', 'string', 'max:50', 'in:'.implode(',', AnimalIntake::SPECIES_OPTIONS)],
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
