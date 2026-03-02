<?php

namespace Database\Seeders;

use App\Models\TemperatureLog;
use App\Models\WarehouseStorage;
use Illuminate\Database\Seeder;

/**
 * Seed temperature logs for warehouse storages — Rwanda cold chain monitoring.
 */
class TemperatureLogSeeder extends Seeder
{
    public function run(): void
    {
        $storages = WarehouseStorage::where('status', WarehouseStorage::STATUS_IN_STORAGE)->get();
        if ($storages->isEmpty()) {
            $this->command?->warn('No warehouse storages. Run WarehouseStorageSeeder first.');
            return;
        }

        foreach ($storages as $storage) {
            for ($i = 0; $i < 3; $i++) {
                $recordedAt = now()->subDays($i)->setHour(8 + $i * 4)->setMinute(0);
                $temp = -18.0 + (rand(-5, 5) / 10.0);
                $status = $temp >= -15 ? ($temp >= -12 ? TemperatureLog::STATUS_CRITICAL : TemperatureLog::STATUS_WARNING) : TemperatureLog::STATUS_NORMAL;
                TemperatureLog::firstOrCreate(
                    [
                        'warehouse_storage_id' => $storage->id,
                        'recorded_at' => $recordedAt,
                    ],
                    [
                        'recorded_temperature' => round($temp, 2),
                        'recorded_by' => 'Rwanda Cold Chain Monitor',
                        'status' => $status,
                    ]
                );
            }
        }

        $this->command?->info('Temperature logs seeded.');
    }
}
