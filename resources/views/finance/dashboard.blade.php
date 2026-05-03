<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Finance Dashboard') }}</span>
    </x-slot>

    @php
        $period = (string) ($kpiPeriod ?? 'all');
        $money = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-5">
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ __('Finance overview') }}</h1>
                        <p class="mt-1 text-sm text-bucha-muted">
                            {{ __('Business: :business', ['business' => $activeBusiness?->business_name ?? __('No active business selected')]) }}
                        </p>
                    </div>
                    <div class="inline-flex flex-wrap rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('Finance KPI time range') }}">
                        @foreach (['all' => __('All time'), 'day' => __('Day'), 'month' => __('Month'), 'year' => __('Year')] as $q => $title)
                            <a
                                href="{{ request()->fullUrlWithQuery(['kpi_period' => $q]) }}"
                                @class([
                                    'px-3 py-1.5 text-xs font-medium rounded-md transition',
                                    'bg-bucha-primary text-white shadow-sm' => $period === $q,
                                    'text-slate-600 hover:text-slate-900 hover:bg-white' => $period !== $q,
                                ])
                            >{{ $title }}</a>
                        @endforeach
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('KPI period: :label', ['label' => $kpiPeriodLabel ?? __('All time')]) }}</p>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <article class="rounded-bucha bg-white border border-slate-200 px-5 py-4 min-h-[132px] flex flex-col justify-between">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Revenue (invoiced)') }}</p>
                    <p class="text-3xl font-bold leading-none text-slate-900">{{ __('RWF :amount', ['amount' => $money($kpis['revenue'] ?? 0)]) }}</p>
                    <p class="text-xs text-bucha-primary/80">{{ __('Sum of finance invoices in selected period.') }}</p>
                </article>
                <article class="rounded-bucha bg-white border border-slate-200 px-5 py-4 min-h-[132px] flex flex-col justify-between">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('AP outstanding') }}</p>
                    <p class="text-3xl font-bold leading-none text-slate-900">{{ __('RWF :amount', ['amount' => $money($kpis['ap_outstanding'] ?? 0)]) }}</p>
                    <p class="text-xs text-bucha-primary/80">{{ __('Open payable balance (total - paid).') }}</p>
                </article>
                <article class="rounded-bucha bg-white border border-slate-200 px-5 py-4 min-h-[132px] flex flex-col justify-between">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Gross margin proxy') }}</p>
                    <p class="text-3xl font-bold leading-none text-slate-900">{{ number_format((float) ($kpis['gross_margin_proxy_pct'] ?? 0), 1) }}%</p>
                    <p class="text-xs text-bucha-primary/80">{{ __('(Revenue - AP outstanding - allocated costs) / Revenue') }}</p>
                </article>
                <article class="rounded-bucha bg-white border border-slate-200 px-5 py-4 min-h-[132px] flex flex-col justify-between">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ __('Overdue receivables / payables') }}</p>
                    <p class="text-3xl font-bold leading-none text-slate-900">{{ (int) ($kpis['overdue_receivables_count'] ?? 0) }} / {{ (int) ($kpis['overdue_payables_count'] ?? 0) }}</p>
                    <p class="text-xs text-bucha-primary/80">{{ __('Counts where due date has passed and balance remains unpaid.') }}</p>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
