<?php

namespace App\Http\Requests\Concerns;

use App\Models\TransportTrip;
use App\Services\Processor\DeliveryTransportAlignmentService;

trait PreparesDeliveryConfirmationFromTransport
{
    protected function prepareForValidation(): void
    {
        $tripId = $this->input('transport_trip_id');
        if ($tripId === null || $tripId === '') {
            return;
        }

        $trip = TransportTrip::query()->find($tripId);
        if ($trip === null) {
            return;
        }

        $service = app(DeliveryTransportAlignmentService::class);
        $defaults = $service->receiverDefaultsFromTrip($trip);

        $merge = [];

        foreach ([
            'receiver_name',
            'receiver_country',
            'receiver_address',
        ] as $field) {
            if (! $this->filled($field) && $defaults[$field] !== null) {
                $merge[$field] = $defaults[$field];
            }
        }

        foreach ($service->lockedReceiverFields($trip) as $field => $value) {
            if (! $this->filled($field)) {
                $merge[$field] = $value;
            }
        }

        $this->merge($merge);
    }
}
