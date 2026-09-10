<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherDelivery;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ButcherInventoryReconciliationService
{
    /**
     * Compare each batch remaining weight to the sum of its ledger quantities.
     *
     * @return Collection<int, array{
     *   batch_id: int,
     *   batch_number: string,
     *   remaining_weight_kg: float,
     *   ledger_sum_kg: float,
     *   delta_kg: float
     * }>
     */
    public function findMismatches(?Business $business = null, float $tolerance = 0.001): Collection
    {
        $batchesQuery = ButcherInventoryBatch::query()->orderBy('id');
        if ($business !== null) {
            $batchesQuery->where('business_id', $business->id);
        }

        $mismatches = collect();

        $batchesQuery->chunkById(100, function ($batches) use ($mismatches, $tolerance): void {
            $batchIds = $batches->pluck('id');
            $sums = ButcherInventoryMovement::query()
                ->whereIn('batch_id', $batchIds)
                ->selectRaw('batch_id, COALESCE(SUM(quantity_kg), 0) as ledger_sum')
                ->groupBy('batch_id')
                ->pluck('ledger_sum', 'batch_id');

            foreach ($batches as $batch) {
                $ledgerSum = round((float) ($sums[$batch->id] ?? 0), 3);
                $remaining = round((float) $batch->remaining_weight_kg, 3);
                $delta = round($remaining - $ledgerSum, 3);

                if (abs($delta) > $tolerance) {
                    $mismatches->push([
                        'batch_id' => (int) $batch->id,
                        'batch_number' => (string) $batch->batch_number,
                        'remaining_weight_kg' => $remaining,
                        'ledger_sum_kg' => $ledgerSum,
                        'delta_kg' => $delta,
                    ]);
                }
            }
        });

        return $mismatches;
    }

    /**
     * One-time backfill for batches created before ledger wiring.
     * Reconstructs receipt + historical disposal/adjustment/cutting movements
     * only when a batch currently has zero ledger rows.
     *
     * @return array{batches_backfilled: int, movements_created: int}
     */
    public function backfillMissingLedger(?Business $business = null): array
    {
        $batchesBackfilled = 0;
        $movementsCreated = 0;

        $query = ButcherInventoryBatch::query()->orderBy('id');
        if ($business !== null) {
            $query->where('business_id', $business->id);
        }

        $query->chunkById(50, function ($batches) use (&$batchesBackfilled, &$movementsCreated): void {
            foreach ($batches as $batch) {
                if (ButcherInventoryMovement::query()->where('batch_id', $batch->id)->exists()) {
                    continue;
                }

                DB::transaction(function () use ($batch, &$batchesBackfilled, &$movementsCreated): void {
                    $created = 0;
                    $initial = (float) $batch->initial_weight_kg;

                    ButcherInventoryMovement::record([
                        'business_id' => $batch->business_id,
                        'outlet_id' => $batch->outlet_id,
                        'batch_id' => $batch->id,
                        'type' => ButcherInventoryMovement::TYPE_RECEIPT,
                        'quantity_kg' => $initial,
                        'before_qty' => 0,
                        'after_qty' => $initial,
                        'reference_type' => $batch->delivery_id ? ButcherDelivery::class : null,
                        'reference_id' => $batch->delivery_id,
                        'occurred_at' => $batch->received_at,
                    ]);
                    $created++;

                    $running = $initial;

                    foreach (ButcherDisposalLog::query()->where('batch_id', $batch->id)->orderBy('disposed_at')->orderBy('id')->get() as $log) {
                        $qty = (float) $log->weight_disposed_kg;
                        $after = round($running - $qty, 3);
                        ButcherInventoryMovement::record([
                            'business_id' => $batch->business_id,
                            'outlet_id' => $batch->outlet_id,
                            'batch_id' => $batch->id,
                            'type' => ButcherInventoryMovement::TYPE_DISPOSAL,
                            'quantity_kg' => -1 * $qty,
                            'before_qty' => $running,
                            'after_qty' => max(0, $after),
                            'reference_type' => ButcherDisposalLog::class,
                            'reference_id' => $log->id,
                            'actor_id' => $log->disposed_by,
                            'occurred_at' => $log->disposed_at,
                        ]);
                        $running = max(0, $after);
                        $created++;
                    }

                    foreach (ButcherInventoryAdjustment::query()->where('batch_id', $batch->id)->orderBy('adjusted_at')->orderBy('id')->get() as $adj) {
                        $change = (float) $adj->weight_change_kg;
                        $after = round($running + $change, 3);
                        $type = $adj->stock_count_line_id
                            ? ButcherInventoryMovement::TYPE_STOCK_COUNT_VARIANCE
                            : ButcherInventoryMovement::TYPE_ADJUSTMENT;
                        ButcherInventoryMovement::record([
                            'business_id' => $batch->business_id,
                            'outlet_id' => $batch->outlet_id,
                            'batch_id' => $batch->id,
                            'type' => $type,
                            'quantity_kg' => $change,
                            'before_qty' => $running,
                            'after_qty' => max(0, $after),
                            'reference_type' => ButcherInventoryAdjustment::class,
                            'reference_id' => $adj->id,
                            'actor_id' => $adj->adjusted_by,
                            'occurred_at' => $adj->adjusted_at,
                        ]);
                        $running = max(0, $after);
                        $created++;
                    }

                    foreach (ButcherCuttingSession::query()->where('batch_id', $batch->id)->orderBy('id')->get() as $session) {
                        $qty = (float) $session->source_weight_kg;
                        $after = round($running - $qty, 3);
                        ButcherInventoryMovement::record([
                            'business_id' => $batch->business_id,
                            'outlet_id' => $batch->outlet_id,
                            'batch_id' => $batch->id,
                            'type' => ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION,
                            'quantity_kg' => -1 * $qty,
                            'before_qty' => $running,
                            'after_qty' => max(0, $after),
                            'reference_type' => ButcherCuttingSession::class,
                            'reference_id' => $session->id,
                            'occurred_at' => $session->closed_at ?? $session->created_at,
                        ]);
                        $running = max(0, $after);
                        $created++;
                    }

                    $batchesBackfilled++;
                    $movementsCreated += $created;
                });
            }
        });

        return [
            'batches_backfilled' => $batchesBackfilled,
            'movements_created' => $movementsCreated,
        ];
    }
}
