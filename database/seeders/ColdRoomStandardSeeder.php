<?php

namespace Database\Seeders;

use App\Models\ColdRoomStandard;
use Illuminate\Database\Seeder;

class ColdRoomStandardSeeder extends Seeder
{
    public function run(): void
    {
        $standards = [
            [
                'name' => 'Chiller standard',
                'type' => ColdRoomStandard::TYPE_CHILLER,
                'min_temperature' => 0.0,
                'max_temperature' => 4.0,
                'tolerance_minutes' => 30,
            ],
            [
                'name' => 'Freezer standard',
                'type' => ColdRoomStandard::TYPE_FREEZER,
                'min_temperature' => -24.0,
                'max_temperature' => -18.0,
                'tolerance_minutes' => 60,
            ],
            [
                'name' => 'Extended chiller standard',
                'type' => ColdRoomStandard::TYPE_CHILLER,
                'min_temperature' => 0.0,
                'max_temperature' => 7.0,
                'tolerance_minutes' => 45,
            ],
        ];

        foreach ($standards as $standard) {
            ColdRoomStandard::updateOrCreate(
                ['name' => $standard['name']],
                $standard
            );
        }

        $this->command?->info('Cold room standards seeded (idempotent).');
    }
}
