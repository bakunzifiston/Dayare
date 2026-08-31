@php
    use App\Models\FinancePayment;
    $document = $document ?? null;
    $documentType = $documentType ?? 'invoice';
    $outstanding = $document ? $document->outstandingBalance() : 0;
    $payments = $document?->financePayments ?? collect();
@endphp

@if ($document)
<section class="rounded-bucha border border-slate-200 bg-white px-5 py-5 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ __('Payment history') }}</p>
            <p class="text-xs text-slate-500">{{ __('Outstanding: :amount RWF', ['amount' => number_format($outstanding, 2)]) }}</p>
        </div>
        <span @class([
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
            'bg-emerald-50 text-emerald-800' => $document->paymentState() === FinancePayment::STATE_PAID,
            'bg-amber-50 text-amber-800' => $document->paymentState() === FinancePayment::STATE_PENDING,
            'bg-slate-100 text-slate-700' => $document->paymentState() === FinancePayment::STATE_UNPAID,
        ])>{{ $document->paymentStateLabel() }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">{{ __('Date') }}</th>
                    <th class="text-left px-3 py-2">{{ __('Method') }}</th>
                    <th class="text-left px-3 py-2">{{ __('Reference') }}</th>
                    <th class="text-right px-3 py-2">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2">{{ FinancePayment::methodLabel($payment->method) }}</td>
                        <td class="px-3 py-2">{{ $payment->reference ?: '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">{{ __('No payments recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($outstanding > 0 && empty($readonly))
        <form method="POST" action="{{ route('finance.payments.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 border-t border-slate-100 pt-4">
            @csrf
            <input type="hidden" name="document_type" value="{{ $documentType }}">
            <input type="hidden" name="document_id" value="{{ $document->id }}">
            <div>
                <x-input-label for="pay_amount" :value="__('Amount')" />
                <x-text-input id="pay_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $outstanding }}" class="mt-1 block w-full" :value="old('amount', $outstanding)" required />
            </div>
            <div>
                <x-input-label for="pay_method" :value="__('Method')" />
                <select id="pay_method" name="method" class="mt-1 block w-full rounded-lg border-slate-300" required>
                    @foreach (FinancePayment::METHODS as $method)
                        <option value="{{ $method }}" @selected(old('method') === $method)>{{ FinancePayment::methodLabel($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="pay_reference" :value="__('Payment reference')" />
                <x-text-input id="pay_reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="{{ __('MoMo / bank / receipt no.') }}" />
            </div>
            <div>
                <x-input-label for="pay_paid_at" :value="__('Payment date')" />
                <x-text-input id="pay_paid_at" name="paid_at" type="datetime-local" class="mt-1 block w-full" :value="old('paid_at', now()->format('Y-m-d\\TH:i'))" required />
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-lg bg-bucha-primary px-3 py-2 text-sm font-semibold text-white">{{ __('Record payment') }}</button>
            </div>
        </form>
    @endif
</section>
@endif
