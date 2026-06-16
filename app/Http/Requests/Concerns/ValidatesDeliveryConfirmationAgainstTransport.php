<?php

namespace App\Http\Requests\Concerns;

use App\Models\TransportTrip;
use App\Services\Processor\DeliveryTransportAlignmentService;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesDeliveryConfirmationAgainstTransport
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tripId = $this->input('transport_trip_id');
            if ($tripId === null || $tripId === '') {
                return;
            }

            $trip = TransportTrip::query()->find($tripId);
            if ($trip === null) {
                return;
            }

            $locked = app(DeliveryTransportAlignmentService::class)->lockedReceiverFields($trip);
            foreach ($locked as $field => $expected) {
                $submitted = $this->input($field);
                if ($submitted === null || trim((string) $submitted) === '') {
                    continue;
                }

                if (trim((string) $submitted) !== $expected) {
                    $validator->errors()->add(
                        $field,
                        __('This must match the destination recorded on the transport trip.')
                    );
                }
            }
        });
    }
}
