<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherReturn;
use App\Models\ButcherSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ButcherReceivablesService
{
    public const BUCKET_0_30 = '0_30';

    public const BUCKET_31_60 = '31_60';

    public const BUCKET_60_PLUS = '60_plus';

    /**
     * Per-customer aging summary for a business.
     *
     * Aging is anchored on sale_date (no payment-terms field exists).
     *
     * @return array{
     *     as_of: string,
     *     totals: array{outstanding: float, bucket_0_30: float, bucket_31_60: float, bucket_60_plus: float},
     *     customers: list<array<string, mixed>>
     * }
     */
    public function agingReport(Business $business, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $customers = $business->butcherCustomers()
            ->orderBy('name')
            ->get();

        $rows = [];
        $totals = [
            'outstanding' => 0.0,
            'bucket_0_30' => 0.0,
            'bucket_31_60' => 0.0,
            'bucket_60_plus' => 0.0,
        ];

        foreach ($customers as $customer) {
            $row = $this->customerAging($customer, $asOf);
            if ($row['outstanding'] <= 0.0005 && (float) $customer->outstanding_balance <= 0.0005) {
                continue;
            }

            $rows[] = $row;
            $totals['outstanding'] = round($totals['outstanding'] + $row['outstanding'], 2);
            $totals['bucket_0_30'] = round($totals['bucket_0_30'] + $row['bucket_0_30'], 2);
            $totals['bucket_31_60'] = round($totals['bucket_31_60'] + $row['bucket_31_60'], 2);
            $totals['bucket_60_plus'] = round($totals['bucket_60_plus'] + $row['bucket_60_plus'], 2);
        }

        usort($rows, fn (array $a, array $b) => $b['outstanding'] <=> $a['outstanding']);

        return [
            'as_of' => $asOf->toDateString(),
            'totals' => $totals,
            'customers' => $rows,
        ];
    }

    /**
     * Chronological statement for one customer. Running balance should match outstanding_balance.
     *
     * @return array{
     *     customer: ButcherCustomer,
     *     as_of: string,
     *     outstanding_balance: float,
     *     computed_balance: float,
     *     lines: list<array<string, mixed>>
     * }
     */
    public function statement(ButcherCustomer $customer, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $customer->loadMissing('business');

        $events = collect();

        $sales = ButcherSale::query()
            ->where('business_id', $customer->business_id)
            ->where('customer_id', $customer->id)
            ->where('payment_method', ButcherSale::PAYMENT_CREDIT)
            ->whereIn('status', [ButcherSale::STATUS_COMPLETED, ButcherSale::STATUS_CANCELLED])
            ->with(['items.returns'])
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get();

        foreach ($sales as $sale) {
            $saleDate = $sale->sale_date?->toDateString() ?? $sale->created_at?->toDateString();
            $total = (float) $sale->total_amount;
            $paid = (float) $sale->amount_paid;

            $events->push([
                'occurred_on' => $saleDate,
                'sort' => ($saleDate ?? '').'-'.$sale->id.'-1',
                'type' => 'sale',
                'reference' => $sale->sale_number,
                'sale_id' => $sale->id,
                'description' => __('Credit sale :number', ['number' => $sale->sale_number]),
                'debit' => $total,
                'credit' => 0.0,
            ]);

            if ($paid > 0) {
                $events->push([
                    'occurred_on' => $saleDate,
                    'sort' => ($saleDate ?? '').'-'.$sale->id.'-2',
                    'type' => 'payment',
                    'reference' => $sale->sale_number,
                    'sale_id' => $sale->id,
                    'description' => __('Payment on :number', ['number' => $sale->sale_number]),
                    'debit' => 0.0,
                    'credit' => $paid,
                ]);
            }

            if ($sale->status === ButcherSale::STATUS_CANCELLED) {
                $creditOnSale = round(max($total - $paid, 0), 2);
                if ($creditOnSale > 0) {
                    $cancelDate = $sale->updated_at?->toDateString() ?? $saleDate;
                    $events->push([
                        'occurred_on' => $cancelDate,
                        'sort' => ($cancelDate ?? '').'-'.$sale->id.'-9',
                        'type' => 'cancellation',
                        'reference' => $sale->sale_number,
                        'sale_id' => $sale->id,
                        'description' => __('Cancellation of :number', ['number' => $sale->sale_number]),
                        'debit' => 0.0,
                        'credit' => $creditOnSale,
                    ]);
                }

                continue;
            }

            foreach ($sale->items as $item) {
                foreach ($item->returns as $return) {
                    $reversed = (float) $return->credit_reversed;
                    if ($reversed <= 0) {
                        continue;
                    }

                    $returnDate = $return->processed_at?->toDateString()
                        ?? $return->created_at?->toDateString()
                        ?? $saleDate;

                    $events->push([
                        'occurred_on' => $returnDate,
                        'sort' => ($returnDate ?? '').'-'.$return->id.'-5',
                        'type' => 'return',
                        'reference' => $sale->sale_number,
                        'sale_id' => $sale->id,
                        'return_id' => $return->id,
                        'description' => __('Return on :number (:reason)', [
                            'number' => $sale->sale_number,
                            'reason' => $return->reason ?: __('no reason'),
                        ]),
                        'debit' => 0.0,
                        'credit' => $reversed,
                    ]);
                }
            }
        }

        $sorted = $events->sortBy('sort')->values();
        $running = 0.0;
        $lines = [];

        foreach ($sorted as $event) {
            $running = round($running + (float) $event['debit'] - (float) $event['credit'], 2);
            $lines[] = array_merge($event, [
                'balance' => $running,
            ]);
        }

        return [
            'customer' => $customer,
            'as_of' => $asOf->toDateString(),
            'outstanding_balance' => round((float) $customer->outstanding_balance, 2),
            'computed_balance' => $running,
            'lines' => $lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerAging(ButcherCustomer $customer, Carbon $asOf): array
    {
        $openInvoices = $this->openCreditInvoices($customer);
        $bucket0 = 0.0;
        $bucket31 = 0.0;
        $bucket60 = 0.0;
        $oldestDate = null;
        $oldestAge = null;

        foreach ($openInvoices as $invoice) {
            $remaining = (float) $invoice['remaining'];
            if ($remaining <= 0) {
                continue;
            }

            $ageDays = (int) $invoice['age_days'];
            $bucket = $this->bucketForAge($ageDays);

            if ($bucket === self::BUCKET_0_30) {
                $bucket0 = round($bucket0 + $remaining, 2);
            } elseif ($bucket === self::BUCKET_31_60) {
                $bucket31 = round($bucket31 + $remaining, 2);
            } else {
                $bucket60 = round($bucket60 + $remaining, 2);
            }

            if ($oldestAge === null || $ageDays > $oldestAge) {
                $oldestAge = $ageDays;
                $oldestDate = $invoice['sale_date'];
            }
        }

        $computedOutstanding = round($bucket0 + $bucket31 + $bucket60, 2);
        // Prefer stored balance for display; aging buckets still from invoice remainders.
        $outstanding = round((float) $customer->outstanding_balance, 2);
        if ($outstanding <= 0 && $computedOutstanding > 0) {
            $outstanding = $computedOutstanding;
        }

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'credit_limit' => round((float) $customer->credit_limit, 2),
            'outstanding' => $outstanding,
            'computed_outstanding' => $computedOutstanding,
            'bucket_0_30' => $bucket0,
            'bucket_31_60' => $bucket31,
            'bucket_60_plus' => $bucket60,
            'aging_bucket' => $this->primaryBucket($bucket0, $bucket31, $bucket60),
            'oldest_unpaid_sale_date' => $oldestDate,
            'oldest_age_days' => $oldestAge,
        ];
    }

    /**
     * @return Collection<int, array{sale_id: int, sale_number: string, sale_date: string, age_days: int, charged: float, reversed: float, remaining: float}>
     */
    private function openCreditInvoices(ButcherCustomer $customer): Collection
    {
        $asOf = now()->startOfDay();

        $sales = ButcherSale::query()
            ->where('business_id', $customer->business_id)
            ->where('customer_id', $customer->id)
            ->where('payment_method', ButcherSale::PAYMENT_CREDIT)
            ->where('status', ButcherSale::STATUS_COMPLETED)
            ->with(['items.returns'])
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get();

        return $sales->map(function (ButcherSale $sale) use ($asOf) {
            $charged = round(max((float) $sale->total_amount - (float) $sale->amount_paid, 0), 2);
            $reversed = round((float) $sale->items->sum(
                fn ($item) => (float) $item->returns->sum(fn (ButcherReturn $r) => (float) $r->credit_reversed)
            ), 2);
            $remaining = round(max($charged - $reversed, 0), 2);
            $saleDate = $sale->sale_date?->copy()->startOfDay() ?? $sale->created_at?->copy()->startOfDay() ?? $asOf;
            $ageDays = (int) $saleDate->diffInDays($asOf);

            return [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'sale_date' => $saleDate->toDateString(),
                'age_days' => $ageDays,
                'charged' => $charged,
                'reversed' => $reversed,
                'remaining' => $remaining,
            ];
        })->filter(fn (array $row) => $row['remaining'] > 0.0005)->values();
    }

    public function bucketForAge(int $ageDays): string
    {
        if ($ageDays <= 30) {
            return self::BUCKET_0_30;
        }

        if ($ageDays <= 60) {
            return self::BUCKET_31_60;
        }

        return self::BUCKET_60_PLUS;
    }

    private function primaryBucket(float $b0, float $b31, float $b60): string
    {
        if ($b60 > 0) {
            return self::BUCKET_60_PLUS;
        }
        if ($b31 > 0) {
            return self::BUCKET_31_60;
        }
        if ($b0 > 0) {
            return self::BUCKET_0_30;
        }

        return self::BUCKET_0_30;
    }
}
