<?php

namespace App\Models\Concerns;

use App\Models\FinancePayment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFinancePayments
{
    public function financePayments(): MorphMany
    {
        return $this->morphMany(FinancePayment::class, 'payable')->orderBy('paid_at')->orderBy('id');
    }

    public function outstandingBalance(): float
    {
        return round(max(0, (float) $this->totalAmountForPayments() - (float) $this->amount_paid), 2);
    }

    public function paymentState(): string
    {
        $total = (float) $this->totalAmountForPayments();
        $paid = (float) $this->amount_paid;

        if ($total <= 0 || $paid >= $total) {
            return FinancePayment::STATE_PAID;
        }

        if ($paid > 0) {
            return FinancePayment::STATE_PENDING;
        }

        return FinancePayment::STATE_UNPAID;
    }

    public function paymentStateLabel(): string
    {
        return match ($this->paymentState()) {
            FinancePayment::STATE_PAID => __('Paid'),
            FinancePayment::STATE_PENDING => __('Pending'),
            default => __('Unpaid'),
        };
    }

    protected function totalAmountForPayments(): float
    {
        if (isset($this->attributes['total_amount'])) {
            return (float) $this->attributes['total_amount'];
        }

        if (isset($this->attributes['amount'])) {
            return (float) $this->attributes['amount'];
        }

        return 0.0;
    }
}
