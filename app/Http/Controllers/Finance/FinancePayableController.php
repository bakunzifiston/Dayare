<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\CasualWorker;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Demand;
use App\Models\Employee;
use App\Models\FinancePayable;
use App\Models\FinancePayableLine;
use App\Models\FinancePayment;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\Finance\FinanceDocumentNumberGenerator;
use App\Services\Finance\FinancePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancePayableController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public const TAB_SUPPLIERS = 'suppliers';

    public const TAB_EMPLOYEES = 'employees';

    public const TAB_CASUAL = 'casual';

    /** @return list<string> */
    public static function validTabs(): array
    {
        return [self::TAB_SUPPLIERS, self::TAB_EMPLOYEES, self::TAB_CASUAL];
    }

    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $tab = (string) $request->query('tab', self::TAB_SUPPLIERS);
        if (! in_array($tab, self::validTabs(), true)) {
            $tab = self::TAB_SUPPLIERS;
        }

        $query = FinancePayable::query()
            ->with(['supplier', 'client', 'employee', 'casualWorker', 'contract', 'facility'])
            ->where('business_id', $businessId);

        $query->forPayablesTab($tab);

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
                $w->where('payable_number', 'like', $q)
                    ->orWhere('notes', 'like', $q)
                    ->orWhereHas('supplier', function ($s) use ($q): void {
                        $s->where('first_name', 'like', $q)->orWhere('last_name', 'like', $q);
                    })
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $q))
                    ->orWhereHas('employee', function ($e) use ($q): void {
                        $e->where('first_name', 'like', $q)->orWhere('last_name', 'like', $q);
                    })
                    ->orWhereHas('casualWorker', function ($c) use ($q): void {
                        $c->where('first_name', 'like', $q)->orWhere('last_name', 'like', $q);
                    });
            });
        }

        $count = (clone $query)->count();
        $total = (float) (clone $query)->sum('total_amount');
        $outstanding = (float) (clone $query)->toBase()->sum(DB::raw(
            'CASE WHEN amount_paid < total_amount THEN total_amount - amount_paid ELSE 0 END'
        ));
        $overdue = (clone $query)->where(function ($w): void {
            $w->where('status', 'overdue')
                ->orWhere(function ($inner): void {
                    $inner->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString())
                        ->whereNotIn('status', ['paid', 'cancelled'])
                        ->whereColumn('amount_paid', '<', 'total_amount');
                });
        })->count();

        $payables = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('finance.payables.index', [
            'payables' => $payables,
            'activeTab' => $tab,
            'summary' => [
                'count' => $count,
                'total' => $total,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
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
        $tab = (string) $request->query('tab', self::TAB_SUPPLIERS);
        if (! in_array($tab, self::validTabs(), true)) {
            $tab = self::TAB_SUPPLIERS;
        }

        $batches = $this->businessBatches($businessId);

        return view('finance.payables.create', [
            'activeTab' => $tab,
            'suppliers' => Supplier::query()->where('business_id', $businessId)->orderBy('first_name')->orderBy('last_name')->get(),
            'employees' => Employee::query()->where('business_id', $businessId)->orderBy('last_name')->orderBy('first_name')->get(),
            'casualWorkers' => CasualWorker::query()->where('business_id', $businessId)->orderBy('last_name')->orderBy('first_name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'animalIntakes' => $this->businessAnimalIntakes($businessId),
            'batches' => $batches,
            'batchCertificateMap' => $this->batchCertificateMapForBatches($batches),
            'batchQuantityMap' => $this->batchQuantityMapForBatches($batches),
            'certificates' => $this->businessCertificates($businessId),
            'units' => $this->payableUnits($request, $businessId),
            'facilities' => $this->businessFacilities($businessId),
            'payable' => null,
            'line' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->validated($request, $businessId, null);

        $payable = DB::transaction(function () use ($data, $businessId) {
            $payable = FinancePayable::query()->create([
                'business_id' => $businessId,
                'ap_bucket' => $data['ap_bucket'],
                'supplier_id' => $data['supplier_id'],
                'client_id' => $data['client_id'],
                'employee_id' => $data['employee_id'],
                'casual_worker_id' => $data['casual_worker_id'],
                'contract_id' => $data['contract_id'],
                'animal_intake_id' => $data['animal_intake_id'],
                'facility_id' => $data['facility_id'],
                'payable_number' => $data['payable_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            FinancePayableLine::query()->create([
                'payable_id' => $payable->id,
                'batch_id' => $data['batch_id'],
                'certificate_id' => $data['certificate_id'],
                'description' => $data['line_description'],
                'quantity' => $data['quantity'],
                'quantity_unit' => $data['quantity_unit'],
                'unit_price' => $data['unit_price'],
                'line_total' => $data['line_total'],
            ]);

            return $payable;
        });

        return redirect()->to(
            route('finance.payables.edit', $payable).'?tab='.urlencode($payable->payablesTabKey())
        )->with('status', __('AP payable created.'));
    }

    public function edit(Request $request, FinancePayable $payable): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $payable->load(['lines.certificate', 'lines.batch', 'client', 'financePayments.recordedBy', 'facility']);

        $batches = $this->businessBatches($businessId);

        return view('finance.payables.edit', [
            'payable' => $payable,
            'line' => $payable->lines->first(),
            'activeTab' => $payable->payablesTabKey(),
            'suppliers' => Supplier::query()->where('business_id', $businessId)->orderBy('first_name')->orderBy('last_name')->get(),
            'employees' => Employee::query()->where('business_id', $businessId)->orderBy('last_name')->orderBy('first_name')->get(),
            'casualWorkers' => CasualWorker::query()->where('business_id', $businessId)->orderBy('last_name')->orderBy('first_name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'animalIntakes' => $this->businessAnimalIntakes($businessId),
            'batches' => $batches,
            'batchCertificateMap' => $this->batchCertificateMapForBatches($batches),
            'batchQuantityMap' => $this->batchQuantityMapForBatches($batches),
            'certificates' => $this->businessCertificates($businessId),
            'units' => $this->payableUnits($request, $businessId),
            'facilities' => $this->businessFacilities($businessId),
        ]);
    }

    public function update(Request $request, FinancePayable $payable): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $payable->id);

        DB::transaction(function () use ($payable, $data): void {
            $hasLedger = $payable->financePayments()->exists();
            $payable->update([
                'ap_bucket' => $data['ap_bucket'],
                'supplier_id' => $data['supplier_id'],
                'client_id' => $data['client_id'],
                'employee_id' => $data['employee_id'],
                'casual_worker_id' => $data['casual_worker_id'],
                'contract_id' => $data['contract_id'],
                'animal_intake_id' => $data['animal_intake_id'],
                'facility_id' => $data['facility_id'],
                'payable_number' => $data['payable_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount']),
                'amount_paid' => $hasLedger ? $payable->amount_paid : $data['amount_paid'],
                'issued_at' => $data['issued_at'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $hasLedger ? $payable->paid_at : ($data['paid_at'] ?? null),
                'notes' => $data['notes'] ?? null,
            ]);

            $line = $payable->lines()->first();
            if ($line) {
                $line->update([
                    'batch_id' => $data['batch_id'],
                    'certificate_id' => $data['certificate_id'],
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'quantity_unit' => $data['quantity_unit'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            } else {
                FinancePayableLine::query()->create([
                    'payable_id' => $payable->id,
                    'batch_id' => $data['batch_id'],
                    'certificate_id' => $data['certificate_id'],
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'quantity_unit' => $data['quantity_unit'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            }
        });

        return redirect()->to(
            route('finance.payables.edit', $payable).'?tab='.urlencode($payable->payablesTabKey())
        )->with('status', __('AP payable updated.'));
    }

    public function show(Request $request, FinancePayable $payable): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $payable->load([
            'lines',
            'supplier',
            'client',
            'employee',
            'casualWorker',
            'facility',
            'financePayments.recordedBy',
        ]);

        return view('finance.payables.show', [
            'payable' => $payable,
            'line' => $payable->lines->first(),
        ]);
    }

    public function destroy(Request $request, FinancePayable $payable): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $tab = $payable->payablesTabKey();

        DB::transaction(function () use ($payable): void {
            $payable->financePayments()->delete();
            $payable->delete();
        });

        return redirect()->route('finance.payables.index', ['tab' => $tab])
            ->with('status', __('Payable deleted.'));
    }

    public function markPaid(Request $request, FinancePayable $payable, FinancePaymentService $payments): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);

        $data = $request->validate([
            'method' => ['nullable', Rule::in(FinancePayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        $payments->settleRemaining($payable, [
            'method' => $data['method'] ?? FinancePayment::METHOD_CASH,
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
            'notes' => __('Marked paid'),
            'facility_id' => $payable->facility_id,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.payables.index', [
            'tab' => $payable->payablesTabKey(),
        ])->with('status', __('Payable marked as paid.'));
    }

    private function validated(Request $request, int $businessId, ?int $payableId): array
    {
        if ($payableId !== null) {
            $existing = FinancePayable::query()
                ->where('business_id', $businessId)
                ->whereKey($payableId)
                ->firstOrFail();
            $request->merge(['ap_bucket' => $existing->ap_bucket]);
        }

        $unique = Rule::unique('finance_payables', 'payable_number')->where(fn ($q) => $q->where('business_id', $businessId));
        if ($payableId !== null) {
            $unique = $unique->ignore($payableId);
        }

        $request->merge([
            'payable_number' => trim((string) $request->input('payable_number', '')) ?: null,
        ]);

        $data = $request->validate([
            'ap_bucket' => ['required', Rule::in(FinancePayable::AP_BUCKETS)],
            'payable_number' => ['nullable', 'string', 'max:40', $unique],
            'status' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'max:8'],
            'link_contract' => ['required', Rule::in(['yes', 'no'])],
            'supplier_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'casual_worker_id' => ['nullable', 'integer'],
            'facility_id' => ['nullable', 'integer'],
            'contract_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $request->input('link_contract') === 'yes'),
            ],
            'animal_intake_id' => ['nullable', 'integer'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'paid_at' => ['nullable', 'date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
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

        $data['facility_id'] = $this->assertFacility($businessId, ! empty($data['facility_id']) ? (int) $data['facility_id'] : null);
        if (trim((string) ($data['payable_number'] ?? '')) === '') {
            $data['payable_number'] = FinanceDocumentNumberGenerator::next('AP', $businessId, 'finance_payables', 'payable_number');
        }

        if (in_array($data['ap_bucket'], [FinancePayable::BUCKET_SUPPLIER, FinancePayable::BUCKET_CLIENT], true)) {
            $data['employee_id'] = null;
            $data['casual_worker_id'] = null;
            if ($data['ap_bucket'] === FinancePayable::BUCKET_SUPPLIER) {
                $data['client_id'] = null;
                abort_if(empty($data['supplier_id']), 422, __('Select a supplier.'));
                $exists = Supplier::query()->whereKey($data['supplier_id'])->where('business_id', $businessId)->exists();
                abort_unless($exists, 422, __('Invalid supplier selection.'));
            } else {
                $data['supplier_id'] = null;
                abort_if(empty($data['client_id']), 422, __('Select a client.'));
                $exists = Client::query()->whereKey($data['client_id'])->where('business_id', $businessId)->exists();
                abort_unless($exists, 422, __('Invalid client selection.'));
            }
        } elseif ($data['ap_bucket'] === FinancePayable::BUCKET_EMPLOYEE) {
            $data['supplier_id'] = null;
            $data['client_id'] = null;
            $data['casual_worker_id'] = null;
            abort_if(empty($data['employee_id']), 422, __('Select an employee.'));
            $exists = Employee::query()->whereKey($data['employee_id'])->where('business_id', $businessId)->exists();
            abort_unless($exists, 422, __('Invalid employee selection.'));
        } elseif ($data['ap_bucket'] === FinancePayable::BUCKET_CASUAL_WORKER) {
            $data['supplier_id'] = null;
            $data['client_id'] = null;
            $data['employee_id'] = null;
            abort_if(empty($data['casual_worker_id']), 422, __('Select a casual worker.'));
            $exists = CasualWorker::query()->whereKey($data['casual_worker_id'])->where('business_id', $businessId)->exists();
            abort_unless($exists, 422, __('Invalid casual worker selection.'));
        }

        if (! empty($data['contract_id'])) {
            $exists = Contract::query()->whereKey($data['contract_id'])->where('business_id', $businessId)->exists();
            abort_unless($exists, 422, __('Invalid contract selection.'));
        }

        if (! empty($data['animal_intake_id'])) {
            $intake = AnimalIntake::query()
                ->whereKey($data['animal_intake_id'])
                ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
                ->first();
            abort_unless($intake !== null, 422, __('Invalid animal intake selection.'));

            if (in_array($data['ap_bucket'], [FinancePayable::BUCKET_SUPPLIER, FinancePayable::BUCKET_CLIENT], true)) {
                if ($data['ap_bucket'] === FinancePayable::BUCKET_SUPPLIER) {
                    abort_unless($intake->source_type === AnimalIntake::SOURCE_TYPE_SUPPLIER, 422, __('Selected intake is not supplier-sourced.'));
                    abort_unless((int) $intake->supplier_id === (int) ($data['supplier_id'] ?? 0), 422, __('Selected intake does not belong to the selected supplier.'));
                } else {
                    abort_unless($intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT, 422, __('Selected intake is not client-sourced.'));
                    abort_unless((int) $intake->client_id === (int) ($data['client_id'] ?? 0), 422, __('Selected intake does not belong to the selected client.'));
                }
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
        $data['amount_paid'] = (float) ($data['amount_paid'] ?? 0);
        $data['quantity'] = (float) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['line_total'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }

    private function businessAnimalIntakes(int $businessId)
    {
        return AnimalIntake::query()
            ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'source_type', 'supplier_id', 'client_id', 'species', 'number_of_animals']);
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
    private function payableUnits(Request $request, int $businessId): Collection
    {
        return $request->user()->configuredUnitsForBusinessIds([$businessId])
            ->map(fn (Unit $unit) => ['code' => $unit->code, 'name' => $unit->name])
            ->values();
    }
}
