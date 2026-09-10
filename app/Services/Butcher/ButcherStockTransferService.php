<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherStockTransfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherStockTransferService
{
    public function transfer(Business $business, array $data, User $user): ButcherStockTransfer
    {
        return DB::transaction(function () use ($business, $data, $user) {
            $fromOutletId = (int) $data['from_outlet_id'];
            $toOutletId = (int) $data['to_outlet_id'];
            $quantity = round((float) $data['quantity_kg'], 3);

            if ($fromOutletId === $toOutletId) {
                throw ValidationException::withMessages([
                    'to_outlet_id' => [__('Destination outlet must be different from the source outlet.')],
                ]);
            }

            $outletIds = $business->butcherOutlets()
                ->whereIn('id', [$fromOutletId, $toOutletId])
                ->pluck('id')
                ->all();

            if (count($outletIds) !== 2) {
                throw ValidationException::withMessages([
                    'to_outlet_id' => [__('Both outlets must belong to this business.')],
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity_kg' => [__('Transfer quantity must be greater than zero.')],
                ]);
            }

            /** @var ButcherInventoryBatch $source */
            $source = ButcherInventoryBatch::query()
                ->where('business_id', $business->id)
                ->whereKey((int) $data['batch_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $source->outlet_id !== $fromOutletId) {
                throw ValidationException::withMessages([
                    'batch_id' => [__('Selected batch does not belong to the source outlet.')],
                ]);
            }

            $before = (float) $source->remaining_weight_kg;
            if ($quantity > $before + 0.0005) {
                throw ValidationException::withMessages([
                    'quantity_kg' => [__('Cannot transfer more than :max kg available on this batch.', [
                        'max' => number_format($before, 3),
                    ])],
                ]);
            }

            $afterSource = round($before - $quantity, 3);
            $sourceStatus = $afterSource <= 0
                ? ButcherInventoryBatch::STATUS_FULLY_USED
                : ButcherInventoryBatch::STATUS_PARTIALLY_USED;

            $source->update([
                'remaining_weight_kg' => max(0, $afterSource),
                'status' => $sourceStatus,
            ]);

            $transferredAt = isset($data['transferred_at'])
                ? Carbon::parse($data['transferred_at'])
                : now();

            $destination = ButcherInventoryBatch::query()->create([
                'business_id' => $business->id,
                'delivery_id' => $source->delivery_id,
                'delivery_line_id' => $source->delivery_line_id,
                'outlet_id' => $toOutletId,
                'batch_number' => $this->generateTransferBatchNumber((int) $business->id),
                'meat_type' => $source->meat_type,
                'initial_weight_kg' => $quantity,
                'remaining_weight_kg' => $quantity,
                'unit_cost_per_kg' => $source->unit_cost_per_kg,
                'status' => ButcherInventoryBatch::STATUS_IN_STORAGE,
                'received_at' => $source->received_at,
                'best_before_date' => $source->best_before_date,
                'storage_location' => $data['storage_location'] ?? $source->storage_location,
            ]);

            $transfer = ButcherStockTransfer::query()->create([
                'business_id' => $business->id,
                'from_outlet_id' => $fromOutletId,
                'to_outlet_id' => $toOutletId,
                'batch_id' => $source->id,
                'destination_batch_id' => $destination->id,
                'quantity_kg' => $quantity,
                'transferred_by' => $user->id,
                'transferred_at' => $transferredAt,
                'notes' => $data['notes'] ?? null,
            ]);

            ButcherInventoryMovement::record([
                'business_id' => $business->id,
                'outlet_id' => $fromOutletId,
                'batch_id' => $source->id,
                'type' => ButcherInventoryMovement::TYPE_TRANSFER_OUT,
                'quantity_kg' => -1 * $quantity,
                'before_qty' => $before,
                'after_qty' => max(0, $afterSource),
                'reference_type' => ButcherStockTransfer::class,
                'reference_id' => $transfer->id,
                'actor_id' => $user->id,
                'occurred_at' => $transferredAt,
            ]);

            ButcherInventoryMovement::record([
                'business_id' => $business->id,
                'outlet_id' => $toOutletId,
                'batch_id' => $destination->id,
                'type' => ButcherInventoryMovement::TYPE_TRANSFER_IN,
                'quantity_kg' => $quantity,
                'before_qty' => 0,
                'after_qty' => $quantity,
                'reference_type' => ButcherStockTransfer::class,
                'reference_id' => $transfer->id,
                'actor_id' => $user->id,
                'occurred_at' => $transferredAt,
            ]);

            return $transfer->fresh([
                'fromOutlet',
                'toOutlet',
                'batch',
                'destinationBatch',
                'transferredByUser',
            ]);
        });
    }

    private function generateTransferBatchNumber(int $businessId): string
    {
        $sequence = ButcherInventoryBatch::query()->where('business_id', $businessId)->count() + 1;

        return sprintf('BATCH-%d-%04d', $businessId, $sequence);
    }
}
