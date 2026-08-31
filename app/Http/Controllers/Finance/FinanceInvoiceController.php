<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\FinanceInvoice;
use App\Models\FinanceInvoiceLine;
use App\Models\FinancePayment;
use App\Models\Unit;
use App\Services\Finance\FinanceDocumentNumberGenerator;
use App\Services\Finance\FinancePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceInvoiceController extends Controller
{
    use ResolvesProcessorFinanceContext;
    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinanceInvoice::query()
            ->with(['client', 'contract', 'animalIntake.client', 'facility', 'ebmRecord'])
            ->where('business_id', $businessId);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('payment_state')) {
            $state = (string) $request->query('payment_state');
            if ($state === FinancePayment::STATE_PAID) {
                $query->whereColumn('amount_paid', '>=', 'total_amount')->where('total_amount', '>', 0);
            } elseif ($state === FinancePayment::STATE_PENDING) {
                $query->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'total_amount');
            } elseif ($state === FinancePayment::STATE_UNPAID) {
                $query->where(function ($w): void {
                    $w->where('amount_paid', '<=', 0)->orWhereNull('amount_paid');
                });
            }
        }
        if ($request->filled('q')) {
            $q = '%'.trim((string) $request->query('q')).'%';
            $query->where(function ($w) use ($q): void {
                $w->where('invoice_number', 'like', $q)
                    ->orWhere('notes', 'like', $q)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $q));
            });
        }

        $count = (clone $query)->count();
        $total = (float) (clone $query)->sum('total_amount');
        $outstanding = (float) (clone $query)->toBase()->sum(DB::raw(
            'CASE WHEN amount_paid < total_amount THEN total_amount - amount_paid ELSE 0 END'
        ));
        $ebmFollowUp = (clone $query)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDoesntHave('ebmRecord')
            ->count();

        $invoices = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('finance.invoices.index', [
            'invoices' => $invoices,
            'summary' => [
                'count' => $count,
                'total' => $total,
                'outstanding' => $outstanding,
                'ebm_follow_up' => $ebmFollowUp,
            ],
            'filters' => [
                'status' => (string) $request->query('status', ''),
                'payment_state' => (string) $request->query('payment_state', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $batches = $this->businessBatches($businessId);

        return view('finance.invoices.create', [
            'clientAnimalIntakes' => $this->businessClientAnimalIntakes($businessId),
            'clients' => $this->businessClients($businessId),
            'facilities' => $this->businessFacilities($businessId),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'batches' => $batches,
            'batchCertificateMap' => $this->batchCertificateMapForBatches($batches),
            'batchQuantityMap' => $this->batchQuantityMapForBatches($batches),
            'units' => $this->invoiceUnits($request, $businessId),
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
                'animal_intake_id' => $data['animal_intake_id'],
                'contract_id' => $data['contract_id'],
                'delivery_confirmation_id' => null,
                'facility_id' => $data['facility_id'],
                'invoice_number' => $data['invoice_number'],
                'source_type' => $data['source_type'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'discount_amount' => $data['discount_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount'] - $data['discount_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            FinanceInvoiceLine::query()->create([
                'invoice_id' => $invoice->id,
                'batch_id' => $data['batch_id'] ?? null,
                'certificate_id' => $data['certificate_id'] ?? null,
                'description' => $data['line_description'],
                'quantity' => $data['quantity'],
                'quantity_unit' => $data['quantity_unit'] ?? null,
                'unit_price' => $data['unit_price'],
                'line_total' => $data['line_total'],
            ]);

            return $invoice;
        });

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('AR invoice created.'));
    }

    public function show(Request $request, FinanceInvoice $invoice): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);
        $invoice->load([
            'client',
            'contract',
            'facility',
            'animalIntake.client',
            'lines',
            'financePayments.recordedBy',
            'ebmRecord',
        ]);

        return view('finance.invoices.show', [
            'invoice' => $invoice,
            'line' => $invoice->lines->first(),
            'from' => $request->query('from') === 'sales' ? 'sales' : 'invoices',
        ]);
    }

    public function edit(Request $request, FinanceInvoice $invoice): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);
        $invoice->load(['lines', 'financePayments.recordedBy', 'ebmRecord', 'facility']);
        $batches = $this->businessBatches($businessId);

        return view('finance.invoices.edit', [
            'invoice' => $invoice,
            'line' => $invoice->lines->first(),
            'clientAnimalIntakes' => $this->businessClientAnimalIntakesForForm($businessId, $invoice->animal_intake_id),
            'clients' => $this->businessClients($businessId),
            'facilities' => $this->businessFacilities($businessId),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'batches' => $batches,
            'batchCertificateMap' => $this->batchCertificateMapForBatches($batches),
            'batchQuantityMap' => $this->batchQuantityMapForBatches($batches),
            'units' => $this->invoiceUnits($request, $businessId),
            'certificates' => $this->businessCertificates($businessId),
        ]);
    }

    public function update(Request $request, FinanceInvoice $invoice): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $invoice->id);

        DB::transaction(function () use ($invoice, $data): void {
            $hasLedger = $invoice->financePayments()->exists();
            $invoice->update([
                'client_id' => $data['client_id'],
                'animal_intake_id' => $data['animal_intake_id'],
                'contract_id' => $data['contract_id'],
                'facility_id' => $data['facility_id'],
                'invoice_number' => $data['invoice_number'],
                'source_type' => $data['source_type'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'discount_amount' => $data['discount_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount'] - $data['discount_amount']),
                'amount_paid' => $hasLedger ? $invoice->amount_paid : $data['amount_paid'],
                'issued_at' => $data['issued_at'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $hasLedger ? $invoice->paid_at : ($data['paid_at'] ?? null),
                'notes' => $data['notes'] ?? null,
            ]);

            $line = $invoice->lines()->first();
            if ($line) {
                $line->update([
                    'batch_id' => $data['batch_id'] ?? null,
                    'certificate_id' => $data['certificate_id'] ?? null,
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'quantity_unit' => $data['quantity_unit'] ?? null,
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            } else {
                FinanceInvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'batch_id' => $data['batch_id'] ?? null,
                    'certificate_id' => $data['certificate_id'] ?? null,
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'quantity_unit' => $data['quantity_unit'] ?? null,
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            }
        });

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('AR invoice updated.'));
    }

    public function destroy(Request $request, FinanceInvoice $invoice): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);

        DB::transaction(function () use ($invoice): void {
            $invoice->financePayments()->delete();
            $invoice->ebmRecord()->delete();
            $invoice->delete();
        });

        $index = $request->input('from') === 'sales'
            ? 'finance.sales.index'
            : 'finance.invoices.index';

        return redirect()->route($index)->with('status', __('Invoice deleted.'));
    }

    public function markPaid(Request $request, FinanceInvoice $invoice, FinancePaymentService $payments): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $invoice->business_id === $businessId, 404);

        $data = $request->validate([
            'method' => ['nullable', Rule::in(FinancePayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        $payments->settleRemaining($invoice, [
            'method' => $data['method'] ?? FinancePayment::METHOD_CASH,
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
            'notes' => __('Marked paid'),
            'facility_id' => $invoice->resolvedFacilityId(),
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.invoices.index')->with('status', __('Invoice marked as paid.'));
    }

    public function createFromDelivery(Request $request, DeliveryConfirmation $delivery): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $delivery->load(['transportTrip.originFacility', 'transportTrip.batch.slaughterExecution.slaughterPlan', 'transportTrip.certificate']);
        $trip = $delivery->transportTrip;
        abort_unless($trip && (int) optional($trip->originFacility)->business_id === $businessId, 404);

        $existing = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->where('delivery_confirmation_id', $delivery->id)
            ->first();
        if ($existing) {
            return redirect()->route('finance.invoices.edit', $existing)->with('status', __('Invoice already exists for this delivery.'));
        }

        $batch = $trip->batch;
        $animalIntakeId = $batch?->slaughterExecution?->slaughterPlan?->animal_intake_id;
        $facilityId = $trip->origin_facility_id;

        $lineTotal = round((float) ($delivery->received_quantity ?? 0) * 3200, 2);
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $businessId,
            'client_id' => $delivery->client_id,
            'animal_intake_id' => $animalIntakeId,
            'contract_id' => $delivery->contract_id,
            'delivery_confirmation_id' => $delivery->id,
            'facility_id' => $facilityId,
            'invoice_number' => FinanceDocumentNumberGenerator::next('AR', $businessId, 'finance_invoices', 'invoice_number'),
            'source_type' => FinanceInvoice::SOURCE_DELIVERY,
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
            'quantity_unit' => $batch?->quantity_unit,
            'unit_price' => 3200,
            'line_total' => $lineTotal,
        ]);

        return redirect()->route('finance.invoices.edit', $invoice)->with('status', __('Invoice generated from delivery.'));
    }

    private function validated(Request $request, int $businessId, ?int $invoiceId): array
    {
        $unique = Rule::unique('finance_invoices', 'invoice_number')->where(fn ($q) => $q->where('business_id', $businessId));
        if ($invoiceId !== null) {
            $unique = $unique->ignore($invoiceId);
        }

        $request->merge([
            'invoice_number' => trim((string) $request->input('invoice_number', '')) ?: null,
        ]);

        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:40', $unique],
            'status' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'max:8'],
            'link_contract' => ['required', Rule::in(['yes', 'no'])],
            'animal_intake_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'facility_id' => ['nullable', 'integer'],
            'contract_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $request->input('link_contract') === 'yes'),
            ],
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
            'quantity_unit' => ['nullable', 'string', 'max:50'],
        ]);

        if (($data['link_contract'] ?? 'no') === 'no') {
            $data['contract_id'] = null;
        }
        unset($data['link_contract']);

        $intake = null;
        if (! empty($data['animal_intake_id'])) {
            $intake = AnimalIntake::query()
                ->whereKey((int) $data['animal_intake_id'])
                ->where('source_type', AnimalIntake::SOURCE_TYPE_CLIENT)
                ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
                ->first();
            abort_unless($intake !== null, 422, __('Invalid animal intake selection.'));
            $data['client_id'] = $intake->client_id;
            $data['animal_intake_id'] = (int) $intake->id;
            if (empty($data['facility_id'])) {
                $data['facility_id'] = $intake->facility_id;
            }
        } else {
            $data['animal_intake_id'] = null;
            abort_unless(! empty($data['client_id']), 422, __('Select a client or a client intake.'));
            $clientExists = DB::table('clients')->where('id', $data['client_id'])->where('business_id', $businessId)->exists();
            abort_unless($clientExists, 422, __('Invalid client selection.'));
            $data['client_id'] = (int) $data['client_id'];
        }

        $data['facility_id'] = $this->assertFacility($businessId, ! empty($data['facility_id']) ? (int) $data['facility_id'] : null);
        $data['source_type'] = $intake ? FinanceInvoice::SOURCE_INTAKE : FinanceInvoice::SOURCE_MANUAL;
        if ($invoiceId !== null) {
            $existingSource = FinanceInvoice::query()->whereKey($invoiceId)->value('source_type');
            if ($existingSource === FinanceInvoice::SOURCE_DELIVERY) {
                $data['source_type'] = FinanceInvoice::SOURCE_DELIVERY;
            }
        }

        if (trim((string) ($data['invoice_number'] ?? '')) === '') {
            $data['invoice_number'] = FinanceDocumentNumberGenerator::next('AR', $businessId, 'finance_invoices', 'invoice_number');
        }

        foreach (['contract_id' => 'contracts'] as $field => $table) {
            if (! empty($data[$field])) {
                $exists = DB::table($table)->where('id', $data[$field])->where('business_id', $businessId)->exists();
                abort_unless($exists, 422, __('Invalid selection for :field', ['field' => $field]));
            }
        }

        if (! empty($data['batch_id'])) {
            $batch = Batch::query()
                ->whereKey((int) $data['batch_id'])
                ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
                ->with('certificate')
                ->first();
            abort_unless($batch !== null, 422, __('Invalid batch selection.'));
            $data['certificate_id'] = $batch->certificate?->id;
            $batchQty = (float) ($batch->quantity ?? 0);
            $data['quantity'] = $batchQty > 0 ? round($batchQty, 4) : 1.0;
            $bu = $batch->quantity_unit;
            $data['quantity_unit'] = ($bu !== null && $bu !== '') ? (string) $bu : null;
        } elseif (! empty($data['certificate_id'])) {
            $certExists = Certificate::query()
                ->whereKey($data['certificate_id'])
                ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
                ->exists();
            abort_unless($certExists, 422, __('Invalid certificate selection.'));
            $data['certificate_id'] = (int) $data['certificate_id'];
        } else {
            $data['certificate_id'] = null;
        }

        if (empty($data['batch_id'])) {
            $allowedUnitCodes = $request->user()->configuredUnitsForBusinessIds([$businessId])->pluck('code')->all();
            $qu = trim((string) ($data['quantity_unit'] ?? ''));
            $data['quantity_unit'] = $qu === '' ? null : $qu;
            if ($data['quantity_unit'] !== null && ! in_array($data['quantity_unit'], $allowedUnitCodes, true)) {
                abort(422, __('Invalid unit.'));
            }
        }

        $data['tax_amount'] = (float) ($data['tax_amount'] ?? 0);
        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['amount_paid'] = (float) ($data['amount_paid'] ?? 0);
        $data['quantity'] = (float) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['line_total'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }

    /**
     * Client-source animal intakes for this processor business (AR invoice payer context).
     *
     * @return Collection<int, AnimalIntake>
     */
    private function businessClientAnimalIntakes(int $businessId): Collection
    {
        return AnimalIntake::query()
            ->where('source_type', AnimalIntake::SOURCE_TYPE_CLIENT)
            ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
            ->with(['client:id,name,business_id'])
            ->orderByDesc('intake_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    /**
     * Same as {@see businessClientAnimalIntakes} but ensures the selected intake appears (e.g. outside the 200 limit).
     *
     * @return Collection<int, AnimalIntake>
     */
    private function businessClientAnimalIntakesForForm(int $businessId, ?int $selectedIntakeId): Collection
    {
        $collection = $this->businessClientAnimalIntakes($businessId);
        if ($selectedIntakeId === null) {
            return $collection;
        }
        if ($collection->contains('id', $selectedIntakeId)) {
            return $collection;
        }

        $current = AnimalIntake::query()
            ->whereKey($selectedIntakeId)
            ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
            ->with(['client:id,name,business_id'])
            ->first();

        return $current !== null ? $collection->prepend($current)->values() : $collection;
    }

    /**
     * @return Collection<int, Batch>
     */
    private function businessBatches(int $businessId): Collection
    {
        return Batch::query()
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->with('certificate')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'batch_code', 'quantity', 'quantity_unit']);
    }

    /**
     * @param  Collection<int, Batch>  $batches
     * @return array<string, array{quantity: float, quantity_unit: string, quantity_unit_label: string}>
     */
    private function batchQuantityMapForBatches(Collection $batches): array
    {
        $codes = $batches->pluck('quantity_unit')->filter()->unique()->values()->all();
        $unitNames = $codes !== []
            ? Unit::query()->whereIn('code', $codes)->pluck('name', 'code')->all()
            : [];

        $out = [];
        foreach ($batches as $batch) {
            $code = (string) ($batch->quantity_unit ?? '');
            $label = $code === ''
                ? ''
                : (string) ($unitNames[$code] ?? Demand::QUANTITY_UNITS[$code] ?? $code);
            $qty = (float) ($batch->quantity ?? 0);
            $out[(string) $batch->id] = [
                'quantity' => $qty > 0 ? round($qty, 4) : 1.0,
                'quantity_unit' => $code,
                'quantity_unit_label' => $label,
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, Batch>  $batches
     * @return array<string, array{certificate_id: int, certificate_number: string}|null>
     */
    private function batchCertificateMapForBatches(Collection $batches): array
    {
        $out = [];
        foreach ($batches as $batch) {
            $certificate = $batch->certificate;
            $out[(string) $batch->id] = $certificate !== null
                ? [
                    'certificate_id' => (int) $certificate->id,
                    'certificate_number' => (string) ($certificate->certificate_number ?? ''),
                ]
                : null;
        }

        return $out;
    }

    private function businessCertificates(int $businessId)
    {
        return Certificate::query()
            ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'certificate_number']);
    }

    /**
     * @return Collection<int, array{code: string, name: string}>
     */
    private function invoiceUnits(Request $request, int $businessId): Collection
    {
        return $request->user()->configuredUnitsForBusinessIds([$businessId])
            ->map(fn (Unit $unit) => ['code' => $unit->code, 'name' => $unit->name])
            ->values();
    }
}
