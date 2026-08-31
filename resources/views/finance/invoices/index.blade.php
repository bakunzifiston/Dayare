<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('AR invoices') }}</span>
    </x-slot>

    @php
        $filters = $filters ?? [];
        $summary = $summary ?? ['count' => 0, 'total' => 0, 'outstanding' => 0, 'ebm_follow_up' => 0];
        $hasFilters = collect(['status', 'payment_state', 'q'])->contains(fn ($key) => filled($filters[$key] ?? ''));
    @endphp

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Search and filters') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <form method="GET" class="flex items-center gap-2">
                    <label class="sr-only" for="invoices_q">{{ __('Search') }}</label>
                    <div class="relative w-52 shrink-0">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="invoices_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search invoice or customer') }}" class="h-9 w-full rounded-lg border-slate-200 pl-9 pr-3 text-sm">
                    </div>
                    <label class="sr-only" for="invoices_status">{{ __('Status') }}</label>
                    <select id="invoices_status" name="status" class="h-9 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Status') }}">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['draft', 'issued', 'overdue', 'paid', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="invoices_payment">{{ __('Payment') }}</label>
                    <select id="invoices_payment" name="payment_state" class="h-9 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Payment') }}">
                        <option value="">{{ __('All payments') }}</option>
                        @foreach (['paid' => __('Paid'), 'unpaid' => __('Unpaid'), 'pending' => __('Pending')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_state'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-9 shrink-0 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('finance.invoices.index') }}" class="h-9 inline-flex shrink-0 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </form>
                <a href="{{ route('finance.invoices.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('New invoice') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Invoice summary') }}">
            <x-kpi-card stat compact color="slate" :title="__('Invoices')" :value="$summary['count']" glyph="clipboard" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Amount')" :value="number_format((float) $summary['total'], 0)" :subtitle="'RWF'" glyph="currency" />
            <x-kpi-card stat compact color="amber" :title="__('Outstanding')" :value="number_format((float) $summary['outstanding'], 0)" :subtitle="'RWF'" glyph="clock" />
            <x-kpi-card
                stat
                compact
                color="bucha"
                :title="__('EBM follow-up')"
                :value="$summary['ebm_follow_up']"
                glyph="alert"
                :href="$summary['ebm_follow_up'] > 0 ? route('finance.ebm.index', ['state' => 'missing_ebm']) : null"
            />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('AR invoices') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $summary['count'], ['count' => number_format($summary['count'])]) }}</p>
            </div>

            @if ($invoices->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No invoices in this view') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('Try clearing the filters, or create an invoice.') : __('Create an invoice from a delivery or a walk-in sale.') }}</p>
                    <a href="{{ route('finance.invoices.create') }}" class="mt-4 inline-flex h-10 items-center rounded-bucha bg-bucha-primary px-4 text-sm font-semibold text-white">{{ __('New invoice') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3">{{ __('Customer') }}</th>
                                <th class="px-4 py-3">{{ __('Source') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-3">{{ __('Payment') }}</th>
                                <th class="px-4 py-3">{{ __('Due') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                @php
                                    $paymentState = $invoice->paymentState();
                                    $paymentTone = match ($paymentState) {
                                        'paid' => 'bg-emerald-50 text-emerald-800',
                                        'pending' => 'bg-amber-50 text-amber-900',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">
                                        <p>{{ optional($invoice->issued_at)->format('d M Y') ?? optional($invoice->created_at)->format('d M Y') }}</p>
                                        <p class="text-xs text-slate-400">{{ $invoice->invoice_number }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $invoice->client?->name ?? $invoice->animalIntake?->clientSourceDisplayName() ?? '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $invoice->facility?->facility_name ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 capitalize text-slate-700">{{ $invoice->source_type ?: 'manual' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $invoice->total_amount, 0) }}</p>
                                        <p class="text-[11px] text-slate-400">{{ __('Paid') }} {{ number_format((float) $invoice->amount_paid, 0) }} RWF</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone }}">{{ $invoice->paymentStateLabel() }}</span>
                                        @if ($invoice->needsEbmFollowUp())
                                            <a href="{{ route('finance.ebm.create', ['finance_invoice_id' => $invoice->id]) }}" class="mt-1 block text-xs font-semibold text-amber-800 hover:underline">{{ __('Needs EBM') }}</a>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @include('finance.invoices._row-actions', ['invoice' => $invoice, 'from' => 'invoices'])
                                            @if ((float) $invoice->amount_paid < (float) $invoice->total_amount)
                                                <form method="POST" action="{{ route('finance.invoices.mark-paid', $invoice) }}">
                                                    @csrf
                                                    <button class="inline-flex h-8 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100">{{ __('Mark paid') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($invoices->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $invoices->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
