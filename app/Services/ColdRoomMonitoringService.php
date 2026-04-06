<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\ColdRoomTemperatureLog;
use App\Models\ColdRoomViolation;
use App\Models\WarehouseStorage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cold chain monitoring: evaluates each cold-room temperature reading against the linked standard,
 * opens/closes violations, and escalates batch cold_chain_status when out-of-tolerance duration grows.
 *
 * @example
 * // Preferred: create logs via the service so processing always runs.
 * $log = app(ColdRoomMonitoringService::class)->recordTemperature($coldRoom, -2.5);
 * @example
 * // Or create a log directly; ColdRoomTemperatureLogObserver runs the same pipeline.
 * ColdRoomTemperatureLog::create([
 *     'cold_room_id' => $room->id,
 *     'temperature' => -3.0,
 *     'recorded_at' => now(),
 * ]);
 */
class ColdRoomMonitoringService
{
    public function recordTemperature(ColdRoom $room, float $temperature, ?CarbonInterface $recordedAt = null): ColdRoomTemperatureLog
    {
        return ColdRoomTemperatureLog::create([
            'cold_room_id' => $room->id,
            'temperature' => $temperature,
            'recorded_at' => $recordedAt ?? now(),
        ]);
    }

    public function processLog(ColdRoomTemperatureLog $log): void
    {
        $room = $log->coldRoom()->with('standard')->first();
        if (! $room || ! $room->standard instanceof ColdRoomStandard) {
            return;
        }

        $standard = $room->standard;
        $recordedAt = $log->recorded_at;
        $temp = (float) $log->temperature;
        $inRange = $standard->temperatureInRange($temp);

        DB::transaction(function () use ($room, $standard, $recordedAt, $inRange): void {
            ColdRoom::query()->whereKey($room->id)->lockForUpdate()->first();

            $open = $this->openViolation($room);

            if (! $inRange) {
                if (! $open) {
                    $open = ColdRoomViolation::create([
                        'cold_room_id' => $room->id,
                        'start_time' => $recordedAt,
                        'end_time' => null,
                        'duration_minutes' => null,
                        'status' => ColdRoomViolation::STATUS_OPEN,
                    ]);
                }

                $minutes = $this->minutesBetween($open->start_time, $recordedAt);
                $open->update(['duration_minutes' => $minutes]);

                $this->applyBatchColdChainRisk($room, $minutes, $standard);

                return;
            }

            if ($open) {
                $minutes = $this->minutesBetween($open->start_time, $recordedAt);
                $this->applyBatchColdChainRisk($room, $minutes, $standard);

                $open->update([
                    'end_time' => $recordedAt,
                    'duration_minutes' => $minutes,
                    'status' => ColdRoomViolation::STATUS_CLOSED,
                ]);
            }
        });
    }

    protected function openViolation(ColdRoom $room): ?ColdRoomViolation
    {
        return ColdRoomViolation::query()
            ->where('cold_room_id', $room->id)
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    protected function minutesBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, (int) $start->diffInMinutes($end));
    }

    /**
     * Batches physically in this cold room (in_storage) are escalated when violation duration
     * exceeds the standard tolerance: at_risk first, then compromised after 2× tolerance.
     */
    protected function applyBatchColdChainRisk(ColdRoom $room, int $durationMinutes, ColdRoomStandard $standard): void
    {
        $tolerance = max((int) $standard->tolerance_minutes, 1);
        if ($durationMinutes <= $tolerance) {
            return;
        }

        $batchIds = WarehouseStorage::query()
            ->where('cold_room_id', $room->id)
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->pluck('batch_id');

        if ($batchIds->isEmpty()) {
            return;
        }

        $compromisedAfter = $tolerance * 2;

        foreach ($batchIds as $batchId) {
            $batch = Batch::query()->whereKey($batchId)->lockForUpdate()->first();
            if (! $batch) {
                continue;
            }

            if ($durationMinutes > $compromisedAfter) {
                if ($batch->cold_chain_status !== Batch::COLD_CHAIN_COMPROMISED) {
                    $batch->update(['cold_chain_status' => Batch::COLD_CHAIN_COMPROMISED]);
                }

                continue;
            }

            if ($batch->cold_chain_status === Batch::COLD_CHAIN_COMPROMISED) {
                continue;
            }

            $batch->update(['cold_chain_status' => Batch::COLD_CHAIN_AT_RISK]);
        }
    }
}
