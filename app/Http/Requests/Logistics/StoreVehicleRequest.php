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
            'type' => ['required', 'string', 'max:80'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'max_units' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(LogisticsVehicle::STATUSES)],
        ];
    }
}

