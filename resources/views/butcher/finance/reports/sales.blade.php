@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $exportUrl = fn (string $format) => route('butcher.finance.reports.export', ['type' => 'sales', 'format' => $format, 'from' => $from, 'to' => $to, 'group_by' => $groupBy]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sales report') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Revenue breakdown for the selected period.') }}</p>
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
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Group by') }}</label>
                    <select name="group_by" class="mt-1 block rounded-lg border-gray-300 text-sm">
                        @foreach (['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'product' => __('Product'), 'outlet' => __('Outlet'), 'customer' => __('Customer')] as $value => $label)
                            <option value="{{ $value }}" @selected($groupBy === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('Apply') }}</button>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-2">
                <x-kpi-card stat :title="__('Total revenue')" :value="$fmtMoney($report['total_revenue'])" />
                <x-kpi-card stat :title="__('Sales count')" :value="$report['total_sales']" />
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Group') }}</th>
                            <th class="py-2 pr-4">{{ __('Sales') }}</th>
                            <th class="py-2">{{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['groups'] as $group)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium">{{ $group['label'] }}</td>
                                <td class="py-3 pr-4">{{ $group['sales_count'] ?? '—' }}</td>
                                <td class="py-3 font-semibold">{{ $fmtMoney($group['revenue']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-500">{{ __('No sales in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
