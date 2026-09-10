<?php

namespace App\Services\Butcher;

use App\Exceptions\Butcher\InsufficientButcherStockException;
use App\Models\ButcherCutOutput;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryConsumptionService
{
    public function __construct(
        private readonly BatchSafetyGate $safetyGate,
    ) {}
    /**
     * Consume quantity FIFO across active batches for an outlet + meat type.
     *
     * @return list<array{batch: ButcherInventoryBatch, quantity_kg: float, before_qty: float, after_qty: float}>
     */
    public function consume(
        int $businessId,
        int $outletId,
        string $meatType,
        float $quantityKg,
        ?User $actor = null,
        ?string $storageLocation = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $movementType = ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION,
        ?\DateTimeInterface $occurredAt = null,
    ): array {
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException('Consumption quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $businessId,
            $outletId,
            $meatType,
            $quantityKg,
            $actor,
            $storageLocation,
            $referenceType,
            $referenceId,
            $movementType,
            $occurredAt,
        ) {
            $query = ButcherInventoryBatch::query()
                ->where('business_id', $businessId)
                ->where('outlet_id', $outletId)
                ->where('meat_type', $meatType)
                ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
                ->where('remaining_weight_kg', '>', 0)
                ->orderBy('received_at')
                ->orderBy('id')
                ->lockForUpdate();

            if ($storageLocation !== null && $storageLocation !== '') {
                $query->where('storage_location', $storageLocation);
            }

            /** @var \Illuminate\Support\Collection<int, ButcherInventoryBatch> $batches */
            $batches = $query->get();
            $available = round((float) $batches->sum('remaining_weight_kg'), 3);

            if ($available + 0.0005 < $quantityKg) {
                throw InsufficientButcherStockException::forRequest($quantityKg, $available, $meatType);
            }

            return $this->applyConsumption(
                $batches,
                $quantityKg,
                $actor,
                $referenceType,
                $referenceId,
                $movementType,
                $occurredAt,
            );
        });
    }

    /**
     * Consume a declared quantity from one specific batch (Phase 4 cutting close).
     *
     * @return list<array{batch: ButcherInventoryBatch, quantity_kg: float, before_qty: float, after_qty: float}>
     */
    public function consumeFromBatch(
        int $businessId,
        int $batchId,
        float $quantityKg,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $movementType = ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION,
        ?\DateTimeInterface $occurredAt = null,
    ): array {
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException('Consumption quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $businessId,
            $batchId,
            $quantityKg,
            $actor,
            $referenceType,
            $referenceId,
            $movementType,
            $occurredAt,
        ) {
            /** @var ButcherInventoryBatch $batch */
            $batch = ButcherInventoryBatch::query()
                ->where('business_id', $businessId)
                ->whereKey($batchId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($batch->status, ButcherInventoryBatch::ACTIVE_STATUSES, true)
                && $batch->status !== ButcherInventoryBatch::STATUS_EXPIRED) {
                throw InsufficientButcherStockException::forRequest(
                    $quantityKg,
                    0,
                    (string) $batch->meat_type
                );
            }

            // Expired batches are not consumable for cutting.
            if ($batch->status === ButcherInventoryBatch::STATUS_EXPIRED || $batch->isExpired()) {
                throw InsufficientButcherStockException::forRequest(
                    $quantityKg,
                    0,
                    (string) $batch->meat_type
                );
            }

            $available = round((float) $batch->remaining_weight_kg, 3);
            if ($available + 0.0005 < $quantityKg) {
                throw InsufficientButcherStockException::forRequest(
                    $quantityKg,
                    $available,
                    (string) $batch->meat_type
                );
            }

            return $this->applyConsumption(
                collect([$batch]),
                $quantityKg,
                $actor,
                $referenceType,
                $referenceId,
                $movementType,
                $occurredAt,
            );
        });
    }

    /**
     * Consume cut-output stock FIFO by cut type (optional outlet scope via cutting session).
     *
     * @return list<array{cut_output: ButcherCutOutput, quantity_kg: float, before_qty: float, after_qty: float}>
     */
    public function consumeCutOutputs(
        int $businessId,
        int $cutTypeId,
        float $quantityKg,
        ?int $outletId = null,
        ?int $preferredCutOutputId = null,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $movementType = ButcherInventoryMovement::TYPE_SALE,
        ?\DateTimeInterface $occurredAt = null,
        ?string $safetyOverrideReason = null,
        string $safetyContextType = \App\Models\ButcherComplianceOverride::CONTEXT_SALE,
    ): array {
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException('Consumption quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $businessId,
            $cutTypeId,
            $quantityKg,
            $outletId,
            $preferredCutOutputId,
            $actor,
            $referenceType,
            $referenceId,
            $movementType,
            $occurredAt,
            $safetyOverrideReason,
            $safetyContextType,
        ) {
            if ($preferredCutOutputId !== null) {
                $output = ButcherCutOutput::query()
                    ->where('business_id', $businessId)
                    ->where('cut_type_id', $cutTypeId)
                    ->whereKey($preferredCutOutputId)
                    ->lockForUpdate()
                    ->first();

                if ($output === null) {
                    throw ValidationException::withMessages([
                        'items' => [__('Selected cut batch was not found.')],
                    ]);
                }

                $output->loadMissing(['session.sources.batch', 'session.batch']);
                $this->safetyGate->assertCutOutputSafe(
                    $output,
                    $actor,
                    $safetyOverrideReason,
                    $safetyContextType,
                    $referenceId,
                );

                $available = round((float) $output->remaining_weight_kg, 3);
                if ($available + 0.0005 < $quantityKg) {
                    throw ValidationException::withMessages([
                        'items' => [__('Insufficient stock for selected cut batch.')],
                    ]);
                }

                return $this->applyCutOutputConsumption(
                    collect([$output]),
                    $quantityKg,
                    $actor,
                    $referenceType,
                    $referenceId,
                    $movementType,
                    $occurredAt,
                    $outletId,
                );
            }

            $query = ButcherCutOutput::query()
                ->where('business_id', $businessId)
                ->where('cut_type_id', $cutTypeId)
                ->where('remaining_weight_kg', '>', 0)
                ->orderBy('id')
                ->lockForUpdate();

            if ($outletId !== null) {
                $query->whereHas('session', fn ($q) => $q->where('outlet_id', $outletId));
            }

            $outputs = $query->get();
            $outputs->loadMissing(['session.sources.batch', 'session.batch']);

            $usable = collect();
            $blockedNumbers = [];

            foreach ($outputs as $output) {
                $session = $output->session;
                $batches = $session ? $this->safetyGate->batchesForSession($session) : collect();
                $hasIssues = $batches->contains(fn ($batch) => $this->safetyGate->issuesForBatch($batch) !== []);

                if ($hasIssues) {
                    if ($safetyOverrideReason) {
                        $this->safetyGate->assertCutOutputSafe(
                            $output,
                            $actor,
                            $safetyOverrideReason,
                            $safetyContextType,
                            $referenceId,
                        );
                        $usable->push($output);
                    } else {
                        foreach ($batches as $batch) {
                            if ($this->safetyGate->issuesForBatch($batch) !== []) {
                                $blockedNumbers[] = $batch->batch_number;
                            }
                        }
                    }
                } else {
                    // Re-check under lock immediately before use (race with concurrent temp logs).
                    $this->safetyGate->assertCutOutputSafe(
                        $output,
                        $actor,
                        null,
                        $safetyContextType,
                        $referenceId,
                    );
                    $usable->push($output);
                }
            }

            $available = round((float) $usable->sum('remaining_weight_kg'), 3);

            if ($available + 0.0005 < $quantityKg) {
                $message = __('Insufficient cut stock available.');
                if ($blockedNumbers !== []) {
                    $message = __('Insufficient safe cut stock available. Blocked batches: :batches. A Manager/Owner override is required to sell from them.', [
                        'batches' => implode(', ', array_unique($blockedNumbers)),
                    ]);
                }

                throw ValidationException::withMessages([
                    'items' => [$message],
                ]);
            }

            return $this->applyCutOutputConsumption(
                $usable,
                $quantityKg,
                $actor,
                $referenceType,
                $referenceId,
                $movementType,
                $occurredAt,
                $outletId,
            );
        });
    }

    /**
     * Restore weight onto a cut output and write a return_in ledger row.
     */
    public function restoreCutOutput(
        int $businessId,
        int $cutOutputId,
        float $quantityKg,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?\DateTimeInterface $occurredAt = null,
    ): ButcherCutOutput {
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException('Restore quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $businessId,
            $cutOutputId,
            $quantityKg,
            $actor,
            $referenceType,
            $referenceId,
            $occurredAt,
        ) {
            /** @var ButcherCutOutput $output */
            $output = ButcherCutOutput::query()
                ->where('business_id', $businessId)
                ->whereKey($cutOutputId)
                ->lockForUpdate()
                ->firstOrFail();

            $output->loadMissing('session');

            $before = (float) $output->remaining_weight_kg;
            $after = round($before + $quantityKg, 3);
            $output->update(['remaining_weight_kg' => $after]);

            $outletId = $output->session?->outlet_id;

            ButcherInventoryMovement::record([
                'business_id' => $businessId,
                'outlet_id' => $outletId,
                'cut_output_id' => $output->id,
                'batch_id' => $output->session?->batch_id,
                'type' => ButcherInventoryMovement::TYPE_RETURN_IN,
                'quantity_kg' => $quantityKg,
                'before_qty' => $before,
                'after_qty' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'actor_id' => $actor?->id,
                'occurred_at' => $occurredAt ?? now(),
            ]);

            return $output->fresh();
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ButcherInventoryBatch>  $batches
     * @return list<array{batch: ButcherInventoryBatch, quantity_kg: float, before_qty: float, after_qty: float}>
     */
    private function applyConsumption(
        $batches,
        float $quantityKg,
        ?User $actor,
        ?string $referenceType,
        ?int $referenceId,
        string $movementType,
        ?\DateTimeInterface $occurredAt,
    ): array {
        $remainingToConsume = round($quantityKg, 3);
        $allocations = [];
        $at = $occurredAt ?? now();

        foreach ($batches as $batch) {
            if ($remainingToConsume <= 0) {
                break;
            }

            $before = (float) $batch->remaining_weight_kg;
            $take = min($before, $remainingToConsume);
            $after = round($before - $take, 3);

            $status = $after <= 0
                ? ButcherInventoryBatch::STATUS_FULLY_USED
                : ButcherInventoryBatch::STATUS_PARTIALLY_USED;

            $batch->update([
                'remaining_weight_kg' => max(0, $after),
                'status' => $status,
            ]);

            ButcherInventoryMovement::record([
                'business_id' => $batch->business_id,
                'outlet_id' => $batch->outlet_id,
                'batch_id' => $batch->id,
                'type' => $movementType,
                'quantity_kg' => -1 * $take,
                'before_qty' => $before,
                'after_qty' => max(0, $after),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'actor_id' => $actor?->id,
                'occurred_at' => $at,
            ]);

            $allocations[] = [
                'batch' => $batch->fresh(),
                'quantity_kg' => $take,
                'before_qty' => $before,
                'after_qty' => max(0, $after),
            ];

            $remainingToConsume = round($remainingToConsume - $take, 3);
        }

        return $allocations;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ButcherCutOutput>  $outputs
     * @return list<array{cut_output: ButcherCutOutput, quantity_kg: float, before_qty: float, after_qty: float}>
     */
    private function applyCutOutputConsumption(
        $outputs,
        float $quantityKg,
        ?User $actor,
        ?string $referenceType,
        ?int $referenceId,
        string $movementType,
        ?\DateTimeInterface $occurredAt,
        ?int $outletId,
    ): array {
        $remainingToConsume = round($quantityKg, 3);
        $allocations = [];
        $at = $occurredAt ?? now();

        foreach ($outputs as $output) {
            if ($remainingToConsume <= 0) {
                break;
            }

            $before = (float) $output->remaining_weight_kg;
            $take = min($before, $remainingToConsume);
            $after = round($before - $take, 3);

            $output->update(['remaining_weight_kg' => max(0, $after)]);

            $sessionOutletId = $outletId ?? $output->session?->outlet_id;

            ButcherInventoryMovement::record([
                'business_id' => $output->business_id,
                'outlet_id' => $sessionOutletId,
                'cut_output_id' => $output->id,
                'batch_id' => $output->session?->batch_id,
                'type' => $movementType,
                'quantity_kg' => -1 * $take,
                'before_qty' => $before,
                'after_qty' => max(0, $after),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'actor_id' => $actor?->id,
                'occurred_at' => $at,
            ]);

            $allocations[] = [
                'cut_output' => $output->fresh(),
                'quantity_kg' => $take,
                'before_qty' => $before,
                'after_qty' => max(0, $after),
            ];

            $remainingToConsume = round($remainingToConsume - $take, 3);
        }

        return $allocations;
    }

    public function availableKg(
        int $businessId,
        int $outletId,
        string $meatType,
        ?string $storageLocation = null,
    ): float {
        $query = ButcherInventoryBatch::query()
            ->where('business_id', $businessId)
            ->where('outlet_id', $outletId)
            ->where('meat_type', $meatType)
            ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES)
            ->where('remaining_weight_kg', '>', 0);

        if ($storageLocation !== null && $storageLocation !== '') {
            $query->where('storage_location', $storageLocation);
        }

        return round((float) $query->sum('remaining_weight_kg'), 3);
    }
}
