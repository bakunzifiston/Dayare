<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Daily sales') }}</span>
    </x-slot>

    @php
        $filters = $filters ?? [];
        $summary = $summary ?? ['count' => 0, 'total' => 0, 'outstanding' => 0, 'ebm_follow_up' => 0];
        $hasFilters = collect(['from', 'to', 'payment_state', 'facility_id', 'q'])
            ->contains(fn ($key) => filled($filters[$key] ?? ''));
        $unbilledCount = $unbilledDeliveries->count();
    @endphp

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Search and filters') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <form method="GET" class="flex items-center gap-2">
                    <label class="sr-only" for="sales_q">{{ __('Search') }}</label>
                    <div class="relative w-52 shrink-0">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input id="sales_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search invoice or customer') }}" class="h-9 w-full rounded-lg border-slate-200 pl-9 pr-3 text-sm">
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <label class="sr-only" for="sales_from">{{ __('From') }}</label>
                        <input id="sales_from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('From') }}">
                        <span class="text-slate-300" aria-hidden="true">–</span>
                        <label class="sr-only" for="sales_to">{{ __('To') }}</label>
                        <input id="sales_to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('To') }}">
                    </div>
                    <label class="sr-only" for="sales_payment">{{ __('Payment') }}</label>
                    <select id="sales_payment" name="payment_state" class="h-9 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Payment') }}">
                        <option value="">{{ __('All payments') }}</option>
                        @foreach (['paid' => __('Paid'), 'unpaid' => __('Unpaid'), 'pending' => __('Pending')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_state'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="sales_facility">{{ __('Site') }}</label>
                    <select id="sales_facility" name="facility_id" class="h-9 w-36 shrink-0 rounded-lg border-slate-200 px-2 text-sm" title="{{ __('Site') }}">
                        <option value="">{{ __('All sites') }}</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected(($filters['facility_id'] ?? '') == $facility->id)>{{ $facility->facility_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-9 shrink-0 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('finance.sales.index') }}" class="h-9 inline-flex shrink-0 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </form>
                <a href="{{ route('finance.invoices.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('Record sale') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Sales summary') }}">
            <x-kpi-card stat size="sm" color="slate" :title="__('Sales')" :value="$summary['count']" :subtitle="__('Invoices in this view')" glyph="clipboard" />
            <x-kpi-card stat size="sm" color="bucha-success" :title="__('Amount')" :value="'RWF '.number_format((float) $summary['total'], 0)" :subtitle="__('Invoice totals')" glyph="currency" />
            <x-kpi-card stat size="sm" color="amber" :title="__('Outstanding')" :value="'RWF '.number_format((float) $summary['outstanding'], 0)" :subtitle="__('Still unpaid')" glyph="clock" />
            <x-kpi-card
                stat
                size="sm"
                color="bucha"
                :title="__('EBM follow-up')"
                :value="$summary['ebm_follow_up']"
                :subtitle="__('Missing an EBM record')"
                glyph="alert"
                :href="$summary['ebm_follow_up'] > 0 ? route('finance.ebm.index', ['state' => 'missing_ebm']) : null"
            />
        </section>

        @if ($unbilledCount > 0)
            <section id="unbilled" class="overflow-hidden rounded-bucha border border-amber-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-100 bg-amber-50/70 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-amber-950">{{ trans_choice(':count unbilled delivery|:count unbilled deliveries', $unbilledCount, ['count' => $unbilledCount]) }}</p>
                        <p class="text-xs text-amber-800/80">{{ __('Create an AR invoice from the delivery instead of recording the sale twice.') }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">{{ __('Date') }}</th>
                                <th class="px-4 py-2.5">{{ __('Customer') }}</th>
                                <th class="px-4 py-2.5">{{ __('Site') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Qty') }}</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unbilledDeliveries as $delivery)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3 tabular-nums text-slate-600">{{ optional($delivery->received_date)->format('d M Y') ?: '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $delivery->client?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $delivery->transportTrip?->originFacility?->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ number_format((float) $delivery->received_quantity, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('finance.invoices.from-delivery', $delivery) }}">
                                            @csrf
                                            <button class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:border-bucha-primary/40 hover:text-bucha-primary">
                                                {{ __('Create invoice') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Sales invoices') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $summary['count'], ['count' => number_format($summary['count'])]) }}</p>
            </div>

            @if ($sales->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No sales in this view') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('Try clearing the filters, or record a sale invoice.') : __('Record a walk-in sale, or create an invoice from an unbilled delivery.') }}</p>
                    <a href="{{ route('finance.invoices.create') }}" class="mt-4 inline-flex h-10 items-center rounded-bucha bg-bucha-primary px-4 text-sm font-semibold text-white">{{ __('Record sale') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3">{{ __('Customer') }}</th>
                                <th class="px-4 py-3">{{ __('Item') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-3">{{ __('Payment') }}</th>
                                <th class="px-4 py-3">{{ __('EBM') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                @php
                                    $line = $sale->lines->first();
                                    $paymentState = $sale->paymentState();
                                    $lastPayment = $sale->financePayments->last();
                                    $paymentTone = match ($paymentState) {
                                        'paid' => 'bg-emerald-50 text-emerald-800',
                                        'pending' => 'bg-amber-50 text-amber-900',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">
                                        <p>{{ optional($sale->issued_at)->format('d M Y') ?? optional($sale->created_at)->format('d M Y') }}</p>
                                        <p class="text-xs text-slate-400">{{ $sale->invoice_number }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $sale->client?->name ?? $sale->animalIntake?->clientSourceDisplayName() ?? '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $sale->facility?->facility_name ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-slate-800">{{ $line?->description ?? '—' }}</p>
                                        @if ($line)
                                            <p class="text-xs tabular-nums text-slate-500">{{ number_format((float) $line->quantity, 2) }} {{ $line->quantity_unit }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $sale->total_amount, 0) }}</p>
                                        <p class="text-[11px] text-slate-400">RWF</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone }}">{{ $sale->paymentStateLabel() }}</span>
                                        @if ($lastPayment)
                                            <p class="mt-1 text-xs text-slate-500">{{ \App\Models\FinancePayment::methodLabel($lastPayment->method) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($sale->ebmRecord)
                                            <p class="text-slate-700">{{ $sale->ebmRecord->ebm_receipt_number ?: $sale->ebmRecord->ebm_invoice_number }}</p>
                                        @else
                                            <a href="{{ route('finance.ebm.create', ['finance_invoice_id' => $sale->id]) }}" class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">{{ __('Needs EBM') }}</a>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @include('finance.invoices._row-actions', ['invoice' => $sale, 'from' => 'sales'])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($sales->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">{{ $sales->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
