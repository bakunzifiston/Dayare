<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherTemperatureLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherStorageService
{
    public function createBatchFromDelivery(ButcherDelivery $delivery, ?string $storageLocation = null): ButcherInventoryBatch
    {
        $business = $delivery->business ?? Business::query()->findOrFail($delivery->business_id);
        $shelfLifeDays = (int) ($business->butcher_batch_shelf_life_days ?? 3);
        $receivedAt = Carbon::parse($delivery->received_at);

        return ButcherInventoryBatch::query()->create([
            'business_id' => $delivery->business_id,
            'delivery_id' => $delivery->id,
            'outlet_id' => $delivery->outlet_id,
            'batch_number' => $this->generateBatchNumber((int) $delivery->business_id),
            'meat_type' => $delivery->meat_type,
            'initial_weight_kg' => $delivery->received_weight_kg,
            'remaining_weight_kg' => $delivery->received_weight_kg,
            'unit_cost_per_kg' => $delivery->unit_cost_per_kg,
            'status' => ButcherInventoryBatch::STATUS_IN_STORAGE,
            'received_at' => $receivedAt,
            'best_before_date' => $receivedAt->copy()->addDays($shelfLifeDays)->toDateString(),
            'storage_location' => $storageLocation,
        ]);
    }

    public function logTemperature(Business $business, array $data, User $user): ButcherTemperatureLog
    {
        $storageType = (string) ($data['storage_type'] ?? ButcherTemperatureLog::TYPE_FRESH);
        $temperature = (float) $data['temperature_celsius'];
        $threshold = $this->temperatureThreshold($business, $storageType);
        $isBreach = $temperature > $threshold;

        return $business->butcherTemperatureLogs()->create([
            'outlet_id' => (int) $data['outlet_id'],
            'storage_location' => (string) $data['storage_location'],
            'storage_type' => $storageType,
            'temperature_celsius' => $temperature,
            'logged_at' => isset($data['logged_at']) ? Carbon::parse($data['logged_at']) : now(),
            'logged_by' => $user->id,
            'is_breach' => $isBreach,
            'breach_note' => $isBreach ? ($data['breach_note'] ?? __('Temperature :temp°C exceeds :max°C limit.', [
                'temp' => $temperature,
                'max' => $threshold,
            ])) : null,
        ]);
    }

    public function logDisposal(ButcherInventoryBatch $batch, array $data, User $user): ButcherDisposalLog
    {
        return DB::transaction(function () use ($batch, $data, $user) {
            $weight = (float) $data['weight_disposed_kg'];
            $remaining = (float) $batch->remaining_weight_kg;

            if ($weight <= 0 || $weight > $remaining) {
                throw ValidationException::withMessages([
                    'weight_disposed_kg' => [__('Disposed weight must be between 0.1 and :max kg.', ['max' => number_format($remaining, 3)])],
                ]);
            }

            $disposedAt = isset($data['disposed_at']) ? Carbon::parse($data['disposed_at']) : now();

            $log = ButcherDisposalLog::query()->create([
                'business_id' => $batch->business_id,
                'batch_id' => $batch->id,
                'weight_disposed_kg' => $weight,
                'reason' => (string) $data['reason'],
                'disposed_at' => $disposedAt,
                'disposed_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $newRemaining = round($remaining - $weight, 3);
            $status = $newRemaining <= 0
                ? ButcherInventoryBatch::STATUS_DISPOSED
                : ButcherInventoryBatch::STATUS_PARTIALLY_USED;

            $batch->update([
                'remaining_weight_kg' => max(0, $newRemaining),
                'status' => $status,
            ]);

            return $log->fresh(['batch', 'disposedByUser']);
        });
    }

    public function logAdjustment(ButcherInventoryBatch $batch, array $data, User $user): ButcherInventoryAdjustment
    {
        return DB::transaction(function () use ($batch, $data, $user) {
            $batch = ButcherInventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $previous = (float) $batch->remaining_weight_kg;
            $change = (float) $data['weight_change_kg'];
            $newWeight = round($previous + $change, 3);

            if ($newWeight < 0) {
                throw ValidationException::withMessages([
                    'weight_change_kg' => [__('Adjustment would make remaining weight negative.')],
                ]);
            }

            $adjustment = ButcherInventoryAdjustment::query()->create([
                'business_id' => $batch->business_id,
                'batch_id' => $batch->id,
                'weight_change_kg' => $change,
                'previous_weight_kg' => $previous,
                'new_weight_kg' => $newWeight,
                'reason' => (string) $data['reason'],
                'adjusted_at' => isset($data['adjusted_at']) ? Carbon::parse($data['adjusted_at']) : now(),
                'adjusted_by' => $user->id,
                'stock_count_line_id' => $data['stock_count_line_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $status = $newWeight <= 0
                ? ButcherInventoryBatch::STATUS_FULLY_USED
                : (in_array($batch->status, [
                    ButcherInventoryBatch::STATUS_FULLY_USED,
                    ButcherInventoryBatch::STATUS_DISPOSED,
                ], true)
                    ? ButcherInventoryBatch::STATUS_PARTIALLY_USED
                    : $batch->status);

            if ($batch->status === ButcherInventoryBatch::STATUS_EXPIRED && $newWeight > 0) {
                $status = ButcherInventoryBatch::STATUS_EXPIRED;
            }

            $batch->update([
                'remaining_weight_kg' => $newWeight,
                'status' => $status,
            ]);

            return $adjustment->fresh(['batch', 'adjustedByUser']);
        });
    }

    /**
     * @return array{
     *   waste_kg: float,
     *   waste_events: int,
     *   adjustment_kg: float,
     *   adjustment_events: int,
     *   recent_waste: \Illuminate\Support\Collection,
     *   recent_adjustments: \Illuminate\Support\Collection
     * }
     */
    public function getWasteSummary(Business $business, string $period = '30d'): array
    {
        $since = match ($period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            default => now()->subDays(30),
        };

        $wasteQuery = $business->butcherDisposalLogs()->where('disposed_at', '>=', $since);
        $adjustQuery = $business->butcherInventoryAdjustments()->where('adjusted_at', '>=', $since);

        return [
            'waste_kg' => (float) (clone $wasteQuery)->sum('weight_disposed_kg'),
            'waste_events' => (int) (clone $wasteQuery)->count(),
            'adjustment_kg' => (float) (clone $adjustQuery)->sum('weight_change_kg'),
            'adjustment_events' => (int) (clone $adjustQuery)->count(),
            'recent_waste' => $business->butcherDisposalLogs()
                ->with(['batch', 'disposedByUser'])
                ->latest('disposed_at')
                ->limit(8)
                ->get(),
            'recent_adjustments' => $business->butcherInventoryAdjustments()
                ->with(['batch', 'adjustedByUser'])
                ->latest('adjusted_at')
                ->limit(8)
                ->get(),
        ];
    }

    public function checkExpiringBatches(Business $business): Collection
    {
        $expired = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->whereDate('best_before_date', '<', now()->toDateString())
            ->get();

        foreach ($expired as $batch) {
            $batch->update(['status' => ButcherInventoryBatch::STATUS_EXPIRED]);
        }

        return $expired;
    }

    /**
     * @return array{
     *   batches_in_storage: int,
     *   kg_in_storage: float,
     *   expiring_soon: int,
     *   expired_batches: int,
     *   temp_breaches_today: int,
     *   fifo_batches: \Illuminate\Support\Collection,
     *   recent_temperature_logs: \Illuminate\Support\Collection,
     *   recent_disposals: \Illuminate\Support\Collection
     * }
     */
    public function getStorageSummary(Business $business): array
    {
        $this->checkExpiringBatches($business);

        $activeQuery = $business->butcherInventoryBatches()
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES);

        $fifoBatches = (clone $activeQuery)
            ->with(['outlet', 'delivery.supplier'])
            ->orderBy('received_at')
            ->limit(10)
            ->get();

        return [
            'batches_in_storage' => (int) (clone $activeQuery)->count(),
            'kg_in_storage' => (float) (clone $activeQuery)->sum('remaining_weight_kg'),
            'expiring_soon' => (int) $business->butcherInventoryBatches()
                ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
                ->whereDate('best_before_date', '>=', now()->toDateString())
                ->whereDate('best_before_date', '<=', now()->addDay()->toDateString())
                ->count(),
            'expired_batches' => (int) $business->butcherInventoryBatches()
                ->where('status', ButcherInventoryBatch::STATUS_EXPIRED)
                ->count(),
            'temp_breaches_today' => (int) $business->butcherTemperatureLogs()
                ->where('is_breach', true)
                ->whereDate('logged_at', now()->toDateString())
                ->count(),
            'fifo_batches' => $fifoBatches,
            'recent_temperature_logs' => $business->butcherTemperatureLogs()
                ->with(['outlet', 'loggedByUser'])
                ->latest('logged_at')
                ->limit(5)
                ->get(),
            'recent_disposals' => $business->butcherDisposalLogs()
                ->with(['batch', 'disposedByUser'])
                ->latest('disposed_at')
                ->limit(5)
                ->get(),
        ];
    }

    public function syncBatchWeightStatus(ButcherInventoryBatch $batch): void
    {
        if ((float) $batch->remaining_weight_kg <= 0
            && in_array($batch->status, ButcherInventoryBatch::ACTIVE_STATUSES, true)) {
            $batch->update(['status' => ButcherInventoryBatch::STATUS_FULLY_USED]);
        }
    }

    public function temperatureThreshold(Business $business, string $storageType): float
    {
        if ($storageType === ButcherTemperatureLog::TYPE_FROZEN) {
            return (float) ($business->butcher_frozen_max_temp_c ?? -18);
        }

        return (float) ($business->butcher_fresh_max_temp_c ?? 4);
    }

    private function generateBatchNumber(int $businessId): string
    {
        $sequence = ButcherInventoryBatch::query()->where('business_id', $businessId)->count() + 1;

        return sprintf('BATCH-%d-%04d', $businessId, $sequence);
    }
}
