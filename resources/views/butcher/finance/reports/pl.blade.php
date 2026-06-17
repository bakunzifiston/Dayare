@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $exportUrl = fn (string $format) => route('butcher.finance.reports.export', ['type' => 'pl', 'format' => $format, 'from' => $from, 'to' => $to]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Profit & loss statement') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $from }} — {{ $to }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ $exportUrl('csv') }}" class="font-semibold text-bucha-primary hover:underline">CSV</a>
                <a href="{{ $exportUrl('xlsx') }}" class="font-semibold text-bucha-primary hover:underline">Excel</a>
                <a href="{{ $exportUrl('pdf') }}" class="font-semibold text-bucha-primary hover:underline">PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr><td class="py-3 font-medium">{{ __('Revenue') }}</td><td class="py-3 text-right font-semibold text-emerald-700">{{ $fmtMoney($pl['revenue']) }}</td></tr>
                        <tr><td class="py-3 pl-4 text-slate-600">{{ __('Cost of goods sold') }}</td><td class="py-3 text-right text-red-700">−{{ $fmtMoney($pl['cogs']) }}</td></tr>
                        <tr class="bg-slate-50"><td class="py-3 font-semibold">{{ __('Gross profit') }} <span class="text-xs text-slate-500">({{ number_format((float) $pl['gross_margin_pct'], 1) }}%)</span></td><td class="py-3 text-right font-bold">{{ $fmtMoney($pl['gross_profit']) }}</td></tr>
                        <tr><td class="py-3 pl-4 text-slate-600">{{ __('Operating expenses') }}</td><td class="py-3 text-right text-red-700">−{{ $fmtMoney($pl['operating_expenses']) }}</td></tr>
                        <tr class="bg-bucha-primary/5"><td class="py-4 text-lg font-bold">{{ __('Net profit') }}</td><td class="py-4 text-right text-lg font-bold @if((float)$pl['net_profit'] < 0) text-red-700 @else text-emerald-800 @endif">{{ $fmtMoney($pl['net_profit']) }}</td></tr>
                        <tr><td class="py-2 text-slate-500">{{ __('Net margin') }}</td><td class="py-2 text-right font-semibold">{{ number_format((float) $pl['net_margin_pct'], 1) }}%</td></tr>
                    </tbody>
                </table>

                @if ($pl['expenses_by_category']->isNotEmpty())
                    <h4 class="mt-8 text-xs font-semibold uppercase text-slate-500">{{ __('Expenses by category') }}</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($pl['expenses_by_category'] as $category => $total)
                            <li class="flex justify-between"><span>{{ ucfirst($category) }}</span><span>{{ $fmtMoney($total) }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
