<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\FinanceExpense;
use App\Models\FinancePayment;
use App\Services\Finance\FinanceDocumentNumberGenerator;
use App\Services\Finance\FinancePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceExpenseController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public function index(Request $request)
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinanceExpense::query()
            ->with(['facility', 'supplier'])
            ->where('business_id', $businessId);

        if ($request->filled('category')) {
            $query->where('category', (string) $request->query('category'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', (string) $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', (string) $request->query('to'));
        }
        if ($request->filled('q')) {
            $q = '%'.trim((string) $request->query('q')).'%';
            $query->where(function ($w) use ($q): void {
                $w->where('expense_number', 'like', $q)
                    ->orWhere('description', 'like', $q)
                    ->orWhere('reference_number', 'like', $q)
                    ->orWhereHas('supplier', function ($s) use ($q): void {
                        $s->where('first_name', 'like', $q)->orWhere('last_name', 'like', $q);
                    });
            });
        }

        $count = (clone $query)->count();
        $total = (float) (clone $query)->sum('amount');
        $outstanding = (float) (clone $query)->toBase()->sum(DB::raw(
            'CASE WHEN amount_paid < amount THEN amount - amount_paid ELSE 0 END'
        ));
        $unpaid = (clone $query)->whereColumn('amount_paid', '<', 'amount')->count();

        $expenses = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('finance.expenses.index', [
            'expenses' => $expenses,
            'summary' => [
                'count' => $count,
                'total' => $total,
                'outstanding' => $outstanding,
                'unpaid' => $unpaid,
            ],
            'filters' => [
                'category' => (string) $request->query('category', ''),
                'status' => (string) $request->query('status', ''),
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $this->activeBusinessId($request);

        return view('finance.expenses.create', [
            'expense' => null,
            'facilities' => $this->businessFacilities($businessId),
            'suppliers' => $this->businessSuppliers($businessId),
        ]);
    }

    public function store(Request $request, FinancePaymentService $payments): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->validated($request, $businessId);
        $path = $this->storeAttachment($request, $businessId);

        $expense = DB::transaction(function () use ($data, $path, $businessId, $request, $payments) {
            $expense = FinanceExpense::query()->create([
                'business_id' => $businessId,
                'facility_id' => $data['facility_id'],
                'supplier_id' => $data['supplier_id'],
                'expense_number' => $data['expense_number'],
                'category' => $data['category'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'currency' => 'RWF',
                'expense_date' => $data['expense_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'attachment_path' => $path,
                'status' => FinanceExpense::STATUS_UNPAID,
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);

            if ($data['already_paid']) {
                $payments->record($expense, [
                    'amount' => $data['amount'],
                    'method' => $data['payment_method'],
                    'reference' => $data['payment_reference'] ?? $data['reference_number'] ?? null,
                    'paid_at' => $data['payment_paid_at'] ?? $data['expense_date'],
                    'notes' => __('Paid when recorded'),
                    'facility_id' => $data['facility_id'],
                    'recorded_by' => $request->user()->id,
                ]);
            }

            return $expense;
        });

        return redirect()->route('finance.expenses.edit', $expense)->with('status', __('Expense recorded.'));
    }

    public function edit(Request $request, FinanceExpense $expense)
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $expense->business_id === $businessId, 404);
        $expense->load(['financePayments.recordedBy', 'facility', 'supplier', 'payable']);

        return view('finance.expenses.edit', [
            'expense' => $expense,
            'facilities' => $this->businessFacilities($businessId),
            'suppliers' => $this->businessSuppliers($businessId),
        ]);
    }

    public function update(Request $request, FinanceExpense $expense): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $expense->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId, $expense->id);
        $path = $this->storeAttachment($request, $businessId) ?? $expense->attachment_path;

        $expense->update([
            'facility_id' => $data['facility_id'],
            'supplier_id' => $data['supplier_id'],
            'expense_number' => $data['expense_number'],
            'category' => $data['category'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'attachment_path' => $path,
            'notes' => $data['notes'] ?? null,
        ]);

        app(\App\Services\Finance\FinancePaymentService::class)->refreshDocument($expense);

        return redirect()->route('finance.expenses.edit', $expense)->with('status', __('Expense updated.'));
    }

    public function show(Request $request, FinanceExpense $expense): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $expense->business_id === $businessId, 404);
        $expense->load(['financePayments.recordedBy', 'facility', 'supplier']);

        return view('finance.expenses.show', [
            'expense' => $expense,
        ]);
    }

    public function destroy(Request $request, FinanceExpense $expense): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $expense->business_id === $businessId, 404);

        DB::transaction(function () use ($expense): void {
            if ($expense->attachmentExists()) {
                Storage::disk('local')->delete($expense->attachment_path);
            }
            $expense->financePayments()->delete();
            $expense->delete();
        });

        return redirect()->route('finance.expenses.index')->with('status', __('Expense deleted.'));
    }

    public function download(Request $request, FinanceExpense $expense): StreamedResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $expense->business_id === $businessId, 404);
        abort_unless($expense->attachmentExists(), 404);

        return Storage::disk('local')->download($expense->attachment_path);
    }

    private function validated(Request $request, int $businessId, ?int $expenseId = null): array
    {
        $unique = Rule::unique('finance_expenses', 'expense_number')->where(fn ($q) => $q->where('business_id', $businessId));
        if ($expenseId !== null) {
            $unique = $unique->ignore($expenseId);
        }

        $request->merge([
            'expense_number' => trim((string) $request->input('expense_number', '')) ?: null,
            'supplier_id' => $request->input('supplier_id') ?: null,
        ]);

        $alreadyPaidRules = $expenseId === null
            ? ['nullable', Rule::in(['0', '1'])]
            : ['prohibited'];

        $data = $request->validate([
            'expense_number' => ['nullable', 'string', 'max:40', $unique],
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::in(FinanceExpense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer'],
            'facility_id' => ['nullable', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
            'already_paid' => $alreadyPaidRules,
            'payment_method' => [
                'nullable',
                Rule::requiredIf(fn () => $expenseId === null && $request->input('already_paid') === '1'),
                Rule::in(FinancePayment::METHODS),
            ],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'payment_paid_at' => ['nullable', 'date'],
        ]);

        if (! empty($data['supplier_id'])) {
            $exists = $this->businessSuppliers($businessId)->contains('id', (int) $data['supplier_id']);
            abort_unless($exists, 422, __('Invalid vendor/supplier.'));
            $data['supplier_id'] = (int) $data['supplier_id'];
        } else {
            $data['supplier_id'] = null;
        }

        $data['facility_id'] = $this->assertFacility($businessId, ! empty($data['facility_id']) ? (int) $data['facility_id'] : null);
        $data['amount'] = round((float) $data['amount'], 2);
        $data['already_paid'] = $expenseId === null && ($data['already_paid'] ?? '0') === '1';
        if (empty($data['expense_number'])) {
            $data['expense_number'] = FinanceDocumentNumberGenerator::next('EX', $businessId, 'finance_expenses', 'expense_number');
        }

        return $data;
    }

    private function storeAttachment(Request $request, int $businessId): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');

        return $file->store('finance/expenses/'.$businessId, 'local');
    }
}
