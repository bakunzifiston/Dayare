<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Cost allocations') }}</span>
    </x-slot>

    @php
        $filters = $filters ?? [];
        $summary = $summary ?? ['count' => 0, 'total' => 0, 'batches' => 0, 'linked_expenses' => 0];
        $hasFilters = collect(['category', 'batch_id', 'from', 'to', 'q'])->contains(fn ($key) => filled($filters[$key] ?? ''));
        $categories = ['labor', 'logistics', 'overhead', 'utilities', 'other'];
    @endphp

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Search and filters') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <form method="GET" class="flex items-center gap-2">
                    <label class="sr-only" for="allocations_q">{{ __('Search') }}</label>
                    <div class="relative w-52 shrink-0">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="allocations_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search batch or notes') }}" class="h-9 w-full rounded-lg border-slate-200 pl-9 pr-3 text-sm">
                    </div>
                    <label class="sr-only" for="allocations_category">{{ __('Category') }}</label>
                    <select id="allocations_category" name="category" class="h-9 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Category') }}">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="allocations_batch">{{ __('Batch') }}</label>
                    <select id="allocations_batch" name="batch_id" class="h-9 w-36 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Batch') }}">
                        <option value="">{{ __('All batches') }}</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected((string) ($filters['batch_id'] ?? '') === (string) $batch->id)>
                                {{ $batch->batch_code ?? ('#'.$batch->id) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <label class="sr-only" for="allocations_from">{{ __('From') }}</label>
                        <input id="allocations_from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('From') }}">
                        <span class="text-slate-300" aria-hidden="true">–</span>
                        <label class="sr-only" for="allocations_to">{{ __('To') }}</label>
                        <input id="allocations_to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('To') }}">
                    </div>
                    <button type="submit" class="h-9 shrink-0 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('finance.cost-allocations.index') }}" class="h-9 inline-flex shrink-0 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </form>
                <a href="{{ route('finance.cost-allocations.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('New allocation') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Allocations summary') }}">
            <x-kpi-card stat compact color="slate" :title="__('Allocations')" :value="$summary['count']" glyph="clipboard" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Amount')" :value="number_format((float) $summary['total'], 0)" :subtitle="'RWF'" glyph="currency" />
            <x-kpi-card stat compact color="amber" :title="__('Batches')" :value="$summary['batches']" glyph="box" />
            <x-kpi-card stat compact color="bucha" :title="__('From expenses')" :value="$summary['linked_expenses']" glyph="alert" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Cost allocations') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $summary['count'], ['count' => number_format($summary['count'])]) }}</p>
            </div>

            @if ($allocations->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No allocations in this view') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('Try clearing the filters, or create an allocation.') : __('Allocate a cost to a batch, or use the template split.') }}</p>
                    <a href="{{ route('finance.cost-allocations.create') }}" class="mt-4 inline-flex h-10 items-center rounded-bucha bg-bucha-primary px-4 text-sm font-semibold text-white">{{ __('New allocation') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3">{{ __('Batch') }}</th>
                                <th class="px-4 py-3">{{ __('Category') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-3">{{ __('Source') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allocations as $allocation)
                                @php
                                    $sourceLabel = match (true) {
                                        $allocation->source_type === \App\Models\FinanceExpense::class => __('Expense'),
                                        (string) $allocation->source_type === 'template' => __('Template'),
                                        filled($allocation->source_type) => class_basename($allocation->source_type),
                                        default => '—',
                                    };
                                @endphp
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ optional($allocation->allocation_date)->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $allocation->batch?->batch_code ?? ('#'.$allocation->batch_id) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-700">{{ $allocation->category }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $allocation->amount, 0) }}</p>
                                        <p class="text-[11px] text-slate-400">RWF</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $sourceLabel }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @include('finance.cost-allocations._row-actions', ['allocation' => $allocation])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($allocations->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $allocations->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
