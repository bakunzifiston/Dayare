<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit AR invoice') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto px-0 sm:px-0 space-y-4">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Invoice') }}</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                    </div>
                    <form method="POST" action="{{ route('finance.invoices.mark-paid', $invoice) }}">
                        @csrf
                        <button class="rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">{{ __('Mark paid') }}</button>
                    </form>
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.invoices.update', $invoice) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.invoices._form')

                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save changes') }}</button>
                        <a href="{{ route('finance.invoices.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
