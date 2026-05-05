<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
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
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancePayableController extends Controller
{
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
            ->with(['supplier', 'client', 'employee', 'casualWorker', 'contract'])
            ->where('business_id', $businessId);

        match ($tab) {
            self::TAB_EMPLOYEES => $query->where('ap_bucket', FinancePayable::BUCKET_EMPLOYEE),
            self::TAB_CASUAL => $query->where('ap_bucket', FinancePayable::BUCKET_CASUAL_WORKER),
            default => $query->whereIn('ap_bucket', [FinancePayable::BUCKET_SUPPLIER, FinancePayable::BUCKET_CLIENT]),
        };

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('q')) {
            $q = '%'.trim((string) $request->query('q')).'%';
            $query->where(function ($w) use ($q): void {
                $w->where('payable_number', 'like', $q)
                    ->orWhere('notes', 'like', $q);
            });
        }

        $payables = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('finance.payables.index', [
            'payables' => $payables,
            'activeTab' => $tab,
            'filters' => [
                'status' => (string) $request->query('status', ''),
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
                'payable_number' => $data['payable_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'],
                'due_date' => $data['due_date'],
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'],
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
        $payable->load(['lines.certificate', 'lines.batch', 'client']);

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
        ]);
    }

    public function update(Request $request, FinancePayable $payable): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $payable->id);

        DB::transaction(function () use ($payable, $data): void {
            $payable->update([
                'ap_bucket' => $data['ap_bucket'],
                'supplier_id' => $data['supplier_id'],
                'client_id' => $data['client_id'],
                'employee_id' => $data['employee_id'],
                'casual_worker_id' => $data['casual_worker_id'],
                'contract_id' => $data['contract_id'],
                'animal_intake_id' => $data['animal_intake_id'],
                'payable_number' => $data['payable_number'],
                'status' => $data['status'],
                'currency' => $data['currency'],
                'subtotal' => $data['line_total'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => max(0, $data['line_total'] + $data['tax_amount']),
                'amount_paid' => $data['amount_paid'],
                'issued_at' => $data['issued_at'],
                'due_date' => $data['due_date'],
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'],
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

    public function markPaid(Request $request, FinancePayable $payable): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);

        $payable->update([
            'amount_paid' => $payable->total_amount,
            'paid_at' => now(),
            'status' => 'paid',
        ]);

        return redirect()->route('finance.payables.index', [
            'tab' => $payable->payablesTabKey(),
        ])->with('status', __('Payable marked as paid.'));
    }

    private function activeBusinessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
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

        $unique = 'unique:finance_payables,payable_number';
        if ($payableId !== null) {
            $unique .= ','.$payableId;
        }

        $data = $request->validate([
            'ap_bucket' => ['required', Rule::in(FinancePayable::AP_BUCKETS)],
            'payable_number' => ['required', 'string', 'max:40', $unique],
            'status' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'max:8'],
            'link_contract' => ['required', Rule::in(['yes', 'no'])],
            'supplier_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'casual_worker_id' => ['nullable', 'integer'],
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
                ->with('certificate:id,batch_id,certificate_number')
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
            ->with(['certificate:id,batch_id,certificate_number'])
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
