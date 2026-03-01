<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Facility;
use App\Models\TransportTrip;
use Illuminate\Database\Seeder;

class TransportTripSeeder extends Seeder
{
    public function run(): void
    {
        $certificates = Certificate::with(['batch', 'facility'])->get();
        if ($certificates->isEmpty()) {
            $this->command?->warn('No certificates. Run CertificateSeeder first.');
            return;
        }
        $facilities = Facility::all();
        if ($facilities->count() < 2) {
            $this->command?->warn('Need at least 2 facilities for transport trips.');
            return;
        }
        foreach ($certificates->take(2) as $cert) {
            $originId = $cert->facility_id;
            $destination = $facilities->firstWhere('id', '!=', $originId);
            if (! $destination) {
                continue;
            }
            TransportTrip::firstOrCreate(
                [
                    'certificate_id' => $cert->id,
                    'origin_facility_id' => $originId,
                ],
                [
                    'batch_id' => $cert->batch_id,
                    'destination_facility_id' => $destination->id,
                    'vehicle_plate_number' => 'RAB 123 A',
                    'driver_name' => 'Test Driver',
                    'driver_phone' => '+250788000001',
                    'departure_date' => now()->subDay(),
                    'arrival_date' => now(),
                    'status' => TransportTrip::STATUS_ARRIVED,
                ]
            );
        }
        $this->command?->info('Transport trips seeded.');
    }
}
