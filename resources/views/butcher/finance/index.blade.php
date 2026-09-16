@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.finance.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="finance_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="finance_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="finance_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="finance_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.finance.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.finance.expenses.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Log expense') }}
                        </a>
                        <a href="{{ route('butcher.finance.reports.pl', ['from' => $from, 'to' => $to]) }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="ti ti-report-analytics text-base leading-none" aria-hidden="true"></i>
                            {{ __('P&L report') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
                <x-butcher.kpi-card :label="__('Revenue')" :value="$fmtMoney($summary['revenue'])" tone="emerald" icon="ti ti-cash" />
                <x-butcher.kpi-card :label="__('COGS')" :value="$fmtMoney($summary['cogs'])" tone="amber" icon="ti ti-package" />
                <x-butcher.kpi-card :label="__('Gross profit')" :value="$fmtMoney($summary['gross_profit'])" tone="sky" icon="ti ti-chart-line" />
                <x-butcher.kpi-card :label="__('Expenses')" :value="$fmtMoney($summary['operating_expenses'])" tone="rose" icon="ti ti-receipt" />
                <x-butcher.kpi-card :label="__('Net profit')" :value="$fmtMoney($summary['net_profit'])" tone="bucha" icon="ti ti-trending-up" />
                <x-butcher.kpi-card :label="__('Net margin')" :value="number_format((float) $summary['net_margin_pct'], 1).'%'" tone="indigo" icon="ti ti-percentage" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('butcher.finance.reports.sales', ['from' => $from, 'to' => $to]) }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-bucha-primary/40 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100"><i class="ti ti-shopping-cart text-lg leading-none"></i></span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Sales report') }}</h3>
                    </div>
                </a>
                <a href="{{ route('butcher.finance.reports.pl', ['from' => $from, 'to' => $to]) }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-bucha-primary/40 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100"><i class="ti ti-report-money text-lg leading-none"></i></span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Profit & loss') }}</h3>
                    </div>
                </a>
                <a href="{{ route('butcher.finance.reports.cashflow', ['from' => $from, 'to' => $to]) }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-bucha-primary/40 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100"><i class="ti ti-arrows-exchange text-lg leading-none"></i></span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Cash flow') }}</h3>
                    </div>
                </a>
                <a href="{{ route('butcher.finance.receivables.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-bucha-primary/40 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100"><i class="ti ti-file-invoice text-lg leading-none"></i></span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Receivables') }}</h3>
                    </div>
                </a>
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent expenses') }}</h3>
                </div>
                <div class="divide-y divide-slate-100 p-4 sm:p-5">
                    @forelse ($summary['recent_expenses'] as $expense)
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm first:pt-0 last:pb-0">
                            <span class="text-slate-700">{{ $expense->description }} <span class="text-slate-500">({{ ucfirst($expense->category) }})</span></span>
                            <span class="font-semibold tabular-nums text-slate-900">{{ $fmtMoney($expense->amount) }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-slate-500">{{ __('No expenses logged yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
