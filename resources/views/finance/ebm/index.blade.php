<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('EBM invoices') }}</span>
    </x-slot>

    @php
        $filters = $filters ?? [];
        $summary = $summary ?? ['matched' => 0, 'missing_ebm' => 0, 'orphan_ebm' => 0, 'follow_up' => 0];
        $hasFilters = collect(['state', 'q'])->contains(fn ($key) => filled($filters[$key] ?? ''));
        $stateLabels = [
            \App\Models\FinanceEbmRecord::RECON_MATCHED => __('Matched'),
            \App\Models\FinanceEbmRecord::RECON_MISSING_EBM => __('Missing EBM'),
            \App\Models\FinanceEbmRecord::RECON_ORPHAN_EBM => __('Orphan EBM'),
            \App\Models\FinanceEbmRecord::RECON_AMOUNT_MISMATCH => __('Amount mismatch'),
            \App\Models\FinanceEbmRecord::RECON_REFERENCE_MISMATCH => __('Reference mismatch'),
        ];
    @endphp

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Search and filters') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <form method="GET" class="flex items-center gap-2">
                    <label class="sr-only" for="ebm_q">{{ __('Search') }}</label>
                    <div class="relative w-52 shrink-0">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="ebm_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search invoice or EBM number') }}" class="h-9 w-full rounded-lg border-slate-200 pl-9 pr-3 text-sm">
                    </div>
                    <label class="sr-only" for="ebm_state">{{ __('State') }}</label>
                    <select id="ebm_state" name="state" class="h-9 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('State') }}">
                        <option value="">{{ __('All states') }}</option>
                        @foreach ($stateLabels as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['state'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-9 shrink-0 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('finance.ebm.index') }}" class="h-9 inline-flex shrink-0 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </form>
                <a href="{{ route('finance.ebm.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('Add EBM record') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('EBM summary') }}">
            <x-kpi-card stat compact color="bucha-success" :title="__('Matched')" :value="$summary['matched']" glyph="check" :href="route('finance.ebm.index', ['state' => \App\Models\FinanceEbmRecord::RECON_MATCHED])" />
            <x-kpi-card stat compact color="amber" :title="__('Missing EBM')" :value="$summary['missing_ebm']" glyph="alert" :href="route('finance.ebm.index', ['state' => \App\Models\FinanceEbmRecord::RECON_MISSING_EBM])" />
            <x-kpi-card stat compact color="slate" :title="__('Orphan EBM')" :value="$summary['orphan_ebm']" glyph="clipboard" :href="route('finance.ebm.index', ['state' => \App\Models\FinanceEbmRecord::RECON_ORPHAN_EBM])" />
            <x-kpi-card stat compact color="bucha" :title="__('Needs follow-up')" :value="$summary['follow_up']" glyph="clock" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('EBM invoices') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $rows->count(), ['count' => number_format($rows->count())]) }}</p>
            </div>

            @if ($rows->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No EBM rows in this view') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('Try clearing the filters, or add an EBM record.') : __('Add an EBM record for a sale invoice.') }}</p>
                    <a href="{{ route('finance.ebm.create') }}" class="mt-4 inline-flex h-10 items-center rounded-bucha bg-bucha-primary px-4 text-sm font-semibold text-white">{{ __('Add EBM record') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('State') }}</th>
                                <th class="px-4 py-3">{{ __('Sale / invoice') }}</th>
                                <th class="px-4 py-3">{{ __('EBM') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $state = $row['state'];
                                    $stateTone = match ($state) {
                                        \App\Models\FinanceEbmRecord::RECON_MATCHED => 'bg-emerald-50 text-emerald-800',
                                        \App\Models\FinanceEbmRecord::RECON_MISSING_EBM => 'bg-amber-50 text-amber-900',
                                        \App\Models\FinanceEbmRecord::RECON_ORPHAN_EBM => 'bg-slate-100 text-slate-700',
                                        default => 'bg-red-50 text-red-800',
                                    };
                                @endphp
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stateTone }}">{{ $stateLabels[$state] ?? str_replace('_', ' ', $state) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($row['invoice'])
                                            <p class="font-medium text-slate-900">{{ $row['invoice']->invoice_number }}</p>
                                            <p class="text-xs text-slate-500">{{ $row['invoice']->client?->name ?? '—' }}</p>
                                        @else
                                            <p class="text-slate-400">—</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($row['ebm'])
                                            <p class="text-slate-800">{{ $row['ebm']->ebm_invoice_number }}</p>
                                            <p class="text-xs text-slate-500">{{ $row['ebm']->ebm_receipt_number ?: '—' }}</p>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('No EBM yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @if ($row['invoice'])
                                            <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $row['invoice']->total_amount, 0) }}</p>
                                            <p class="text-[11px] text-slate-400">
                                                @if ($row['ebm'] && $row['ebm']->amount !== null)
                                                    {{ __('EBM') }} {{ number_format((float) $row['ebm']->amount, 0) }} RWF
                                                @else
                                                    RWF
                                                @endif
                                            </p>
                                        @elseif ($row['ebm'] && $row['ebm']->amount !== null)
                                            <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $row['ebm']->amount, 0) }}</p>
                                            <p class="text-[11px] text-slate-400">RWF</p>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @include('finance.ebm._row-actions', ['invoice' => $row['invoice'], 'ebm' => $row['ebm']])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
