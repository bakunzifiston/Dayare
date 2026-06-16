<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreparesTransportTripFromCertificate;
use App\Http\Requests\Concerns\ValidatesTransportTripAgainstCertificate;
use App\Http\Requests\Concerns\ValidatesTransportTripDestination;
use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportTripRequest extends FormRequest
{
    use PreparesTransportTripFromCertificate;
    use ValidatesTransportTripAgainstCertificate;
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
            'certificate_id' => [
                'required',
                Rule::exists('certificates', 'id')->where(function ($query) {
                    $query->where('status', Certificate::STATUS_ACTIVE)
                        ->where(function ($inner) {
                            $inner->whereNull('expiry_date')
                                ->orWhereDate('expiry_date', '>=', today());
                        });
                }),
            ],
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
