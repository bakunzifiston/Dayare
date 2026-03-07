<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transport_trip_id' => ['required', 'exists:transport_trips,id'],
            'receiving_facility_id' => ['nullable', 'exists:facilities,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'received_quantity' => ['required', 'integer', 'min:0'],
            'received_date' => ['required', 'date'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_country' => ['nullable', 'string', 'max:100'],
            'receiver_address' => ['nullable', 'string'],
            'confirmation_status' => ['required', 'string', 'in:pending,confirmed,disputed'],
        ];
    }
}
