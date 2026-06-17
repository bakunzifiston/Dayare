@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $exportUrl = fn (string $format) => route('butcher.finance.reports.export', ['type' => 'cashflow', 'format' => $format, 'from' => $from, 'to' => $to]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cash flow') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Daily cash in from sales vs cash out for expenses.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ $exportUrl('csv') }}" class="font-semibold text-bucha-primary hover:underline">CSV</a>
                <a href="{{ $exportUrl('xlsx') }}" class="font-semibold text-bucha-primary hover:underline">Excel</a>
                <a href="{{ $exportUrl('pdf') }}" class="font-semibold text-bucha-primary hover:underline">PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('Apply') }}</button>
            </form>

            <div class="grid grid-cols-3 gap-3">
                <x-kpi-card stat :title="__('Cash in')" :value="$fmtMoney($cashflow['total_cash_in'])" />
                <x-kpi-card stat :title="__('Cash out')" :value="$fmtMoney($cashflow['total_cash_out'])" />
                <x-kpi-card stat :title="__('Net cash flow')" :value="$fmtMoney($cashflow['net_cash_flow'])" />
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2 pr-4">{{ __('Cash in') }}</th>
                            <th class="py-2 pr-4">{{ __('Cash out') }}</th>
                            <th class="py-2">{{ __('Net') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cashflow['days'] as $day)
                            <tr class="border-b border-slate-100 @if($day['net'] < 0) bg-red-50/50 @endif">
                                <td class="py-2 pr-4">{{ $day['date'] }}</td>
                                <td class="py-2 pr-4 text-emerald-700">{{ $fmtMoney($day['cash_in']) }}</td>
                                <td class="py-2 pr-4 text-red-700">{{ $fmtMoney($day['cash_out']) }}</td>
                                <td class="py-2 font-semibold">{{ $fmtMoney($day['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
