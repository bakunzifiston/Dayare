<?php

namespace App\Http\Requests\Farmer;

use App\Models\Farm;
use App\Models\PermitRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePermitRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'movement_purpose' => ['required', 'string', Rule::in(PermitRequest::PURPOSES)],
            'destination_type' => ['required', 'string', Rule::in(PermitRequest::DESTINATION_TYPES)],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'destination_district' => ['nullable', 'string', 'max:120'],
            'destination_sector' => ['nullable', 'string', 'max:120'],
            'destination_cell' => ['nullable', 'string', 'max:120'],
            'destination_village' => ['nullable', 'string', 'max:120'],
            'transport_method' => ['nullable', 'string', Rule::in(PermitRequest::TRANSPORT_METHODS)],
            'vehicle_plate_number' => ['nullable', 'string', 'max:50'],
            'proposed_departure_date' => ['required', 'date', 'after_or_equal:today'],
            'expected_arrival_date' => ['required', 'date', 'after_or_equal:proposed_departure_date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'animal_ids' => ['required', 'array', 'min:1'],
            'animal_ids.*' => ['integer', 'exists:animals,id'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $farm = Farm::query()->find((int) $this->input('farm_id'));
            $farmerIds = $this->user()->accessibleFarmerBusinessIds();
            if ($farm && ! $farmerIds->contains((int) $farm->business_id)) {
                $validator->errors()->add('farm_id', __('Selected farm is not accessible.'));
            }
        });
    }
}
