<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportTripRequest extends FormRequest
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
            'certificate_id' => ['required', 'exists:certificates,id'],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'origin_facility_id' => ['required', 'exists:facilities,id'],
            'destination_facility_id' => ['required', 'exists:facilities,id', 'different:origin_facility_id'],
            'vehicle_plate_number' => ['required', 'string', 'max:50'],
            'driver_name' => ['required', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'departure_date' => ['required', 'date'],
            'arrival_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'status' => ['required', 'string', 'in:pending,in_transit,arrived,completed'],
        ];
    }
}
