@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Finance & reporting') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Revenue, COGS, expenses, and net margin from all modules.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.finance.expenses.create') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Log expense') }}</a>
                <a href="{{ route('butcher.finance.reports.pl') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('P&L report') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input id="from" type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
                <div>
                    <label for="to" class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input id="to" type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
                <x-kpi-card stat :title="__('Revenue')" :value="$fmtMoney($summary['revenue'])" :href="route('butcher.finance.reports.sales', ['from' => $from, 'to' => $to])" />
                <x-kpi-card stat :title="__('COGS')" :value="$fmtMoney($summary['cogs'])" />
                <x-kpi-card stat :title="__('Gross profit')" :value="$fmtMoney($summary['gross_profit'])" :href="route('butcher.finance.reports.pl', ['from' => $from, 'to' => $to])" />
                <x-kpi-card stat :title="__('Expenses')" :value="$fmtMoney($summary['operating_expenses'])" :href="route('butcher.finance.expenses.index', ['from' => $from, 'to' => $to])" />
                <x-kpi-card stat :title="__('Net profit')" :value="$fmtMoney($summary['net_profit'])" />
                <x-kpi-card stat :title="__('Net margin')" :value="number_format((float) $summary['net_margin_pct'], 1).'%'" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <a href="{{ route('butcher.finance.reports.sales', ['from' => $from, 'to' => $to]) }}" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha hover:border-bucha-primary">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Sales report') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Breakdown by day, product, outlet, or customer.') }}</p>
                </a>
                <a href="{{ route('butcher.finance.reports.pl', ['from' => $from, 'to' => $to]) }}" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha hover:border-bucha-primary">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Profit & loss') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Full P&L for accounting and RRA filings.') }}</p>
                </a>
                <a href="{{ route('butcher.finance.reports.cashflow', ['from' => $from, 'to' => $to]) }}" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha hover:border-bucha-primary">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Cash flow') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Daily cash in from sales vs operating expenses.') }}</p>
                </a>
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent expenses') }}</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($summary['recent_expenses'] as $expense)
                        <div class="flex justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <span>{{ $expense->description }} <span class="text-slate-500">({{ ucfirst($expense->category) }})</span></span>
                            <span class="font-semibold">{{ $fmtMoney($expense->amount) }}</span>
                        </div>
                    @empty
                        <p class="text-slate-500">{{ __('No expenses logged yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
