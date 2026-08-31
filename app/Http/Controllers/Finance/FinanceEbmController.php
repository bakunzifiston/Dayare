<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Http\Requests\Finance\SaveFinanceEbmRequest;
use App\Models\FinanceEbmRecord;
use App\Models\FinanceInvoice;
use App\Services\Finance\FinanceEbmReconciler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinanceEbmController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public function index(Request $request, FinanceEbmReconciler $reconciler)
    {
        $businessId = $this->activeBusinessId($request);
        $state = (string) $request->query('state', '');
        $q = trim((string) $request->query('q', ''));
        $rows = $reconciler->rows($businessId, $state !== '' ? $state : null);

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(function (array $row) use ($needle): bool {
                $haystacks = [
                    (string) ($row['invoice']?->invoice_number ?? ''),
                    (string) ($row['invoice']?->client?->name ?? ''),
                    (string) ($row['ebm']?->ebm_invoice_number ?? ''),
                    (string) ($row['ebm']?->ebm_receipt_number ?? ''),
                ];

                foreach ($haystacks as $value) {
                    if ($value !== '' && str_contains(mb_strtolower($value), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $summary = $reconciler->summary($businessId);

        return view('finance.ebm.index', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => [
                'state' => $state,
                'q' => $q,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $this->activeBusinessId($request);

        return view('finance.ebm.create', [
            'record' => null,
            'selectedInvoiceId' => $request->query('finance_invoice_id'),
            'invoices' => $this->invoicesForEbm($businessId),
            'facilities' => $this->businessFacilities($businessId),
        ]);
    }

    public function store(SaveFinanceEbmRequest $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->applyLinkedInvoiceDefaults($request->validated(), $businessId);

        $record = FinanceEbmRecord::query()->create([
            'business_id' => $businessId,
            'finance_invoice_id' => $data['finance_invoice_id'],
            'facility_id' => $data['facility_id'],
            'ebm_invoice_number' => $data['ebm_invoice_number'],
            'ebm_receipt_number' => $data['ebm_receipt_number'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        $this->syncMismatchStatus($record);

        return redirect()->route('finance.ebm.index')->with('status', __('EBM record saved.'));
    }

    public function edit(Request $request, FinanceEbmRecord $ebm)
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $ebm->business_id === $businessId, 404);

        $ebm->loadMissing('invoice');

        return view('finance.ebm.edit', [
            'record' => $ebm,
            'invoices' => $this->invoicesForEbm($businessId, $ebm->finance_invoice_id),
            'facilities' => $this->businessFacilities($businessId),
        ]);
    }

    public function update(SaveFinanceEbmRequest $request, FinanceEbmRecord $ebm): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $ebm->business_id === $businessId, 404);
        $data = $this->applyLinkedInvoiceDefaults($request->validated(), $businessId);

        $ebm->update([
            'finance_invoice_id' => $data['finance_invoice_id'],
            'facility_id' => $data['facility_id'],
            'ebm_invoice_number' => $data['ebm_invoice_number'],
            'ebm_receipt_number' => $data['ebm_receipt_number'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncMismatchStatus($ebm);

        return redirect()->route('finance.ebm.index')->with('status', __('EBM record updated.'));
    }

    public function destroy(Request $request, FinanceEbmRecord $ebm): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $ebm->business_id === $businessId, 404);
        $ebm->delete();

        return redirect()->route('finance.ebm.index')->with('status', __('EBM record deleted.'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyLinkedInvoiceDefaults(array $data, int $businessId): array
    {
        if (! empty($data['finance_invoice_id'])) {
            $invoice = FinanceInvoice::query()
                ->where('business_id', $businessId)
                ->whereKey($data['finance_invoice_id'])
                ->first();

            $data['finance_invoice_id'] = $invoice !== null ? (int) $invoice->id : null;
            if ($invoice !== null && empty($data['facility_id'])) {
                $data['facility_id'] = $invoice->resolvedFacilityId();
            }
            if ($invoice !== null && ($data['amount'] === null || $data['amount'] === '')) {
                $data['amount'] = $invoice->total_amount;
            }
        } else {
            $data['finance_invoice_id'] = null;
        }

        $data['facility_id'] = ! empty($data['facility_id']) ? (int) $data['facility_id'] : null;
        $data['amount'] = isset($data['amount']) && $data['amount'] !== null && $data['amount'] !== ''
            ? round((float) $data['amount'], 2)
            : null;

        return $data;
    }

    private function invoicesForEbm(int $businessId, ?int $selectedId = null)
    {
        $query = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('issued_at')
            ->limit(200);

        return $query->get(['id', 'invoice_number', 'total_amount', 'facility_id']);
    }

    private function syncMismatchStatus(FinanceEbmRecord $record): void
    {
        $record->load('invoice');
        $state = $record->reconciliationState();
        if (in_array($state, [FinanceEbmRecord::RECON_AMOUNT_MISMATCH, FinanceEbmRecord::RECON_REFERENCE_MISMATCH], true)
            && $record->status !== FinanceEbmRecord::STATUS_CANCELLED) {
            $record->update(['status' => FinanceEbmRecord::STATUS_MISMATCH]);
        }
    }
}
