<?php

namespace App\Services\Butcher;

use App\Models\ButcherOrder;
use App\Models\ButcherOutlet;
use App\Models\ButcherSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherOrderFulfillmentService
{
    public function __construct(
        private readonly ButcherSalesService $sales,
    ) {}

    /**
     * Convert a ready order into exactly one sale (no partial fulfillment in this pass).
     *
     * @param  array{
     *     outlet_id: int,
     *     payment_method: string,
     *     amount_paid?: float|int|string,
     *     split_payments?: list<array{payment_method: string, amount: float|int|string}>
     * }  $data
     */
    public function fulfill(ButcherOrder $order, array $data, User $user): ButcherSale
    {
        return DB::transaction(function () use ($order, $data, $user) {
            /** @var ButcherOrder $locked */
            $locked = ButcherOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ButcherOrder::STATUS_FULFILLED || $locked->sale_id !== null) {
                throw ValidationException::withMessages([
                    'order' => [__('This order has already been fulfilled.')],
                ]);
            }

            if ($locked->status !== ButcherOrder::STATUS_READY) {
                throw ValidationException::withMessages([
                    'order' => [__('Only ready orders can be fulfilled.')],
                ]);
            }

            $locked->load(['items.product', 'customer', 'business']);

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => [__('Order has no items to fulfill.')],
                ]);
            }

            $outletId = (int) $data['outlet_id'];
            ButcherOutlet::query()
                ->where('business_id', $locked->business_id)
                ->whereKey($outletId)
                ->firstOrFail();

            $deposit = (float) $locked->deposit_paid;
            $paymentMethod = (string) $data['payment_method'];
            $totalHint = (float) $locked->total_amount;
            $amountPaid = array_key_exists('amount_paid', $data)
                ? (float) $data['amount_paid']
                : ($paymentMethod === ButcherSale::PAYMENT_CREDIT ? $deposit : $totalHint);

            $saleItems = $locked->items->map(function ($item) {
                $row = [
                    'product_id' => $item->product_id,
                    'unit_price' => (float) $item->unit_price,
                    'quantity_kg' => (float) $item->quantity_kg,
                ];
                if ($item->quantity_units !== null) {
                    $row['quantity_units'] = (int) $item->quantity_units;
                }

                return $row;
            })->all();

            $sale = $this->sales->createSale($locked->business, [
                'outlet_id' => $outletId,
                'customer_id' => $locked->customer_id,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'split_payments' => $data['split_payments'] ?? [],
                'discount_amount' => 0,
                'safety_override_reason' => $data['safety_override_reason'] ?? null,
                'items' => $saleItems,
            ], $user);

            $locked->update([
                'sale_id' => $sale->id,
                'outlet_id' => $outletId,
                'status' => ButcherOrder::STATUS_FULFILLED,
            ]);

            return $sale->fresh(['items.product', 'items.cutOutput', 'customer', 'outlet', 'payments']);
        });
    }
}
