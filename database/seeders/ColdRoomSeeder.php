<?php

namespace Database\Seeders;

use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\Facility;
use App\Models\WarehouseStorage;
use Illuminate\Database\Seeder;

class ColdRoomSeeder extends Seeder
{
    public function run(): void
    {
        $chillerStandard = ColdRoomStandard::where('type', ColdRoomStandard::TYPE_CHILLER)
            ->where('name', 'Chiller standard')
            ->first();
        $freezerStandard = ColdRoomStandard::where('type', ColdRoomStandard::TYPE_FREEZER)->first();

        if (! $chillerStandard || ! $freezerStandard) {
            $this->command?->warn('Cold room standards missing. Run ColdRoomStandardSeeder first.');

            return;
        }

        $facilityCount = 0;
        foreach (Facility::where('facility_type', Facility::TYPE_STORAGE)->get() as $facility) {
            $facilityCount++;

            ColdRoom::updateOrCreate(
                ['facility_id' => $facility->id, 'name' => 'Chiller 1'],
                [
                    'type' => ColdRoom::TYPE_CHILLER,
                    'capacity' => 5000,
                    'standard_id' => $chillerStandard->id,
                ]
            );

            ColdRoom::updateOrCreate(
                ['facility_id' => $facility->id, 'name' => 'Freezer 1'],
                [
                    'type' => ColdRoom::TYPE_FREEZER,
                    'capacity' => 3000,
                    'standard_id' => $freezerStandard->id,
                ]
            );
        }

        $linked = 0;
        WarehouseStorage::whereNull('cold_room_id')
            ->whereNotNull('warehouse_facility_id')
            ->chunkById(100, function ($storages) use (&$linked) {
                foreach ($storages as $storage) {
                    $chiller = ColdRoom::where('facility_id', $storage->warehouse_facility_id)
                        ->where('type', ColdRoom::TYPE_CHILLER)
                        ->first();

                    if ($chiller) {
                        $storage->update(['cold_room_id' => $chiller->id]);
                        $linked++;
                    }
                }
            });

        $this->command?->info(sprintf(
            'Cold rooms seeded for %d storage facilities; %d warehouse storages linked to chiller rooms.',
            $facilityCount,
            $linked
        ));
    }
}
