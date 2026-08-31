<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit cost allocation') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $allocation->batch?->batch_code ?? ('#'.$allocation->batch_id) }}</p>
                    <p class="text-xs capitalize text-slate-500">{{ $allocation->category }} · {{ number_format((float) $allocation->amount, 0) }} RWF</p>
                </div>
                <div class="flex items-center gap-2">
                    @include('finance.cost-allocations._row-actions', ['allocation' => $allocation, 'showView' => true])
                    <a href="{{ route('finance.cost-allocations.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <form method="POST" action="{{ route('finance.cost-allocations.update', $allocation) }}" class="space-y-4 px-4 py-4">
                @csrf
                @method('PUT')
                @include('finance.cost-allocations._form')
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save changes') }}</button>
                    <a href="{{ route('finance.cost-allocations.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
