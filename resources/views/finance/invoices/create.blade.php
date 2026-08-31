<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New AR invoice') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Invoice details') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Number is generated on save if you leave it empty.') }}</p>
                </div>
                <a href="{{ route('finance.invoices.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
            </div>
            <form method="POST" action="{{ route('finance.invoices.store') }}" class="space-y-4 px-4 py-4">
                @csrf
                @include('finance.invoices._form')
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create invoice') }}</button>
                    <a href="{{ route('finance.invoices.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
