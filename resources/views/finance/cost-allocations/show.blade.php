<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('View cost allocation') }}</span>
    </x-slot>

    @php
        $sourceLabel = match (true) {
            $allocation->source_type === \App\Models\FinanceExpense::class => $allocation->source instanceof \App\Models\FinanceExpense
                ? $allocation->source->expense_number.' · '.$allocation->source->description
                : __('Operating expense'),
            (string) $allocation->source_type === 'template' => __('Template split'),
            filled($allocation->source_type) => class_basename($allocation->source_type).($allocation->source_id ? ' #'.$allocation->source_id : ''),
            default => '—',
        };
    @endphp

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Cost allocation') }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $allocation->batch?->batch_code ?? ('#'.$allocation->batch_id) }}</p>
                    <p class="mt-1 text-sm capitalize text-slate-500">{{ $allocation->category }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @include('finance.cost-allocations._row-actions', ['allocation' => $allocation, 'showView' => false])
                    <a href="{{ route('finance.cost-allocations.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Date') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($allocation->allocation_date)->format('d M Y') ?? '—' }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Amount') }}</p>
                <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">{{ number_format((float) $allocation->amount, 0) }} RWF</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Batch') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $allocation->batch?->batch_code ?? ('#'.$allocation->batch_id) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Category') }}</p>
                <p class="mt-1 text-sm font-semibold capitalize text-slate-900">{{ $allocation->category }}</p>
            </div>
        </section>

        <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
            <p class="text-sm font-semibold text-slate-900">{{ __('Allocation details') }}</p>
            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Source') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $sourceLabel }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Recorded by') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $allocation->creator?->name ?? '—' }}</dd>
                </div>
                @if ($allocation->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $allocation->notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>
</x-app-layout>
