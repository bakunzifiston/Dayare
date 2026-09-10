<?php

namespace App\Services\Butcher;

use App\Models\ButcherReturn;
use App\Models\ButcherSale;
use App\Models\ButcherSaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ButcherReturnService
{
    public function __construct(
        private readonly InventoryConsumptionService $consumption,
        private readonly ButcherSalesService $sales,
    ) {}

    /**
     * @param  array{sale_item_id: int, quantity_kg: float|int|string, reason?: string|null}  $data
     */
    public function processReturn(ButcherSale $sale, array $data, User $user): ButcherReturn
    {
        return DB::transaction(function () use ($sale, $data, $user) {
            /** @var ButcherSale $lockedSale */
            $lockedSale = ButcherSale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->status !== ButcherSale::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'sale' => [__('Only completed sales can accept returns.')],
                ]);
            }

            /** @var ButcherSaleItem $item */
            $item = ButcherSaleItem::query()
                ->where('sale_id', $lockedSale->id)
                ->whereKey((int) $data['sale_item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantityKg = round((float) $data['quantity_kg'], 3);
            if ($quantityKg <= 0) {
                throw ValidationException::withMessages([
                    'quantity_kg' => [__('Return quantity must be greater than zero.')],
                ]);
            }

            $returnable = $item->returnableQuantityKg();
            if ($quantityKg > $returnable + 0.0005) {
                throw ValidationException::withMessages([
                    'quantity_kg' => [__('Return quantity cannot exceed the original sale quantity.')],
                ]);
            }

            if ($item->cut_output_id === null) {
                throw ValidationException::withMessages([
                    'sale_item_id' => [__('This sale item has no cut output to restore.')],
                ]);
            }

            $return = ButcherReturn::query()->create([
                'business_id' => $lockedSale->business_id,
                'sale_item_id' => $item->id,
                'cut_output_id' => $item->cut_output_id,
                'quantity_kg' => $quantityKg,
                'reason' => $data['reason'] ?? null,
                'processed_by' => $user->id,
                'processed_at' => now(),
                'credit_reversed' => 0,
            ]);

            $this->consumption->restoreCutOutput(
                businessId: (int) $lockedSale->business_id,
                cutOutputId: (int) $item->cut_output_id,
                quantityKg: $quantityKg,
                actor: $user,
                referenceType: ButcherReturn::class,
                referenceId: (int) $return->id,
            );

            $creditReversed = 0.0;
            if ($lockedSale->payment_method === ButcherSale::PAYMENT_CREDIT && $lockedSale->customer_id) {
                $itemQty = (float) $item->quantity_kg;
                $saleCredit = round((float) $lockedSale->total_amount - (float) $lockedSale->amount_paid, 2);
                $saleTotal = (float) $lockedSale->total_amount;

                if ($saleCredit > 0 && $saleTotal > 0 && $itemQty > 0) {
                    $creditReversed = round(
                        $saleCredit
                        * ((float) $item->subtotal / $saleTotal)
                        * ($quantityKg / $itemQty),
                        2
                    );
                }

                if ($creditReversed > 0) {
                    $this->sales->reverseCustomerCredit($lockedSale->customer, $creditReversed);
                    $return->update(['credit_reversed' => $creditReversed]);
                }
            }

            return $return->fresh(['saleItem.product', 'cutOutput', 'processedByUser']);
        });
    }
}
