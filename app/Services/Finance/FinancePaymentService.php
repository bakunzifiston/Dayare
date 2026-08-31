<?php

namespace App\Services\Finance;

use App\Models\FinanceExpense;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\FinancePayment;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FinancePaymentService
{
    /**
     * @param  array{
     *   amount: float|int|string,
     *   method: string,
     *   reference?: ?string,
     *   paid_at?: mixed,
     *   notes?: ?string,
     *   facility_id?: ?int,
     *   recorded_by?: ?int
     * }  $payload
     */
    public function record(Model $document, array $payload): FinancePayment
    {
        $this->assertPayable($document);

        $amount = round((float) $payload['amount'], 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $outstanding = $this->outstanding($document);
        if ($amount - $outstanding > 0.009) {
            throw new InvalidArgumentException('Payment exceeds the outstanding balance.');
        }

        $method = (string) $payload['method'];
        if (! in_array($method, FinancePayment::METHODS, true)) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        $payment = $document->financePayments()->create([
            'business_id' => (int) $document->getAttribute('business_id'),
            'facility_id' => $payload['facility_id'] ?? $document->getAttribute('facility_id'),
            'amount' => $amount,
            'method' => $method,
            'reference' => $payload['reference'] ?? null,
            'paid_at' => $payload['paid_at'] ?? now(),
            'notes' => $payload['notes'] ?? null,
            'recorded_by' => $payload['recorded_by'] ?? null,
        ]);

        $this->refreshDocument($document);

        return $payment;
    }

    public function settleRemaining(Model $document, array $payload): ?FinancePayment
    {
        $outstanding = $this->outstanding($document);
        if ($outstanding <= 0) {
            $this->refreshDocument($document);

            return null;
        }

        $payload['amount'] = $outstanding;

        return $this->record($document, $payload);
    }

    public function outstanding(Model $document): float
    {
        return round(max(0, $this->documentTotal($document) - (float) $document->getAttribute('amount_paid')), 2);
    }

    public function refreshDocument(Model $document): void
    {
        $paid = round((float) $document->financePayments()->sum('amount'), 2);
        $total = $this->documentTotal($document);
        $lastPaidAt = $document->financePayments()->max('paid_at');

        $updates = [
            'amount_paid' => $paid,
            'paid_at' => $paid > 0 ? $lastPaidAt : null,
        ];

        if ($document instanceof FinanceExpense) {
            $updates['status'] = $this->paymentState($paid, $total);
        } elseif ($paid >= $total && $total > 0) {
            $updates['status'] = 'paid';
        } elseif ($document instanceof FinanceInvoice && $document->status === 'paid' && $paid < $total) {
            $updates['status'] = 'issued';
        } elseif ($document instanceof FinancePayable && $document->status === 'paid' && $paid < $total) {
            $updates['status'] = 'open';
        }

        $document->update($updates);
        $document->refresh();
    }

    private function documentTotal(Model $document): float
    {
        if ($document instanceof FinanceExpense) {
            return (float) $document->amount;
        }

        return (float) $document->getAttribute('total_amount');
    }

    private function paymentState(float $paid, float $total): string
    {
        if ($total <= 0 || $paid >= $total) {
            return FinancePayment::STATE_PAID;
        }

        if ($paid > 0) {
            return FinancePayment::STATE_PENDING;
        }

        return FinancePayment::STATE_UNPAID;
    }

    private function assertPayable(Model $document): void
    {
        if (! ($document instanceof FinanceInvoice || $document instanceof FinancePayable || $document instanceof FinanceExpense)) {
            throw new InvalidArgumentException('Unsupported finance document.');
        }
    }
}
