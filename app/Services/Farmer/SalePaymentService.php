<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use App\Models\SaleLog;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalePaymentService
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly SaleHistoryService $history,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function record(Sale $sale, array $data, int $userId, ?string $ip = null): SalePayment
    {
        return DB::transaction(function () use ($sale, $data, $userId, $ip): SalePayment {
            $amount = (float) $data['amount_paid'];
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount_paid' => __('Payment amount must be greater than zero.')]);
            }

            $remainingBefore = $sale->remainingBalance();
            if ($amount - $remainingBefore > 0.009) {
                throw ValidationException::withMessages(['amount_paid' => __('Payment exceeds the outstanding balance.')]);
            }

            $payment = SalePayment::query()->create([
                'sale_id' => $sale->id,
                'payment_reference' => $data['payment_reference'] ?? $this->generateReference($sale->id),
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'amount_paid' => $amount,
                'remaining_balance' => max(0, round($remainingBefore - $amount, 2)),
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'payment_status' => $amount + 0.009 >= $remainingBefore ? SalePayment::STATUS_PAID : SalePayment::STATUS_PARTIAL,
                'received_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->sales->refreshPaymentStatus($sale);
            $this->history->log($sale, SaleLog::ACTION_PAYMENT_RECORDED, $userId, $ip, __('Payment :ref recorded.', ['ref' => $payment->payment_reference]));

            return $payment;
        });
    }

    private function generateReference(int $saleId): string
    {
        do {
            $reference = 'PAY-'.$saleId.'-'.Str::upper(Str::random(5));
        } while (SalePayment::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }
}
