<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\FinancePayable;
use App\Models\FinancePayableLine;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancePayableController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinancePayable::query()
            ->with(['supplier', 'client', 'contract'])
            ->where('business_id', $businessId);

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
            'filters' => [
                'status' => (string) $request->query('status', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);

        return view('finance.payables.create', [
            'suppliers' => Supplier::query()->where('business_id', $businessId)->orderBy('first_name')->orderBy('last_name')->get(),
            'clients' => Client::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'animalIntakes' => $this->businessAnimalIntakes($businessId),
            'batches' => $this->businessBatches($businessId),
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
                'supplier_id' => $data['supplier_id'],
                'client_id' => $data['client_id'],
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
                'description' => $data['line_description'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'line_total' => $data['line_total'],
            ]);

            return $payable;
        });

        return redirect()->route('finance.payables.edit', $payable)->with('status', __('AP payable created.'));
    }

    public function edit(Request $request, FinancePayable $payable): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $payable->load('lines');

        return view('finance.payables.edit', [
            'payable' => $payable,
            'line' => $payable->lines->first(),
            'suppliers' => Supplier::query()->where('business_id', $businessId)->orderBy('first_name')->orderBy('last_name')->get(),
            'clients' => Client::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'contracts' => Contract::query()->where('business_id', $businessId)->orderByDesc('id')->get(),
            'animalIntakes' => $this->businessAnimalIntakes($businessId),
            'batches' => $this->businessBatches($businessId),
        ]);
    }

    public function update(Request $request, FinancePayable $payable): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $payable->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $payable->id);

        DB::transaction(function () use ($payable, $data): void {
            $payable->update([
                'supplier_id' => $data['supplier_id'],
                'client_id' => $data['client_id'],
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
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            } else {
                FinancePayableLine::query()->create([
                    'payable_id' => $payable->id,
                    'batch_id' => $data['batch_id'],
                    'description' => $data['line_description'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'line_total' => $data['line_total'],
                ]);
            }
        });

        return redirect()->route('finance.payables.edit', $payable)->with('status', __('AP payable updated.'));
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

        return redirect()->route('finance.payables.index')->with('status', __('Payable marked as paid.'));
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
        $unique = 'unique:finance_payables,payable_number';
        if ($payableId !== null) {
            $unique .= ','.$payableId;
        }

        $data = $request->validate([
            'counterparty_type' => ['required', 'in:supplier,client'],
            'payable_number' => ['required', 'string', 'max:40', $unique],
            'status' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'max:8'],
            'supplier_id' => ['nullable', 'integer', 'required_if:counterparty_type,supplier'],
            'client_id' => ['nullable', 'integer', 'required_if:counterparty_type,client'],
            'contract_id' => ['nullable', 'integer'],
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
        ]);

        if ($data['counterparty_type'] === 'supplier') {
            $data['client_id'] = null;
        } else {
            $data['supplier_id'] = null;
        }

        if (! empty($data['supplier_id'])) {
            $exists = Supplier::query()->whereKey($data['supplier_id'])->where('business_id', $businessId)->exists();
            abort_unless($exists, 422, __('Invalid supplier selection.'));
        }

        if (! empty($data['client_id'])) {
            $exists = Client::query()->whereKey($data['client_id'])->where('business_id', $businessId)->exists();
            abort_unless($exists, 422, __('Invalid client selection.'));
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

            if ($data['counterparty_type'] === 'supplier') {
                abort_unless($intake->source_type === AnimalIntake::SOURCE_TYPE_SUPPLIER, 422, __('Selected intake is not supplier-sourced.'));
                abort_unless((int) $intake->supplier_id === (int) ($data['supplier_id'] ?? 0), 422, __('Selected intake does not belong to the selected supplier.'));
            }
            if ($data['counterparty_type'] === 'client') {
                abort_unless($intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT, 422, __('Selected intake is not client-sourced.'));
                abort_unless((int) $intake->client_id === (int) ($data['client_id'] ?? 0), 422, __('Selected intake does not belong to the selected client.'));
            }
        }

        if (! empty($data['batch_id'])) {
            $exists = Batch::query()
                ->whereKey($data['batch_id'])
                ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
                ->exists();
            abort_unless($exists, 422, __('Invalid batch selection.'));
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

    private function businessBatches(int $businessId)
    {
        return Batch::query()
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'batch_code']);
    }
}
