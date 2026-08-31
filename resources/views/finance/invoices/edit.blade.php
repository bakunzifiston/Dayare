<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit AR invoice') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Invoice') }}</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ((float) $invoice->amount_paid < (float) $invoice->total_amount)
                        <form method="POST" action="{{ route('finance.invoices.mark-paid', $invoice) }}">
                            @csrf
                            <button class="inline-flex h-8 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100">{{ __('Mark paid') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('finance.invoices.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
        </section>

        @include('finance.partials.payment-panel', ['document' => $invoice, 'documentType' => 'invoice'])

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Invoice details') }}</p>
            </div>
            <form method="POST" action="{{ route('finance.invoices.update', $invoice) }}" class="space-y-4 px-4 py-4">
                @csrf
                @method('PUT')
                @include('finance.invoices._form')
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save changes') }}</button>
                    <a href="{{ route('finance.invoices.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
