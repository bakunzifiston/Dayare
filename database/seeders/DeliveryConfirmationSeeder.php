<?php

namespace Database\Seeders;

use App\Models\DeliveryConfirmation;
use App\Models\TransportTrip;
use Illuminate\Database\Seeder;

class DeliveryConfirmationSeeder extends Seeder
{
    public function run(): void
    {
        $trips = TransportTrip::with('certificate.batch')->get();
        if ($trips->isEmpty()) {
            $this->command?->warn('No transport trips. Run TransportTripSeeder first.');
            return;
        }
        foreach ($trips as $trip) {
            $qty = $trip->certificate && $trip->certificate->batch
                ? $trip->certificate->batch->quantity
                : 10;
            DeliveryConfirmation::firstOrCreate(
                ['transport_trip_id' => $trip->id],
                [
                    'receiving_facility_id' => $trip->destination_facility_id,
                    'received_quantity' => $qty,
                    'received_date' => now(),
                    'receiver_name' => 'Test Receiver',
                    'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
                ]
            );
        }
        $this->command?->info('Delivery confirmations seeded.');
    }
}
