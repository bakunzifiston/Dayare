<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherStockCount;
use App\Models\ButcherStockCountLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherStockCountService
{
    public function __construct(
        private readonly ButcherStorageService $storage,
    ) {}

    public function startCount(Business $business, array $data, User $user): ButcherStockCount
    {
        return DB::transaction(function () use ($business, $data, $user) {
            $outletId = isset($data['outlet_id']) && $data['outlet_id'] !== ''
                ? (int) $data['outlet_id']
                : null;

            $batchesQuery = $business->butcherInventoryBatches()
                ->whereIn('status', [
                    ButcherInventoryBatch::STATUS_IN_STORAGE,
                    ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                    ButcherInventoryBatch::STATUS_EXPIRED,
                ])
                ->where('remaining_weight_kg', '>', 0)
                ->orderBy('received_at');

            if ($outletId !== null) {
                $batchesQuery->where('outlet_id', $outletId);
            }

            $batches = $batchesQuery->get();

            if ($batches->isEmpty()) {
                throw ValidationException::withMessages([
                    'outlet_id' => [__('No inventory batches available to count.')],
                ]);
            }

            $count = ButcherStockCount::query()->create([
                'business_id' => $business->id,
                'outlet_id' => $outletId,
                'count_number' => $this->generateCountNumber($business),
                'status' => ButcherStockCount::STATUS_DRAFT,
                'count_date' => isset($data['count_date'])
                    ? Carbon::parse($data['count_date'])->toDateString()
                    : now()->toDateString(),
                'counted_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($batches as $batch) {
                ButcherStockCountLine::query()->create([
                    'stock_count_id' => $count->id,
                    'batch_id' => $batch->id,
                    'system_weight_kg' => $batch->remaining_weight_kg,
                ]);
            }

            return $count->fresh(['lines.batch', 'outlet', 'countedByUser']);
        });
    }

    public function updateLines(ButcherStockCount $count, array $lines): void
    {
        if (! $count->isDraft()) {
            throw ValidationException::withMessages([
                'count' => [__('Completed stock counts cannot be edited.')],
            ]);
        }

        DB::transaction(function () use ($count, $lines) {
            foreach ($lines as $lineData) {
                $line = $count->lines()->whereKey((int) $lineData['id'])->first();
                if ($line === null) {
                    continue;
                }

                $counted = isset($lineData['counted_weight_kg']) && $lineData['counted_weight_kg'] !== ''
                    ? (float) $lineData['counted_weight_kg']
                    : null;

                $line->update([
                    'counted_weight_kg' => $counted,
                    'variance_kg' => $counted === null
                        ? null
                        : round($counted - (float) $line->system_weight_kg, 3),
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });
    }

    public function completeCount(ButcherStockCount $count, User $user, bool $applyVariances = false): ButcherStockCount
    {
        if (! $count->isDraft()) {
            throw ValidationException::withMessages([
                'count' => [__('This stock count is already completed.')],
            ]);
        }

        $count->load('lines.batch');

        $missing = $count->lines->filter(fn (ButcherStockCountLine $line) => $line->counted_weight_kg === null);
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'lines' => [__('Enter counted weight for every batch before completing.')],
            ]);
        }

        return DB::transaction(function () use ($count, $user, $applyVariances) {
            if ($applyVariances) {
                foreach ($count->lines as $line) {
                    $variance = (float) $line->variance_kg;
                    if (abs($variance) < 0.001) {
                        continue;
                    }

                    $this->storage->logAdjustment($line->batch, [
                        'weight_change_kg' => $variance,
                        'reason' => ButcherInventoryAdjustment::REASON_RECOUNT,
                        'stock_count_line_id' => $line->id,
                        'notes' => __('Applied from stock count :number', ['number' => $count->count_number]),
                    ], $user);
                }
            }

            $count->update([
                'status' => ButcherStockCount::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $count->fresh(['lines.batch', 'outlet', 'countedByUser']);
        });
    }

    private function generateCountNumber(Business $business): string
    {
        $sequence = $business->butcherStockCounts()->count() + 1;

        return sprintf('SC-%d-%04d', $business->id, $sequence);
    }
}
