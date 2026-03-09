<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Facility;
use App\Models\WarehouseStorage;
use Illuminate\Database\Seeder;

/**
 * Seed warehouse (cold storage) records — Rwanda. Run after CertificateSeeder.
 */
class WarehouseStorageSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Facility::where('facility_type', Facility::TYPE_STORAGE)->first();
        if (! $warehouse) {
            $this->command?->warn('No storage facility. Add type "storage" in TestDataSeeder.');
            return;
        }

        $certificates = Certificate::with('batch')->whereHas('batch')->get();
        if ($certificates->isEmpty()) {
            $this->command?->warn('No certificates with batch. Run CertificateSeeder first.');
            return;
        }

        $entryDate = now()->subDays(5);

        foreach ($certificates->take(2) as $cert) {
            if (WarehouseStorage::where('certificate_id', $cert->id)->exists()) {
                continue;
            }
            $qty = $cert->batch ? $cert->batch->quantity : 10;
            WarehouseStorage::firstOrCreate(
                [
                    'certificate_id' => $cert->id,
                ],
                [
                    'warehouse_facility_id' => $warehouse->id,
                    'batch_id' => $cert->batch_id,
                    'entry_date' => $entryDate,
                    'storage_location' => 'Cold Room A - Rwanda',
                    'temperature_at_entry' => -18.5,
                    'quantity_stored' => $qty,
                    'quantity_unit' => \App\Models\Unit::where('code', 'kg')->value('code') ?: 'kg',
                    'status' => WarehouseStorage::STATUS_IN_STORAGE,
                ]
            );
        }

        $this->command?->info('Warehouse storages seeded (Rwanda cold storage).');
    }
}
