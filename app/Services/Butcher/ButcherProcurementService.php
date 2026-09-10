<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherDeliveryRejection;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherPurchaseOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherProcurementService
{
    public function __construct(
        private readonly ButcherStorageService $storage,
    ) {}

    public function createPurchaseOrder(Business $business, array $data): ButcherPurchaseOrder
    {
        return $business->butcherPurchaseOrders()->create([
            'supplier_id' => (int) $data['supplier_id'],
            'po_number' => $this->generatePoNumber($business),
            'meat_type' => (string) $data['meat_type'],
            'requested_weight_kg' => $data['requested_weight_kg'],
            'requested_date' => $data['requested_date'],
            'status' => ButcherPurchaseOrder::STATUS_DRAFT,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateOrderStatus(ButcherPurchaseOrder $order, string $status): void
    {
        if (! in_array($status, ButcherPurchaseOrder::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('Invalid purchase order status.')],
            ]);
        }

        if ($order->status === ButcherPurchaseOrder::STATUS_DELIVERED
            && $status !== ButcherPurchaseOrder::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'status' => [__('Delivered purchase orders cannot change status.')],
            ]);
        }

        $allowed = match ($order->status) {
            ButcherPurchaseOrder::STATUS_DRAFT => [
                ButcherPurchaseOrder::STATUS_SENT,
                ButcherPurchaseOrder::STATUS_CANCELLED,
                ButcherPurchaseOrder::STATUS_DRAFT,
            ],
            ButcherPurchaseOrder::STATUS_SENT => [
                ButcherPurchaseOrder::STATUS_CONFIRMED,
                ButcherPurchaseOrder::STATUS_CANCELLED,
                ButcherPurchaseOrder::STATUS_SENT,
            ],
            ButcherPurchaseOrder::STATUS_CONFIRMED => [
                ButcherPurchaseOrder::STATUS_DELIVERED,
                ButcherPurchaseOrder::STATUS_CANCELLED,
                ButcherPurchaseOrder::STATUS_CONFIRMED,
            ],
            ButcherPurchaseOrder::STATUS_CANCELLED => [ButcherPurchaseOrder::STATUS_CANCELLED],
            ButcherPurchaseOrder::STATUS_DELIVERED => [ButcherPurchaseOrder::STATUS_DELIVERED],
            default => ButcherPurchaseOrder::STATUSES,
        };

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => [__('This purchase order cannot move to :status from :current.', [
                    'status' => $status,
                    'current' => $order->status,
                ])],
            ]);
        }

        $order->update(['status' => $status]);
    }

    /**
     * Create and post a delivery with per-line outcomes in one atomic transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function receiveDelivery(Business $business, array $data, User $user): ButcherDelivery
    {
        return DB::transaction(function () use ($business, $data, $user) {
            $lines = $this->normalizeDeliveryLines($data);
            if ($lines === []) {
                throw ValidationException::withMessages([
                    'lines' => [__('Add at least one delivery line with an outcome.')],
                ]);
            }

            $this->assertLineWeights($lines);

            $receivedAt = isset($data['received_at'])
                ? Carbon::parse($data['received_at'])
                : now();

            $totals = $this->aggregateLineTotals($lines);

            // Deprecated delivery-level fields kept for backward-compatible reads.
            $delivery = $business->butcherDeliveries()->create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => (int) $data['supplier_id'],
                'delivery_number' => $this->generateDeliveryNumber($business),
                'meat_type' => (string) $lines[0]['meat_type'],
                'received_weight_kg' => $totals['received_weight_kg'],
                'unit_cost_per_kg' => $totals['avg_unit_cost'],
                'total_cost' => $totals['accepted_cost'],
                'condition' => $totals['derived_condition'],
                'received_at' => $receivedAt,
                'received_by' => $user->id,
                'outlet_id' => (int) $data['outlet_id'],
                'certificate_ref' => $data['certificate_ref'] ?? null,
                'certificate_issuer' => $data['certificate_issuer'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $index => $lineData) {
                $line = $delivery->lines()->create([
                    'meat_type' => (string) $lineData['meat_type'],
                    'expected_weight_kg' => $lineData['expected_weight_kg'] ?? null,
                    'received_weight_kg' => $lineData['received_weight_kg'],
                    'temperature_c' => $lineData['temperature_c'] ?? null,
                    'condition_notes' => $lineData['condition_notes'] ?? null,
                    'outcome' => (string) $lineData['outcome'],
                    'accepted_weight_kg' => $lineData['accepted_weight_kg'],
                    'rejected_weight_kg' => $lineData['rejected_weight_kg'],
                    'unit_cost' => $lineData['unit_cost'],
                ]);

                if ($line->createsInventory()) {
                    $batch = $this->storage->createBatchFromDelivery(
                        $delivery,
                        $data['storage_location'] ?? null,
                        (float) $line->accepted_weight_kg,
                        (float) $line->unit_cost,
                        (string) $line->meat_type,
                        (int) $line->id,
                    );

                    ButcherInventoryMovement::record([
                        'business_id' => $business->id,
                        'outlet_id' => $delivery->outlet_id,
                        'batch_id' => $batch->id,
                        'type' => ButcherInventoryMovement::TYPE_RECEIPT,
                        'quantity_kg' => (float) $line->accepted_weight_kg,
                        'before_qty' => 0,
                        'after_qty' => (float) $line->accepted_weight_kg,
                        'reference_type' => ButcherDelivery::class,
                        'reference_id' => $delivery->id,
                        'actor_id' => $user->id,
                        'occurred_at' => $receivedAt,
                    ]);
                }

                if ($line->createsRejection()) {
                    $this->createRejectionLog($delivery, $user, $line);
                }
            }

            if ($delivery->purchase_order_id !== null) {
                $this->markPurchaseOrderDelivered($business, $delivery, $totals['received_weight_kg']);
            }

            return $delivery->fresh([
                'supplier',
                'outlet',
                'purchaseOrder',
                'lines.inventoryBatch',
                'lines.rejection',
                'inventoryBatches',
                'rejections',
                'receivedByUser',
            ]);
        });
    }

    /**
     * @return array{
     *   period: string,
     *   deliveries_total: int,
     *   received_weight_kg: float,
     *   total_spend: float,
     *   rejected_deliveries: int,
     *   inventory_batches_created: int
     * }
     */
    public function getReceivingSummary(Business $business, string $period = '30d'): array
    {
        $since = $this->periodStart($period);
        $deliveriesQuery = $business->butcherDeliveries()->where('received_at', '>=', $since);

        return [
            'period' => $period,
            'deliveries_total' => (int) (clone $deliveriesQuery)->count(),
            'received_weight_kg' => (float) (clone $deliveriesQuery)
                ->whereIn('condition', [ButcherDelivery::CONDITION_GOOD, ButcherDelivery::CONDITION_FAIR])
                ->sum('received_weight_kg'),
            'total_spend' => (float) (clone $deliveriesQuery)
                ->whereIn('condition', [ButcherDelivery::CONDITION_GOOD, ButcherDelivery::CONDITION_FAIR])
                ->sum('total_cost'),
            'rejected_deliveries' => (int) (clone $deliveriesQuery)
                ->where('condition', ButcherDelivery::CONDITION_REJECTED)
                ->count(),
            'inventory_batches_created' => (int) $business->butcherInventoryBatches()
                ->where('created_at', '>=', $since)
                ->count(),
        ];
    }

    /**
     * @return array{
     *   period: string,
     *   orders_total: int,
     *   orders_open: int,
     *   deliveries_total: int,
     *   received_weight_kg: float,
     *   total_spend: float,
     *   rejected_deliveries: int,
     *   inventory_batches_created: int,
     *   recent_orders: \Illuminate\Support\Collection,
     *   recent_deliveries: \Illuminate\Support\Collection
     * }
     */
    public function getProcurementSummary(Business $business, string $period = '30d'): array
    {
        $since = $this->periodStart($period);

        $ordersQuery = $business->butcherPurchaseOrders()->where('created_at', '>=', $since);
        $deliveriesQuery = $business->butcherDeliveries()->where('received_at', '>=', $since);

        $recentOrders = $business->butcherPurchaseOrders()
            ->with('supplier')
            ->latest()
            ->limit(5)
            ->get();

        $recentDeliveries = $business->butcherDeliveries()
            ->with(['supplier', 'outlet'])
            ->latest('received_at')
            ->limit(5)
            ->get();

        return [
            'period' => $period,
            'orders_total' => (int) (clone $ordersQuery)->count(),
            'orders_open' => (int) (clone $ordersQuery)
                ->whereNotIn('status', [
                    ButcherPurchaseOrder::STATUS_DELIVERED,
                    ButcherPurchaseOrder::STATUS_CANCELLED,
                ])
                ->count(),
            'deliveries_total' => (int) (clone $deliveriesQuery)->count(),
            'received_weight_kg' => (float) (clone $deliveriesQuery)
                ->whereIn('condition', [ButcherDelivery::CONDITION_GOOD, ButcherDelivery::CONDITION_FAIR])
                ->sum('received_weight_kg'),
            'total_spend' => (float) (clone $deliveriesQuery)
                ->whereIn('condition', [ButcherDelivery::CONDITION_GOOD, ButcherDelivery::CONDITION_FAIR])
                ->sum('total_cost'),
            'rejected_deliveries' => (int) (clone $deliveriesQuery)
                ->where('condition', ButcherDelivery::CONDITION_REJECTED)
                ->count(),
            'inventory_batches_created' => (int) $business->butcherInventoryBatches()
                ->where('created_at', '>=', $since)
                ->count(),
            'recent_orders' => $recentOrders,
            'recent_deliveries' => $recentDeliveries,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function assertLineWeights(array $lines): void
    {
        foreach ($lines as $index => $line) {
            $received = round((float) $line['received_weight_kg'], 3);
            $accepted = round((float) $line['accepted_weight_kg'], 3);
            $rejected = round((float) $line['rejected_weight_kg'], 3);
            $outcome = (string) $line['outcome'];

            if (abs(($accepted + $rejected) - $received) > 0.001) {
                throw ValidationException::withMessages([
                    "lines.$index.accepted_weight_kg" => [
                        __('Accepted + rejected weight must equal received weight for each line.'),
                    ],
                ]);
            }

            if ($outcome === ButcherDeliveryLine::OUTCOME_ACCEPTED && $rejected > 0.001) {
                throw ValidationException::withMessages([
                    "lines.$index.outcome" => [__('Accepted lines cannot include rejected weight.')],
                ]);
            }

            if ($outcome === ButcherDeliveryLine::OUTCOME_REJECTED && $accepted > 0.001) {
                throw ValidationException::withMessages([
                    "lines.$index.outcome" => [__('Rejected lines cannot include accepted weight.')],
                ]);
            }

            if ($outcome === ButcherDeliveryLine::OUTCOME_PARTIALLY_ACCEPTED
                && ($accepted <= 0 || $rejected <= 0)) {
                throw ValidationException::withMessages([
                    "lines.$index.outcome" => [__('Partially accepted lines need both accepted and rejected weight.')],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function normalizeDeliveryLines(array $data): array
    {
        if (! empty($data['lines']) && is_array($data['lines'])) {
            return array_values(array_map(function (array $line): array {
                $outcome = (string) $line['outcome'];
                $received = (float) $line['received_weight_kg'];
                $accepted = array_key_exists('accepted_weight_kg', $line)
                    ? (float) $line['accepted_weight_kg']
                    : match ($outcome) {
                        ButcherDeliveryLine::OUTCOME_ACCEPTED => $received,
                        ButcherDeliveryLine::OUTCOME_REJECTED => 0.0,
                        default => (float) ($line['accepted_weight_kg'] ?? 0),
                    };
                $rejected = array_key_exists('rejected_weight_kg', $line)
                    ? (float) $line['rejected_weight_kg']
                    : match ($outcome) {
                        ButcherDeliveryLine::OUTCOME_REJECTED => $received,
                        ButcherDeliveryLine::OUTCOME_ACCEPTED => 0.0,
                        default => (float) ($line['rejected_weight_kg'] ?? 0),
                    };

                return [
                    'meat_type' => $line['meat_type'],
                    'expected_weight_kg' => $line['expected_weight_kg'] ?? null,
                    'received_weight_kg' => $received,
                    'temperature_c' => $line['temperature_c'] ?? null,
                    'condition_notes' => $line['condition_notes'] ?? null,
                    'outcome' => $outcome,
                    'accepted_weight_kg' => $accepted,
                    'rejected_weight_kg' => $rejected,
                    'unit_cost' => (float) ($line['unit_cost'] ?? $line['unit_cost_per_kg'] ?? 0),
                ];
            }, $data['lines']));
        }

        // Legacy single-condition payload → one synthetic line.
        if (! empty($data['meat_type']) && isset($data['received_weight_kg'], $data['condition'])) {
            $weight = (float) $data['received_weight_kg'];
            $condition = (string) $data['condition'];
            $rejected = $condition === ButcherDelivery::CONDITION_REJECTED;
            $outcome = $rejected
                ? ButcherDeliveryLine::OUTCOME_REJECTED
                : ButcherDeliveryLine::OUTCOME_ACCEPTED;

            return [[
                'meat_type' => $data['meat_type'],
                'expected_weight_kg' => null,
                'received_weight_kg' => $weight,
                'temperature_c' => null,
                'condition_notes' => null,
                'outcome' => $outcome,
                'accepted_weight_kg' => $rejected ? 0.0 : $weight,
                'rejected_weight_kg' => $rejected ? $weight : 0.0,
                'unit_cost' => (float) ($data['unit_cost_per_kg'] ?? 0),
            ]];
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{received_weight_kg: float, accepted_cost: float, avg_unit_cost: float, derived_condition: string}
     */
    private function aggregateLineTotals(array $lines): array
    {
        $received = 0.0;
        $acceptedWeight = 0.0;
        $acceptedCost = 0.0;
        $anyAccepted = false;
        $anyRejected = false;

        foreach ($lines as $line) {
            $received += (float) $line['received_weight_kg'];
            $accepted = (float) $line['accepted_weight_kg'];
            $rejected = (float) $line['rejected_weight_kg'];
            $acceptedWeight += $accepted;
            $acceptedCost += $accepted * (float) $line['unit_cost'];
            if ($accepted > 0) {
                $anyAccepted = true;
            }
            if ($rejected > 0) {
                $anyRejected = true;
            }
        }

        $derived = match (true) {
            $anyAccepted && ! $anyRejected => ButcherDelivery::CONDITION_GOOD,
            $anyAccepted && $anyRejected => ButcherDelivery::CONDITION_FAIR,
            default => ButcherDelivery::CONDITION_REJECTED,
        };

        return [
            'received_weight_kg' => round($received, 3),
            'accepted_cost' => round($acceptedCost, 2),
            'avg_unit_cost' => $acceptedWeight > 0 ? round($acceptedCost / $acceptedWeight, 2) : 0.0,
            'derived_condition' => $derived,
        ];
    }

    private function markPurchaseOrderDelivered(
        Business $business,
        ButcherDelivery $delivery,
        float $receivedWeightKg,
    ): void {
        $order = ButcherPurchaseOrder::query()
            ->whereKey($delivery->purchase_order_id)
            ->where('business_id', $business->id)
            ->first();

        if ($order === null) {
            return;
        }

        $ordered = (float) $order->requested_weight_kg;
        $delta = round($receivedWeightKg - $ordered, 3);
        $note = null;
        if (abs($delta) > 0.001) {
            $note = $delta > 0
                ? __('Over-delivery vs PO: +:kg kg.', ['kg' => number_format($delta, 3)])
                : __('Under-delivery vs PO: :kg kg.', ['kg' => number_format(abs($delta), 3)]);
        }

        $notes = trim((string) $order->notes);
        if ($note !== null) {
            $notes = trim($notes === '' ? $note : $notes."\n".$note);
        }

        $order->update([
            'status' => ButcherPurchaseOrder::STATUS_DELIVERED,
            'notes' => $notes !== '' ? $notes : null,
        ]);
    }

    private function createRejectionLog(
        ButcherDelivery $delivery,
        User $user,
        ?ButcherDeliveryLine $line = null,
    ): ButcherDeliveryRejection {
        return ButcherDeliveryRejection::query()->create([
            'business_id' => $delivery->business_id,
            'delivery_id' => $delivery->id,
            'delivery_line_id' => $line?->id,
            'supplier_id' => $delivery->supplier_id,
            'meat_type' => $line?->meat_type ?? $delivery->meat_type,
            'rejected_weight_kg' => $line?->rejected_weight_kg ?? $delivery->received_weight_kg,
            'certificate_ref' => $delivery->certificate_ref,
            'certificate_issuer' => $delivery->certificate_issuer,
            'notes' => $line?->condition_notes ?? $delivery->notes,
            'rejected_by' => $user->id,
            'rejected_at' => $delivery->received_at,
        ]);
    }

    private function generatePoNumber(Business $business): string
    {
        $sequence = $business->butcherPurchaseOrders()->count() + 1;

        return sprintf('PO-%d-%04d', $business->id, $sequence);
    }

    private function generateDeliveryNumber(Business $business): string
    {
        $sequence = $business->butcherDeliveries()->count() + 1;

        return sprintf('DEL-%d-%04d', $business->id, $sequence);
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            default => now()->subDays(30),
        };
    }
}
