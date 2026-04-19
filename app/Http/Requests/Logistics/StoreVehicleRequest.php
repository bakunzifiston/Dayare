<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsVehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('logistics_companies', 'id')],
            'plate_number' => ['required', 'string', 'max:60', 'unique:logistics_vehicles,plate_number'],
            'type' => ['required', Rule::in(LogisticsVehicle::TYPES)],
            'capacity_value' => ['required', 'numeric', 'min:0.01'],
            'capacity_unit' => ['required', Rule::in(LogisticsVehicle::CAPACITY_UNITS)],
            'vehicle_features' => ['nullable', 'array'],
            'vehicle_features.*' => [Rule::in(LogisticsVehicle::FEATURES)],
            'status' => ['nullable', Rule::in(LogisticsVehicle::STATUSES)],
        ];
    }
}
