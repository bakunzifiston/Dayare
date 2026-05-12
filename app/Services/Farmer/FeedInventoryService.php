<?php

namespace App\Services\Farmer;

use App\Models\FeedInventory;
use App\Models\FeedInventoryMovement;
use App\Models\FeedingRecord;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FeedInventoryService
{
    public function receiveStock(FeedInventory $inventory, ?int $userId = null): FeedInventoryMovement
    {
        return $this->recordMovement(
            $inventory,
            FeedInventoryMovement::TYPE_RECEIVED,
            (float) $inventory->quantity_received,
            (float) $inventory->quantity_remaining,
            null,
            __('Initial stock receipt'),
            $userId,
        );
    }

    public function consumeForFeeding(FeedInventory $inventory, FeedingRecord $feedingRecord, float $quantity, ?int $userId = null): FeedInventoryMovement
    {
        if ($inventory->status === FeedInventory::STATUS_EXPIRED) {
            throw new InvalidArgumentException(__('Expired feed cannot be used.'));
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException(__('Feeding quantity must be greater than zero.'));
        }

        if ($quantity > (float) $inventory->quantity_remaining) {
            throw new InvalidArgumentException(__('Insufficient feed inventory.'));
        }

        return DB::transaction(function () use ($inventory, $feedingRecord, $quantity, $userId): FeedInventoryMovement {
            $inventory->refresh();
            $balance = round((float) $inventory->quantity_remaining - $quantity, 3);
            $inventory->update(['quantity_remaining' => $balance]);
            $inventory->syncStatus();

            return $this->recordMovement(
                $inventory,
                FeedInventoryMovement::TYPE_FEEDING,
                -$quantity,
                $balance,
                $feedingRecord->id,
                __('Feeding record :code', ['code' => $feedingRecord->feeding_code]),
                $userId,
            );
        });
    }

    private function recordMovement(
        FeedInventory $inventory,
        string $type,
        float $quantityChange,
        float $balanceAfter,
        ?int $feedingRecordId,
        ?string $notes,
        ?int $userId,
    ): FeedInventoryMovement {
        return $inventory->movements()->create([
            'movement_type' => $type,
            'quantity_change' => $quantityChange,
            'balance_after' => $balanceAfter,
            'feeding_record_id' => $feedingRecordId,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
    }
}
