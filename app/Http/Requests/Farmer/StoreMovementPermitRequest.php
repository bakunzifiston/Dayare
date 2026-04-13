<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permit_number' => ['required', 'string', 'max:100', 'unique:movement_permits,permit_number'],
            'source_farm_id' => ['required', 'integer', 'exists:farms,id'],
            'destination_district_id' => ['required', 'integer', 'exists:administrative_divisions,id'],
            'destination_sector_id' => ['required', 'integer', 'exists:administrative_divisions,id'],
            'destination_cell_id' => ['required', 'integer', 'exists:administrative_divisions,id'],
            'destination_village_id' => ['required', 'integer', 'exists:administrative_divisions,id'],
            'transport_mode' => ['nullable', 'string', 'max:50'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'issued_by' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'animals' => ['required', 'array', 'min:1'],
            'animals.*.livestock_id' => ['nullable', 'integer', 'exists:livestock,id', 'required_without:animals.*.animal_identifier'],
            'animals.*.animal_identifier' => ['nullable', 'string', 'max:120', 'required_without:animals.*.livestock_id'],
            'animals.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

