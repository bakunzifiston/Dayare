<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherCutOutput;
use App\Models\ButcherOrder;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\ButcherSalePayment;
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
                $this->addSaleItem($sale, $itemData, $customer);
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

    public function addSaleItem(ButcherSale $sale, array $data, ?ButcherCustomer $customer = null): ButcherSaleItem
    {
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

        $subtotal = $this->calculateLineSubtotal($product, $unitPrice, $quantityKg, $quantityUnits);

        $cutOutputId = isset($data['cut_output_id']) ? (int) $data['cut_output_id'] : null;

        if ($product->unit === ButcherProduct::UNIT_PER_KG) {
            if ($quantityKg <= 0) {
                throw ValidationException::withMessages([
                    'items' => [__('Enter weight in kg for :product.', ['product' => $product->name])],
                ]);
            }
            $cutOutputId = $this->deductStock($sale->business_id, $cutOutputId, $product->cut_type_id, $quantityKg);
        } elseif ($quantityKg > 0) {
            $cutOutputId = $this->deductStock($sale->business_id, $cutOutputId, $product->cut_type_id, $quantityKg);
        }

        if ($product->unit !== ButcherProduct::UNIT_PER_KG && ($quantityUnits === null || $quantityUnits <= 0)) {
            throw ValidationException::withMessages([
                'items' => [__('Enter quantity for :product.', ['product' => $product->name])],
            ]);
        }

        return ButcherSaleItem::query()->create([
            'sale_id' => $sale->id,
            'cut_output_id' => $cutOutputId,
            'product_id' => $product->id,
            'quantity_kg' => $quantityKg,
            'quantity_units' => $quantityUnits,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);
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
                $newBalance = round((float) $customer->outstanding_balance + $creditAmount, 2);
                if ($newBalance > (float) $customer->credit_limit) {
                    throw ValidationException::withMessages([
                        'payment_method' => [__('Credit limit exceeded for this customer.')],
                    ]);
                }
                $customer->update(['outstanding_balance' => $newBalance]);
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

    public function cancelSale(ButcherSale $sale): void
    {
        if (! $sale->isCancellable()) {
            throw ValidationException::withMessages([
                'sale' => [__('This sale cannot be cancelled.')],
            ]);
        }

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            foreach ($sale->items as $item) {
                if ($item->cut_output_id && (float) $item->quantity_kg > 0) {
                    ButcherCutOutput::query()
                        ->where('id', $item->cut_output_id)
                        ->increment('remaining_weight_kg', (float) $item->quantity_kg);
                }
            }

            if ($sale->payment_method === ButcherSale::PAYMENT_CREDIT && $sale->customer_id) {
                $customer = ButcherCustomer::query()->lockForUpdate()->find($sale->customer_id);
                if ($customer) {
                    $creditAmount = round((float) $sale->total_amount - (float) $sale->amount_paid, 2);
                    if ($creditAmount > 0) {
                        $customer->update([
                            'outstanding_balance' => round(max((float) $customer->outstanding_balance - $creditAmount, 0), 2),
                        ]);
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
    public function getDailySalesSummary(Business $business, Carbon $date): array
    {
        $dateString = $date->toDateString();

        $sales = $business->butcherSales()
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->whereDate('sale_date', $dateString)
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

        $order->update(['status' => $status]);
    }

    private function deductStock(int $businessId, ?int $cutOutputId, ?int $cutTypeId, float $quantityKg): ?int
    {
        if ($quantityKg <= 0) {
            return $cutOutputId;
        }

        if ($cutOutputId) {
            $output = ButcherCutOutput::query()
                ->where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($cutOutputId);

            if ((float) $output->remaining_weight_kg < $quantityKg) {
                throw ValidationException::withMessages([
                    'items' => [__('Insufficient stock for selected cut batch.')],
                ]);
            }

            $output->update([
                'remaining_weight_kg' => round((float) $output->remaining_weight_kg - $quantityKg, 3),
            ]);

            return $output->id;
        }

        if ($cutTypeId === null) {
            return null;
        }

        $remaining = $quantityKg;
        $lastOutputId = null;

        $outputs = ButcherCutOutput::query()
            ->where('business_id', $businessId)
            ->where('cut_type_id', $cutTypeId)
            ->where('remaining_weight_kg', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($outputs as $output) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $output->remaining_weight_kg;
            $deduct = min($available, $remaining);
            $output->update([
                'remaining_weight_kg' => round($available - $deduct, 3),
            ]);
            $remaining -= $deduct;
            $lastOutputId = $output->id;
        }

        if ($remaining > 0.001) {
            throw ValidationException::withMessages([
                'items' => [__('Insufficient cut stock available.')],
            ]);
        }

        return $lastOutputId;
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
