<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryRejection;
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

        $order->update(['status' => $status]);
    }

    public function receiveDelivery(Business $business, array $data, User $user): ButcherDelivery
    {
        return DB::transaction(function () use ($business, $data, $user) {
            $weight = (float) $data['received_weight_kg'];
            $unitCost = (float) $data['unit_cost_per_kg'];
            $condition = (string) $data['condition'];
            $receivedAt = isset($data['received_at'])
                ? Carbon::parse($data['received_at'])
                : now();

            $delivery = $business->butcherDeliveries()->create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => (int) $data['supplier_id'],
                'delivery_number' => $this->generateDeliveryNumber($business),
                'meat_type' => (string) $data['meat_type'],
                'received_weight_kg' => $weight,
                'unit_cost_per_kg' => $unitCost,
                'total_cost' => round($weight * $unitCost, 2),
                'condition' => $condition,
                'received_at' => $receivedAt,
                'received_by' => $user->id,
                'outlet_id' => (int) $data['outlet_id'],
                'certificate_ref' => $data['certificate_ref'] ?? null,
                'certificate_issuer' => $data['certificate_issuer'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($delivery->createsInventory()) {
                $this->storage->createBatchFromDelivery($delivery, $data['storage_location'] ?? null);
            } else {
                $this->createRejectionLog($delivery, $user);
            }

            if ($delivery->purchase_order_id !== null) {
                ButcherPurchaseOrder::query()
                    ->whereKey($delivery->purchase_order_id)
                    ->where('business_id', $business->id)
                    ->update(['status' => ButcherPurchaseOrder::STATUS_DELIVERED]);
            }

            return $delivery->fresh([
                'supplier',
                'outlet',
                'purchaseOrder',
                'inventoryBatch',
                'rejection',
                'receivedByUser',
            ]);
        });
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

    private function createRejectionLog(ButcherDelivery $delivery, User $user): ButcherDeliveryRejection
    {
        return ButcherDeliveryRejection::query()->create([
            'business_id' => $delivery->business_id,
            'delivery_id' => $delivery->id,
            'supplier_id' => $delivery->supplier_id,
            'meat_type' => $delivery->meat_type,
            'rejected_weight_kg' => $delivery->received_weight_kg,
            'certificate_ref' => $delivery->certificate_ref,
            'certificate_issuer' => $delivery->certificate_issuer,
            'notes' => $delivery->notes,
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
