<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreparesTransportTripFromWarehouseStorage;
use App\Http\Requests\Concerns\ValidatesTransportTripDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportTripRequest extends FormRequest
{
    use PreparesTransportTripFromWarehouseStorage;
    use ValidatesTransportTripDestination;
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
            'warehouse_storage_id' => [
                'nullable',
                'exists:warehouse_storages,id',
                Rule::requiredIf(fn () => $this->boolean('require_released_storage')),
            ],
            'require_released_storage' => ['sometimes', 'boolean'],
            'certificate_id' => ['required', 'exists:certificates,id'],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'origin_facility_id' => ['required', 'exists:facilities,id'],
            ...$this->transportTripDestinationRules(),
            'vehicle_plate_number' => ['required', 'string', 'max:50'],
            'driver_name' => ['required', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'departure_date' => ['required', 'date'],
            'arrival_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'status' => ['required', 'string', 'in:pending,in_transit,arrived,completed'],
        ];
    }
}
