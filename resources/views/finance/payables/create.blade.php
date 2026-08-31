<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New AP payable') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
            @include('finance.payables._tabs', ['activeTab' => $activeTab, 'filters' => ['status' => '', 'q' => '']])
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Payable details') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Number is generated on save if you leave it empty.') }}</p>
                </div>
                <a href="{{ route('finance.payables.index', ['tab' => $activeTab]) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
            </div>
            <form method="POST" action="{{ route('finance.payables.store') }}" class="space-y-4 px-4 py-4">
                @csrf
                @include('finance.payables._form', ['activeTab' => $activeTab])
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create payable') }}</button>
                    <a href="{{ route('finance.payables.index', ['tab' => $activeTab]) }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
