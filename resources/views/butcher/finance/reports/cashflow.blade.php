@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.finance.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Finance') }}
                </a>
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="cashflow_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="cashflow_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="cashflow_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="cashflow_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.finance.reports.cashflow') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-butcher.kpi-card :label="__('Cash in')" :value="$fmtMoney($cashflow['total_cash_in'])" tone="emerald" icon="ti ti-arrow-down-left" />
                <x-butcher.kpi-card :label="__('Cash out')" :value="$fmtMoney($cashflow['total_cash_out'])" tone="rose" icon="ti ti-arrow-up-right" />
                <x-butcher.kpi-card :label="__('Net cash flow')" :value="$fmtMoney($cashflow['net_cash_flow'])" tone="bucha" icon="ti ti-arrows-exchange" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Daily cash flow') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 sm:px-5">{{ __('Date') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Cash in') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Cash out') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cashflow['days'] as $day)
                                <tr class="border-b border-slate-50 @if($day['net'] < 0) bg-red-50/40 @endif">
                                    <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $day['date'] }}</td>
                                    <td class="px-4 py-3 sm:px-5 tabular-nums text-emerald-700">{{ $fmtMoney($day['cash_in']) }}</td>
                                    <td class="px-4 py-3 sm:px-5 tabular-nums text-red-700">{{ $fmtMoney($day['cash_out']) }}</td>
                                    <td class="px-4 py-3 sm:px-5 font-semibold tabular-nums text-slate-900">{{ $fmtMoney($day['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
