<?php

namespace App\Http\Requests;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportTransportTripsRequest extends FormRequest
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
            'format' => ['nullable', 'string', Rule::in(['csv', 'excel', 'pdf', 'json'])],
            'status' => ['nullable', 'string', Rule::in(TransportTrip::STATUSES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'origin_facility_id' => ['nullable', 'integer'],
            'destination_facility_id' => ['nullable', 'integer'],
        ];
    }
}
