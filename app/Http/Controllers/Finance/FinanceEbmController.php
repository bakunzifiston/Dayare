<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\FinanceEbmRecord;
use App\Models\FinanceInvoice;
use App\Services\Finance\FinanceEbmReconciler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceEbmController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public function index(Request $request, FinanceEbmReconciler $reconciler)
    {
        $businessId = $this->activeBusinessId($request);
        $state = (string) $request->query('state', '');
        $rows = $reconciler->rows($businessId, $state !== '' ? $state : null);
        $summary = $reconciler->summary($businessId);

        return view('finance.ebm.index', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => ['state' => $state],
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

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->validated($request, $businessId);

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

        return view('finance.ebm.edit', [
            'record' => $ebm,
            'invoices' => $this->invoicesForEbm($businessId, $ebm->finance_invoice_id),
            'facilities' => $this->businessFacilities($businessId),
        ]);
    }

    public function update(Request $request, FinanceEbmRecord $ebm): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $ebm->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $ebm->id);

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

    private function validated(Request $request, int $businessId, ?int $recordId = null): array
    {
        $unique = Rule::unique('finance_ebm_records', 'ebm_invoice_number')->where(fn ($q) => $q->where('business_id', $businessId));
        if ($recordId !== null) {
            $unique = $unique->ignore($recordId);
        }

        $invoiceUnique = Rule::unique('finance_ebm_records', 'finance_invoice_id');
        if ($recordId !== null) {
            $invoiceUnique = $invoiceUnique->ignore($recordId);
        }

        $data = $request->validate([
            'finance_invoice_id' => ['nullable', 'integer', $invoiceUnique],
            'facility_id' => ['nullable', 'integer'],
            'ebm_invoice_number' => ['required', 'string', 'max:80', $unique],
            'ebm_receipt_number' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(FinanceEbmRecord::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['finance_invoice_id'])) {
            $invoice = FinanceInvoice::query()
                ->where('business_id', $businessId)
                ->whereKey($data['finance_invoice_id'])
                ->first();
            abort_unless($invoice !== null, 422, __('Invalid invoice.'));
            $data['finance_invoice_id'] = (int) $invoice->id;
            if (empty($data['facility_id'])) {
                $data['facility_id'] = $invoice->resolvedFacilityId();
            }
            if ($data['amount'] === null || $data['amount'] === '') {
                $data['amount'] = $invoice->total_amount;
            }
        } else {
            $data['finance_invoice_id'] = null;
        }

        $data['facility_id'] = $this->assertFacility($businessId, ! empty($data['facility_id']) ? (int) $data['facility_id'] : null);
        $data['amount'] = $data['amount'] !== null && $data['amount'] !== '' ? round((float) $data['amount'], 2) : null;

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
