<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\DeliveryConfirmation;
use App\Models\TransportTrip;
use Illuminate\Database\Seeder;

/**
 * Seed delivery confirmations — Rwanda receiver names.
 * Optionally link client_id when destination facility has clients for same business.
 */
class DeliveryConfirmationSeeder extends Seeder
{
    private const RWANDA_RECEIVERS = ['Claudine Uwineza', 'Jean Pierre Ndayisaba', 'Grace Mukiza', 'David Mugisha'];

    public function run(): void
    {
        $trips = TransportTrip::with('certificate.batch', 'destinationFacility')->get();
        if ($trips->isEmpty()) {
            $this->command?->warn('No transport trips. Run TransportTripSeeder first.');
            return;
        }
        $idx = 0;
        foreach ($trips as $trip) {
            $qty = $trip->certificate && $trip->certificate->batch
                ? $trip->certificate->batch->quantity
                : 10;
            $destFacility = $trip->destinationFacility;
            $clientId = null;
            if ($destFacility && $destFacility->business_id) {
                $client = Client::where('business_id', $destFacility->business_id)->where('is_active', true)->inRandomOrder()->first();
                $clientId = $client?->id;
            }
            DeliveryConfirmation::firstOrCreate(
                ['transport_trip_id' => $trip->id],
                [
                    'receiving_facility_id' => $trip->destination_facility_id,
                    'client_id' => $clientId,
                    'received_quantity' => $qty,
                    'received_date' => now(),
                    'receiver_name' => self::RWANDA_RECEIVERS[$idx % count(self::RWANDA_RECEIVERS)],
                    'receiver_country' => 'Rwanda',
                    'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
                ]
            );
            $idx++;
        }
        $this->command?->info('Delivery confirmations seeded (Rwanda).');
    }
}
