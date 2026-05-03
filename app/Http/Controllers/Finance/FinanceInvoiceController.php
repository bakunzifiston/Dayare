<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\FinanceInvoice;
use App\Models\FinanceInvoiceLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinanceInvoice::query()
            ->with(['client', 'contract'])
            ->where('business_id', $businessId);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('q')) {
            $q = '%'.trim((string) $request->query('q')).'%';
            $query->where(function ($w) use ($q): void {
                $w->where('invoice_number', 'like', $q)
                    ->orWhere('notes', 'like', $q);
            });
        }

        $invoices = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('finance.invoices.index', [
            'invoices' => $invoices,
            'filters' => [
                'status' => (string) $request->query('status', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);

        return view('finance.invoices.create', [
            'clients' => Client::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'deliveries' => $this->businessDeliveries($businessId),
            'batches' => $this->businessBatches($businessId),
            'certificates' => $this->businessCertificates($businessId),
            'invoice' => null,
            'line' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->validated($request, $businessId, null);

        $invoice = DB::transaction(function () use ($data, $businessId) {
            $invoice = FinanceInvoice::query()->create([
                'business_id' => $businessId,
                'client_id' => $data['client_id'],
                'contract_id' => $data['contract_id'],
                'delivery_confirmation_id' => $data['delivery_confirmation_id'],
                'invoice_number' => $data['invoice_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'discount_amount' => $data['discount_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount'] - $data['discount_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'],
                'due_date' => $data['due_date'],
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'],
            ]);

            FinanceInvoiceLine::query()->create([
                'invoice_id' => $invoice->id,
                'batch_id' => $data['batch_id'],
                'certificate_id' => $data['certificate_id'],
                'description' => $data['line_description'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'line_total' => $data['line_total'],
            ]);

            return $invoice;
        });

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('AR invoice created.'));
    }

    public function edit(Request $request, FinanceInvoice $invoice): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);
        $invoice->load('lines');

        return view('finance.invoices.edit', [
            'invoice' => $invoice,
            'line' => $invoice->lines->first(),
            'clients' => Client::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'deliveries' => $this->businessDeliveries($businessId),
            'batches' => $this->businessBatches($businessId),
            'certificates' => $this->businessCertificates($businessId),
        ]);
    }

    public function update(Request $request, FinanceInvoice $invoice): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $invoice->id);

        DB::transaction(function () use ($invoice, $data): void {
            $invoice->update([
                'client_id' => $data['client_id'],
                'contract_id' => $data['contract_id'],
                'delivery_confirmation_id' => $data['delivery_confirmation_id'],
                'invoice_number' => $data['invoice_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'discount_amount' => $data['discount_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount'] - $data['discount_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'],
                'due_date' => $data['due_date'],
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'],
            ]);

            $line = $invoice->lines()->first();
            if ($line) {
                $line->update([
                    'batch_id' => $data['batch_id'],
                    'certificate_id' => $data['certificate_id'],
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            } else {
                FinanceInvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'batch_id' => $data['batch_id'],
                    'certificate_id' => $data['certificate_id'],
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            }
        });

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('AR invoice updated.'));
    }

    public function markPaid(Request $request, FinanceInvoice $invoice): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);

        $invoice->update([
            'amount_paid' => $invoice->total_amount,
            'paid_at' => now(),
            'status' => 'paid',
        ]);

        return redirect()->route('finance.invoices.index')->with('status', __('Invoice marked as paid.'));
    }

    public function createFromDelivery(Request $request, DeliveryConfirmation $delivery): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $delivery->load(['transportTrip.batch', 'transportTrip.certificate']);
        $trip = $delivery->transportTrip;
        abort_unless($trip && (int) optional($trip->originFacility)->business_id === $businessId, 404);

        $lineTotal = round((float) ($delivery->received_quantity ?? 0) * 3200, 2);
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $businessId,
            'client_id' => $delivery->client_id,
            'contract_id' => $delivery->contract_id,
            'delivery_confirmation_id' => $delivery->id,
            'invoice_number' => 'AR-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'issued',
            'currency' => 'RWF',
            'subtotal' => $lineTotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $lineTotal,
            'amount_paid' => 0,
            'issued_at' => now(),
            'due_date' => now()->addDays(14),
            'notes' => 'Auto-created from delivery #'.$delivery->id,
        ]);

        FinanceInvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'batch_id' => optional($trip)->batch_id,
            'certificate_id' => optional($trip)->certificate_id,
            'description' => 'Delivery '.$delivery->id.' invoice line',
            'quantity' => (float) ($delivery->received_quantity ?? 1),
            'unit_price' => 3200,
            'line_total' => $lineTotal,
        ]);

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('Invoice generated from delivery.'));
    }

    private function activeBusinessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
    }

    private function validated(Request $request, int $businessId, ?int $invoiceId): array
    {
        $unique = 'unique:finance_invoices,invoice_number';
        if ($invoiceId !== null) {
            $unique .= ','.$invoiceId;
        }

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:40', $unique],
            'status' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'max:8'],
            'client_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'integer'],
            'delivery_confirmation_id' => ['nullable', 'integer'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'paid_at' => ['nullable', 'date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'line_description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'batch_id' => ['nullable', 'integer'],
            'certificate_id' => ['nullable', 'integer'],
        ]);

        foreach (['client_id' => 'clients', 'contract_id' => 'contracts'] as $field => $table) {
            if (! empty($data[$field])) {
                $exists = DB::table($table)->where('id', $data[$field])->where('business_id', $businessId)->exists();
                abort_unless($exists, 422, __('Invalid selection for :field', ['field' => $field]));
            }
        }

        if (! empty($data['delivery_confirmation_id'])) {
            $deliveryExists = DeliveryConfirmation::query()
                ->whereKey($data['delivery_confirmation_id'])
                ->whereHas('transportTrip.originFacility', fn ($q) => $q->where('business_id', $businessId))
                ->exists();
            abort_unless($deliveryExists, 422, __('Invalid delivery confirmation selection.'));
        }

        if (! empty($data['batch_id'])) {
            $batchExists = Batch::query()
                ->whereKey($data['batch_id'])
                ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
                ->exists();
            abort_unless($batchExists, 422, __('Invalid batch selection.'));
        }

        if (! empty($data['certificate_id'])) {
            $certExists = Certificate::query()
                ->whereKey($data['certificate_id'])
                ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
                ->exists();
            abort_unless($certExists, 422, __('Invalid certificate selection.'));
        }

        $data['tax_amount'] = (float) ($data['tax_amount'] ?? 0);
        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['amount_paid'] = (float) ($data['amount_paid'] ?? 0);
        $data['quantity'] = (float) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['line_total'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }

    private function businessDeliveries(int $businessId)
    {
        return DeliveryConfirmation::query()
            ->whereHas('transportTrip.originFacility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    private function businessBatches(int $businessId)
    {
        return Batch::query()
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'batch_code']);
    }

    private function businessCertificates(int $businessId)
    {
        return Certificate::query()
            ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'certificate_number']);
    }
}
