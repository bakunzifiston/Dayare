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
            'destination_facility_id' => ['nullable', 'exists:facilities,id', 'different:origin_facility_id'],
            'destination_name' => ['required_without:destination_facility_id', 'nullable', 'string', 'max:255'],
            'destination_country' => ['nullable', 'string', 'max:100'],
            'destination_address' => ['nullable', 'string'],
        ];
    }
}
