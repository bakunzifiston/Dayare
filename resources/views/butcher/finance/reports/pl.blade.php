@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.finance.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Finance') }}
                </a>
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="pl_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="pl_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="pl_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="pl_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.finance.reports.pl') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100">
                        <i class="ti ti-report-money text-lg leading-none" aria-hidden="true"></i>
                    </span>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Profit & loss') }}</h3>
                </div>
                <div class="px-4 py-2 sm:px-5">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-3 font-medium text-slate-900">{{ __('Revenue') }}</td>
                                <td class="py-3 text-right font-semibold tabular-nums text-emerald-700">{{ $fmtMoney($pl['revenue']) }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 pl-4 text-slate-600">{{ __('Cost of goods sold') }}</td>
                                <td class="py-3 text-right tabular-nums text-red-700">−{{ $fmtMoney($pl['cogs']) }}</td>
                            </tr>
                            <tr class="bg-slate-50/80">
                                <td class="py-3 font-semibold text-slate-900">
                                    {{ __('Gross profit') }}
                                    <span class="ml-1 text-xs font-normal text-slate-500">({{ number_format((float) $pl['gross_margin_pct'], 1) }}%)</span>
                                </td>
                                <td class="py-3 text-right font-bold tabular-nums text-slate-900">{{ $fmtMoney($pl['gross_profit']) }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 pl-4 text-slate-600">{{ __('Operating expenses') }}</td>
                                <td class="py-3 text-right tabular-nums text-red-700">−{{ $fmtMoney($pl['operating_expenses']) }}</td>
                            </tr>
                            <tr class="bg-bucha-primary/5">
                                <td class="py-4 text-base font-bold text-slate-900">{{ __('Net profit') }}</td>
                                <td class="py-4 text-right text-base font-bold tabular-nums @if((float) $pl['net_profit'] < 0) text-red-700 @else text-emerald-800 @endif">{{ $fmtMoney($pl['net_profit']) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">{{ __('Net margin') }}</td>
                                <td class="py-2 text-right font-semibold tabular-nums text-slate-900">{{ number_format((float) $pl['net_margin_pct'], 1) }}%</td>
                            </tr>
                        </tbody>
                    </table>

                    @if ($pl['expenses_by_category']->isNotEmpty())
                        <div class="mt-6 border-t border-slate-100 pt-5 pb-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expenses by category') }}</h4>
                            <ul class="mt-3 space-y-2 text-sm">
                                @foreach ($pl['expenses_by_category'] as $category => $total)
                                    <li class="flex justify-between gap-3">
                                        <span class="text-slate-700">{{ ucfirst($category) }}</span>
                                        <span class="font-medium tabular-nums text-slate-900">{{ $fmtMoney($total) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
