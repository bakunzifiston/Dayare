<?php

namespace App\Services\Processor;

use App\Models\TransportTrip;

class DeliveryTransportAlignmentService
{
    /**
     * @return array{
     *     receiver_name: string|null,
     *     receiver_country: string|null,
     *     receiver_address: string|null
     * }
     */
    public function receiverDefaultsFromTrip(TransportTrip $trip): array
    {
        $trip->loadMissing(['destinationFacility', 'certificate']);

        if ($this->nonEmpty($trip->destination_name)) {
            return [
                'receiver_name' => trim((string) $trip->destination_name),
                'receiver_country' => $this->nonEmpty($trip->destination_country)
                    ? trim((string) $trip->destination_country)
                    : null,
                'receiver_address' => $this->nonEmpty($trip->destination_address)
                    ? trim((string) $trip->destination_address)
                    : null,
            ];
        }

        if ($trip->destination_facility_id && $trip->destinationFacility) {
            return [
                'receiver_name' => $trip->destinationFacility->facility_name,
                'receiver_country' => null,
                'receiver_address' => null,
            ];
        }

        return [
            'receiver_name' => null,
            'receiver_country' => null,
            'receiver_address' => null,
        ];
    }

    /**
     * Receiver fields that must match the linked transport trip destination.
     *
     * @return array<string, string>
     */
    public function lockedReceiverFields(TransportTrip $trip): array
    {
        $defaults = $this->receiverDefaultsFromTrip($trip);
        $locked = [];

        if ($defaults['receiver_name'] !== null) {
            $locked['receiver_name'] = $defaults['receiver_name'];
        }

        if ($defaults['receiver_country'] !== null) {
            $locked['receiver_country'] = $defaults['receiver_country'];
        }

        if ($defaults['receiver_address'] !== null) {
            $locked['receiver_address'] = $defaults['receiver_address'];
        }

        return $locked;
    }

    /**
     * @return list<string>
     */
    public function lockedReceiverFieldKeys(TransportTrip $trip): array
    {
        return array_keys($this->lockedReceiverFields($trip));
    }

    /**
     * @return array<string, mixed>
     */
    public function tripContextForForm(TransportTrip $trip): array
    {
        $trip->loadMissing(['certificate.batch', 'originFacility', 'destinationFacility']);

        return [
            'certificate_number' => $trip->certificate?->certificate_number,
            'certificate_id' => $trip->certificate_id,
            'batch_code' => $trip->certificate?->batch?->batch_code,
            'origin' => $trip->originFacility?->facility_name,
            'destination' => $trip->destination_display,
            'driver_name' => $trip->driver_name,
            'driver_phone' => $trip->driver_phone,
            'vehicle_plate_number' => $trip->vehicle_plate_number,
            'departure_date' => $trip->departure_date?->format('d M Y'),
            'receiver_defaults' => $this->receiverDefaultsFromTrip($trip),
            'locked_receiver_fields' => $this->lockedReceiverFieldKeys($trip),
        ];
    }

    private function nonEmpty(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }
}
