<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherOrder;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\User;
use App\Support\DomPdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ButcherSalesService
{
    public function __construct(
        private readonly ButcherCatalogService $catalog,
        private readonly InventoryConsumptionService $consumption,
    ) {}

    public function createSale(Business $business, array $data, User $user): ButcherSale
    {
        return DB::transaction(function () use ($business, $data, $user) {
            $customer = isset($data['customer_id'])
                ? ButcherCustomer::query()->where('business_id', $business->id)->find($data['customer_id'])
                : null;

            $sale = ButcherSale::query()->create([
                'business_id' => $business->id,
                'outlet_id' => (int) $data['outlet_id'],
                'sale_number' => $this->generateSaleNumber($business->id),
                'customer_id' => $customer?->id,
                'sale_date' => isset($data['sale_date']) ? Carbon::parse($data['sale_date'])->toDateString() : now()->toDateString(),
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'payment_method' => (string) $data['payment_method'],
                'status' => ButcherSale::STATUS_PENDING,
                'sold_by' => $user->id,
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                if (! empty($data['safety_override_reason'])) {
                    $itemData['_sale_safety_override_reason'] = (string) $data['safety_override_reason'];
                }
                $this->addSaleItem($sale, $itemData, $customer, $user);
            }

            $sale->refresh();
            $subtotal = round((float) $sale->items()->sum('subtotal'), 2);
            $discount = (float) $sale->discount_amount;
            $total = round(max($subtotal - $discount, 0), 2);

            $sale->update([
                'subtotal' => $subtotal,
                'total_amount' => $total,
            ]);

            $this->processPayment($sale->fresh(), [
                'payment_method' => $data['payment_method'],
                'amount_paid' => (float) ($data['amount_paid'] ?? $total),
                'split_payments' => $data['split_payments'] ?? [],
            ], $customer);

            $sale = $sale->fresh(['items.product', 'customer', 'outlet', 'payments']);
            $this->generateReceipt($sale);

            return $sale->fresh();
        });
    }

    /**
     * @return list<ButcherSaleItem>
     */
    public function addSaleItem(
        ButcherSale $sale,
        array $data,
        ?ButcherCustomer $customer = null,
        ?User $actor = null,
    ): array {
        if ($sale->status !== ButcherSale::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'sale' => [__('Cannot add items to this sale.')],
            ]);
        }

        $product = ButcherProduct::query()
            ->where('business_id', $sale->business_id)
            ->where('is_active', true)
            ->findOrFail((int) $data['product_id']);

        $tier = $customer?->tier;
        $unitPrice = isset($data['unit_price'])
            ? (float) $data['unit_price']
            : $this->catalog->resolvePrice($product, (int) $sale->outlet_id, $tier);

        $quantityKg = (float) ($data['quantity_kg'] ?? 0);
        $quantityUnits = isset($data['quantity_units']) ? (int) $data['quantity_units'] : null;

        if ($product->unit === ButcherProduct::UNIT_PER_KG) {
            if ($quantityKg <= 0) {
                throw ValidationException::withMessages([
                    'items' => [__('Enter weight in kg for :product.', ['product' => $product->name])],
                ]);
            }
        } elseif ($quantityUnits === null || $quantityUnits <= 0) {
            throw ValidationException::withMessages([
                'items' => [__('Enter quantity for :product.', ['product' => $product->name])],
            ]);
        }

        $preferredCutOutputId = isset($data['cut_output_id']) ? (int) $data['cut_output_id'] : null;
        $needsStock = $quantityKg > 0 && $product->cut_type_id !== null;

        if (! $needsStock) {
            $subtotal = $this->calculateLineSubtotal($product, $unitPrice, $quantityKg, $quantityUnits);

            return [
                ButcherSaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'cut_output_id' => null,
                    'product_id' => $product->id,
                    'quantity_kg' => $quantityKg,
                    'quantity_units' => $quantityUnits,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]),
            ];
        }

        $allocations = $this->consumption->consumeCutOutputs(
            businessId: (int) $sale->business_id,
            cutTypeId: (int) $product->cut_type_id,
            quantityKg: $quantityKg,
            outletId: (int) $sale->outlet_id,
            preferredCutOutputId: $preferredCutOutputId,
            actor: $actor,
            referenceType: ButcherSale::class,
            referenceId: (int) $sale->id,
            safetyOverrideReason: isset($data['safety_override_reason'])
                ? (string) $data['safety_override_reason']
                : ($data['_sale_safety_override_reason'] ?? null),
        );

        $items = [];
        $remainingKg = $quantityKg;
        $remainingSubtotal = $this->calculateLineSubtotal($product, $unitPrice, $quantityKg, $quantityUnits);
        $unitsAssigned = false;

        foreach ($allocations as $index => $allocation) {
            $allocKg = (float) $allocation['quantity_kg'];
            $isLast = $index === count($allocations) - 1;

            if ($product->unit === ButcherProduct::UNIT_PER_KG) {
                $lineSubtotal = $isLast
                    ? round($remainingSubtotal, 2)
                    : round($unitPrice * $allocKg, 2);
                $remainingSubtotal = round($remainingSubtotal - $lineSubtotal, 2);
            } else {
                $lineSubtotal = $isLast ? round($remainingSubtotal, 2) : 0.0;
                if ($isLast) {
                    $remainingSubtotal = 0.0;
                }
            }

            $lineUnits = null;
            if ($product->unit !== ButcherProduct::UNIT_PER_KG && ! $unitsAssigned) {
                $lineUnits = $quantityUnits;
                $unitsAssigned = true;
                if (! $isLast) {
                    $lineSubtotal = $this->calculateLineSubtotal($product, $unitPrice, 0, $quantityUnits);
                    $remainingSubtotal = 0.0;
                }
            }

            $items[] = ButcherSaleItem::query()->create([
                'sale_id' => $sale->id,
                'cut_output_id' => $allocation['cut_output']->id,
                'product_id' => $product->id,
                'quantity_kg' => $allocKg,
                'quantity_units' => $lineUnits,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ]);

            $remainingKg = round($remainingKg - $allocKg, 3);
        }

        return $items;
    }

    public function processPayment(ButcherSale $sale, array $paymentData, ?ButcherCustomer $customer = null): void
    {
        if ($sale->status === ButcherSale::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'sale' => [__('Cannot process payment for a cancelled sale.')],
            ]);
        }

        $method = (string) $paymentData['payment_method'];
        $total = (float) $sale->total_amount;
        $amountPaid = (float) ($paymentData['amount_paid'] ?? 0);

        if ($method === ButcherSale::PAYMENT_SPLIT) {
            $splits = $paymentData['split_payments'] ?? [];
            $splitTotal = round(collect($splits)->sum(fn ($p) => (float) ($p['amount'] ?? 0)), 2);

            if ($splitTotal < $total) {
                throw ValidationException::withMessages([
                    'split_payments' => [__('Split payments must cover the total amount.')],
                ]);
            }

            foreach ($splits as $split) {
                $sale->payments()->create([
                    'payment_method' => (string) $split['payment_method'],
                    'amount' => (float) $split['amount'],
                ]);
            }

            $amountPaid = $splitTotal;
            $changeGiven = round(max($amountPaid - $total, 0), 2);
        } elseif ($method === ButcherSale::PAYMENT_CREDIT) {
            $customer = $customer ?? $sale->customer;
            if ($customer === null) {
                throw ValidationException::withMessages([
                    'customer_id' => [__('A customer is required for credit sales.')],
                ]);
            }

            $creditAmount = round($total - $amountPaid, 2);
            if ($creditAmount > 0) {
                $this->assertCustomerCredit($customer, $creditAmount);
                $customer->update([
                    'outstanding_balance' => round((float) $customer->outstanding_balance + $creditAmount, 2),
                ]);
            }
            $changeGiven = 0;
        } else {
            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => [__('Amount paid must be at least the total.')],
                ]);
            }
            $changeGiven = round($amountPaid - $total, 2);
        }

        $sale->update([
            'payment_method' => $method,
            'amount_paid' => $amountPaid,
            'change_given' => $changeGiven ?? 0,
            'status' => ButcherSale::STATUS_COMPLETED,
        ]);
    }

    public function assertCustomerCredit(ButcherCustomer $customer, float $additionalCredit): void
    {
        $newBalance = round((float) $customer->outstanding_balance + $additionalCredit, 2);
        if ($newBalance > (float) $customer->credit_limit) {
            throw ValidationException::withMessages([
                'payment_method' => [__('Credit limit exceeded for this customer.')],
            ]);
        }
    }

    public function reverseCustomerCredit(ButcherCustomer $customer, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $customer = ButcherCustomer::query()->lockForUpdate()->findOrFail($customer->id);
        $customer->update([
            'outstanding_balance' => round(max((float) $customer->outstanding_balance - $amount, 0), 2),
        ]);
    }

    public function cancelSale(ButcherSale $sale, ?User $actor = null): void
    {
        if (! $sale->isCancellable()) {
            throw ValidationException::withMessages([
                'sale' => [__('This sale cannot be cancelled.')],
            ]);
        }

        DB::transaction(function () use ($sale, $actor) {
            $sale->load('items');

            foreach ($sale->items as $item) {
                if ($item->cut_output_id && (float) $item->quantity_kg > 0) {
                    $this->consumption->restoreCutOutput(
                        businessId: (int) $sale->business_id,
                        cutOutputId: (int) $item->cut_output_id,
                        quantityKg: (float) $item->quantity_kg,
                        actor: $actor,
                        referenceType: ButcherSale::class,
                        referenceId: (int) $sale->id,
                    );
                }
            }

            if ($sale->payment_method === ButcherSale::PAYMENT_CREDIT && $sale->customer_id) {
                $creditAmount = round((float) $sale->total_amount - (float) $sale->amount_paid, 2);
                if ($creditAmount > 0) {
                    $customer = ButcherCustomer::query()->find($sale->customer_id);
                    if ($customer) {
                        $this->reverseCustomerCredit($customer, $creditAmount);
                    }
                }
            }

            $sale->payments()->delete();
            $sale->update(['status' => ButcherSale::STATUS_CANCELLED]);
        });
    }

    public function generateReceipt(ButcherSale $sale): string
    {
        $sale->loadMissing(['items.product', 'customer', 'outlet', 'business', 'payments', 'soldByUser']);

        $path = sprintf('butcher-receipts/%d/%s.pdf', $sale->business_id, $sale->sale_number);

        $pdf = DomPdf::loadView('butcher.sales.documents.receipt', [
            'sale' => $sale,
            'business' => $sale->business,
        ])->setPaper('a5', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());
        $sale->update(['receipt_path' => $path]);

        return $path;
    }

    public function generateInvoice(ButcherSale $sale): string
    {
        $sale->loadMissing(['items.product', 'customer', 'outlet', 'business', 'payments', 'soldByUser']);

        $path = sprintf('butcher-invoices/%d/%s.pdf', $sale->business_id, $sale->sale_number);

        $pdf = DomPdf::loadView('butcher.sales.documents.invoice', [
            'sale' => $sale,
            'business' => $sale->business,
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());
        $sale->update(['invoice_path' => $path]);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailySalesSummary(Business $business, Carbon $date, ?int $outletId = null): array
    {
        $dateString = $date->toDateString();

        $sales = $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', $dateString)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->get();

        $byMethod = $sales->groupBy('payment_method')->map(fn ($group) => [
            'count' => $group->count(),
            'total' => round((float) $group->sum('total_amount'), 2),
        ]);

        return [
            'date' => $dateString,
            'sales_count' => $sales->count(),
            'gross_total' => round((float) $sales->sum('total_amount'), 2),
            'discount_total' => round((float) $sales->sum('discount_amount'), 2),
            'by_payment_method' => $byMethod,
            'cancelled_count' => $business->butcherSales()
                ->where('status', ButcherSale::STATUS_CANCELLED)
                ->whereDate('sale_date', $dateString)
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
                ->count(),
        ];
    }

    public function createCustomer(Business $business, array $data): ButcherCustomer
    {
        return $business->butcherCustomers()->create([
            'name' => (string) $data['name'],
            'phone' => (string) $data['phone'],
            'email' => $data['email'] ?? null,
            'tier' => (string) ($data['tier'] ?? ButcherCustomer::TIER_RETAIL),
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
        ]);
    }

    public function createOrder(Business $business, array $data): ButcherOrder
    {
        return DB::transaction(function () use ($business, $data) {
            $customer = ButcherCustomer::query()
                ->where('business_id', $business->id)
                ->findOrFail((int) $data['customer_id']);

            $order = ButcherOrder::query()->create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'outlet_id' => isset($data['outlet_id']) ? (int) $data['outlet_id'] : null,
                'order_number' => $this->generateOrderNumber($business->id),
                'order_date' => isset($data['order_date']) ? Carbon::parse($data['order_date'])->toDateString() : now()->toDateString(),
                'delivery_date' => isset($data['delivery_date']) ? Carbon::parse($data['delivery_date'])->toDateString() : null,
                'deposit_paid' => (float) ($data['deposit_paid'] ?? 0),
                'status' => ButcherOrder::STATUS_PENDING,
            ]);

            $total = 0;
            foreach ($data['items'] ?? [] as $itemData) {
                $product = ButcherProduct::query()
                    ->where('business_id', $business->id)
                    ->findOrFail((int) $itemData['product_id']);

                $unitPrice = $this->catalog->resolvePrice($product, null, $customer->tier);
                $quantityKg = (float) ($itemData['quantity_kg'] ?? 0);
                $quantityUnits = isset($itemData['quantity_units']) ? (int) $itemData['quantity_units'] : null;
                $lineSubtotal = $this->calculateLineSubtotal($product, $unitPrice, $quantityKg, $quantityUnits);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity_kg' => $quantityKg,
                    'quantity_units' => $quantityUnits,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $total += $lineSubtotal;
            }

            $order->update(['total_amount' => round($total, 2)]);

            return $order->fresh(['items.product', 'customer']);
        });
    }

    public function updateOrderStatus(ButcherOrder $order, string $status): void
    {
        if (! in_array($status, ButcherOrder::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('Invalid order status.')],
            ]);
        }

        if ($status === ButcherOrder::STATUS_FULFILLED) {
            throw ValidationException::withMessages([
                'status' => [__('Use the fulfill action to complete this order and create a sale.')],
            ]);
        }

        if ($order->status === ButcherOrder::STATUS_FULFILLED || $order->sale_id !== null) {
            throw ValidationException::withMessages([
                'status' => [__('This order has already been fulfilled.')],
            ]);
        }

        if ($order->status === ButcherOrder::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'status' => [__('Cancelled orders cannot change status.')],
            ]);
        }

        if ($status === ButcherOrder::STATUS_CONFIRMED) {
            $this->assertOrderCreditOnConfirm($order);
        }

        $order->update(['status' => $status]);
    }

    public function assertOrderCreditOnConfirm(ButcherOrder $order): void
    {
        $order->loadMissing('customer');
        $customer = $order->customer;
        if ($customer === null) {
            return;
        }

        $creditNeeded = round(max((float) $order->total_amount - (float) $order->deposit_paid, 0), 2);
        if ($creditNeeded <= 0) {
            return;
        }

        // Confirmation reserves against available credit (balance + this order).
        $this->assertCustomerCredit($customer, $creditNeeded);
    }

    private function calculateLineSubtotal(ButcherProduct $product, float $unitPrice, float $quantityKg, ?int $quantityUnits): float
    {
        if ($product->unit === ButcherProduct::UNIT_PER_KG) {
            return round($unitPrice * $quantityKg, 2);
        }

        return round($unitPrice * max($quantityUnits ?? 0, 0), 2);
    }

    private function generateSaleNumber(int $businessId): string
    {
        $date = now()->format('Ymd');
        $prefix = "SALE-{$date}-";
        $sequence = ButcherSale::query()
            ->where('business_id', $businessId)
            ->where('sale_number', 'like', $prefix.'%')
            ->count() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateOrderNumber(int $businessId): string
    {
        $sequence = ButcherOrder::query()->where('business_id', $businessId)->count() + 1;

        return sprintf('ORD-%d-%04d', $businessId, $sequence);
    }
}
