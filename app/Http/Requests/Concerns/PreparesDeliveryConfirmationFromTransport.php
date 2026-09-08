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
        $locked = $service->lockedReceiverFields($trip);

        $merge = [];

        foreach ([
            'receiver_name',
            'receiver_country',
            'receiver_address',
        ] as $field) {
            if (array_key_exists($field, $locked)) {
                // Locked destination fields always come from the transport trip.
                $merge[$field] = $locked[$field];
            } elseif (! $this->filled($field) && $defaults[$field] !== null) {
                $merge[$field] = $defaults[$field];
            }
        }

        if (isset($merge['receiver_country']) && filled($merge['receiver_country'])) {
            $merge['receiver_country'] = strtoupper((string) $merge['receiver_country']);
        } elseif ($this->filled('receiver_country')) {
            $merge['receiver_country'] = strtoupper((string) $this->input('receiver_country'));
        }

        $this->merge($merge);
    }
}
