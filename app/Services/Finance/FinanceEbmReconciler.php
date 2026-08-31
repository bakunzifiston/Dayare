<?php

namespace App\Services\Finance;

use App\Models\FinanceEbmRecord;
use App\Models\FinanceInvoice;
use Illuminate\Support\Collection;

class FinanceEbmReconciler
{
    /**
     * @return array{
     *   matched: int,
     *   missing_ebm: int,
     *   orphan_ebm: int,
     *   amount_mismatch: int,
     *   reference_mismatch: int,
     *   follow_up: int
     * }
     */
    public function summary(int $businessId): array
    {
        $rows = $this->rows($businessId);
        $counts = [
            FinanceEbmRecord::RECON_MATCHED => 0,
            FinanceEbmRecord::RECON_MISSING_EBM => 0,
            FinanceEbmRecord::RECON_ORPHAN_EBM => 0,
            FinanceEbmRecord::RECON_AMOUNT_MISMATCH => 0,
            FinanceEbmRecord::RECON_REFERENCE_MISMATCH => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row['state']]++;
        }

        $followUp = $counts[FinanceEbmRecord::RECON_MISSING_EBM]
            + $counts[FinanceEbmRecord::RECON_ORPHAN_EBM]
            + $counts[FinanceEbmRecord::RECON_AMOUNT_MISMATCH]
            + $counts[FinanceEbmRecord::RECON_REFERENCE_MISMATCH];

        return [
            'matched' => $counts[FinanceEbmRecord::RECON_MATCHED],
            'missing_ebm' => $counts[FinanceEbmRecord::RECON_MISSING_EBM],
            'orphan_ebm' => $counts[FinanceEbmRecord::RECON_ORPHAN_EBM],
            'amount_mismatch' => $counts[FinanceEbmRecord::RECON_AMOUNT_MISMATCH],
            'reference_mismatch' => $counts[FinanceEbmRecord::RECON_REFERENCE_MISMATCH],
            'follow_up' => $followUp,
        ];
    }

    /**
     * @return Collection<int, array{
     *   state: string,
     *   invoice: ?FinanceInvoice,
     *   ebm: ?FinanceEbmRecord
     * }>
     */
    public function rows(int $businessId, ?string $state = null): Collection
    {
        $invoices = FinanceInvoice::query()
            ->with('ebmRecord')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        $rows = collect();
        $linkedIds = [];

        foreach ($invoices as $invoice) {
            $ebm = $invoice->ebmRecord;
            if ($ebm === null) {
                $rows->push([
                    'state' => FinanceEbmRecord::RECON_MISSING_EBM,
                    'invoice' => $invoice,
                    'ebm' => null,
                ]);

                continue;
            }

            $linkedIds[] = $ebm->id;
            $rows->push([
                'state' => $ebm->reconciliationState(),
                'invoice' => $invoice,
                'ebm' => $ebm,
            ]);
        }

        $orphans = FinanceEbmRecord::query()
            ->where('business_id', $businessId)
            ->where(function ($q) use ($linkedIds): void {
                $q->whereNull('finance_invoice_id');
                if ($linkedIds !== []) {
                    $q->orWhereNotIn('id', $linkedIds);
                }
            })
            ->orderByDesc('id')
            ->get();

        foreach ($orphans as $ebm) {
            if ($ebm->finance_invoice_id && in_array($ebm->id, $linkedIds, true)) {
                continue;
            }
            $rows->push([
                'state' => $ebm->finance_invoice_id
                    ? $ebm->reconciliationState()
                    : FinanceEbmRecord::RECON_ORPHAN_EBM,
                'invoice' => $ebm->invoice,
                'ebm' => $ebm,
            ]);
        }

        $rows = $rows->unique(function (array $row) {
            return ($row['invoice']?->id ?? 'x').'-'.($row['ebm']?->id ?? 'x');
        })->values();

        if ($state) {
            $rows = $rows->filter(fn (array $row) => $row['state'] === $state)->values();
        }

        return $rows;
    }
}
