<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit AP payable') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3 space-y-3">
            @include('finance.payables._tabs', ['activeTab' => $payable->payablesTabKey(), 'filters' => ['status' => '', 'q' => '']])
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Payable') }}</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $payable->payable_number }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ((float) $payable->amount_paid < (float) $payable->total_amount)
                        <form method="POST" action="{{ route('finance.payables.mark-paid', $payable) }}">
                            @csrf
                            <button class="inline-flex h-8 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100">{{ __('Mark paid') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('finance.payables.index', ['tab' => $payable->payablesTabKey()]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
        </section>

        @include('finance.partials.payment-panel', ['document' => $payable, 'documentType' => 'payable'])

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Payable details') }}</p>
            </div>
            <form method="POST" action="{{ route('finance.payables.update', $payable) }}" class="space-y-4 px-4 py-4">
                @csrf
                @method('PUT')
                @include('finance.payables._form', ['activeTab' => $payable->payablesTabKey()])
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save changes') }}</button>
                    <a href="{{ route('finance.payables.index', ['tab' => $payable->payablesTabKey()]) }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
