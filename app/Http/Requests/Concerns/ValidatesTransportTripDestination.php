<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesTransportTripDestination
{
    /**
     * @return array<string, mixed>
     */
    protected function transportTripDestinationRules(): array
    {
        $countryCodes = array_map(
            'strtoupper',
            array_keys(config('processor.destination_countries', []))
        );

        return [
            'destination_facility_id' => ['nullable', 'prohibited'],
            'destination_name' => ['required', 'string', 'max:255'],
            'destination_country' => ['nullable', 'string', 'size:2', Rule::in($countryCodes)],
            'destination_address' => ['nullable', 'string'],
        ];
    }
}
