<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Facility;
use App\Models\TransportTrip;
use Illuminate\Database\Seeder;

/**
 * Seed transport trips — Rwanda vehicle plates (RAB), drivers.
 */
class TransportTripSeeder extends Seeder
{
    private const RWANDA_DRIVERS = ['Mugisha Jean', 'Habimana Patrick', 'Uwera Marie', 'Niyonsenga Eric'];

    public function run(): void
    {
        $certificates = Certificate::with(['batch', 'facility'])->get();
        if ($certificates->isEmpty()) {
            $this->command?->warn('No certificates. Run CertificateSeeder first.');
            return;
        }
        $facilities = Facility::whereIn('facility_type', [Facility::TYPE_SLAUGHTER_HOUSE, Facility::TYPE_COLD_ROOM])->get();
        if ($facilities->count() < 2) {
            $this->command?->warn('Need at least 2 facilities for transport trips.');
            return;
        }
        $idx = 0;
        foreach ($certificates->take(2) as $cert) {
            $originId = $cert->facility_id;
            $destination = $facilities->firstWhere('id', '!=', $originId);
            if (! $destination) {
                continue;
            }
            $plate = 'RAB ' . rand(100, 999) . ' ' . chr(65 + ($idx % 26));
            TransportTrip::firstOrCreate(
                [
                    'certificate_id' => $cert->id,
                    'origin_facility_id' => $originId,
                ],
                [
                    'batch_id' => $cert->batch_id,
                    'destination_facility_id' => $destination->id,
                    'vehicle_plate_number' => $plate,
                    'driver_name' => self::RWANDA_DRIVERS[$idx % count(self::RWANDA_DRIVERS)],
                    'driver_phone' => '+250788' . rand(100000, 999999),
                    'departure_date' => now()->subDay(),
                    'arrival_date' => now(),
                    'status' => TransportTrip::STATUS_ARRIVED,
                ]
            );
            $idx++;
        }
        $this->command?->info('Transport trips seeded (Rwanda).');
    }
}
