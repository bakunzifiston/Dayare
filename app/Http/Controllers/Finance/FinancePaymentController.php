<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\ResolvesProcessorFinanceContext;
use App\Models\BusinessUser;
use App\Models\FinanceExpense;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\FinancePayment;
use App\Services\Finance\FinancePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class FinancePaymentController extends Controller
{
    use ResolvesProcessorFinanceContext;

    public function store(Request $request, FinancePaymentService $payments): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $request->validate([
            'document_type' => ['required', Rule::in(['invoice', 'payable', 'expense'])],
            'document_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(FinancePayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:80'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'facility_id' => ['nullable', 'integer'],
        ]);

        $document = $this->resolveDocument($businessId, $data['document_type'], (int) $data['document_id']);
        $permission = $document instanceof FinanceInvoice
            ? BusinessUser::PERMISSION_MANAGE_AR_INVOICES
            : BusinessUser::PERMISSION_MANAGE_AP_PAYABLES;
        abort_unless($request->user()->canProcessorPermission($permission, $businessId), 403);

        try {
            $payments->record($document, [
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
                'facility_id' => $this->assertFacility($businessId, isset($data['facility_id']) ? (int) $data['facility_id'] : null),
                'recorded_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', __('Payment recorded.'));
    }

    private function resolveDocument(int $businessId, string $type, int $id): FinanceInvoice|FinancePayable|FinanceExpense
    {
        $document = match ($type) {
            'invoice' => FinanceInvoice::query()->where('business_id', $businessId)->whereKey($id)->first(),
            'payable' => FinancePayable::query()->where('business_id', $businessId)->whereKey($id)->first(),
            default => FinanceExpense::query()->where('business_id', $businessId)->whereKey($id)->first(),
        };

        abort_unless($document !== null, 404);

        return $document;
    }
}
