<?php

namespace App\Http\Requests\Concerns;

trait ValidatesTransportTripDestination
{
    /**
     * @return array<string, mixed>
     */
    protected function transportTripDestinationRules(): array
    {
        return [
            'destination_facility_id' => ['nullable', 'prohibited'],
            'destination_name' => ['required', 'string', 'max:255'],
            'destination_country' => ['nullable', 'string', 'max:100'],
            'destination_address' => ['nullable', 'string'],
        ];
    }
}
