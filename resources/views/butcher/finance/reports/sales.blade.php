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
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="sales_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="sales_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="sales_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="sales_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="sales_group_by" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Group by') }}</label>
                            <select id="sales_group_by" name="group_by" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                @foreach (['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'product' => __('Product'), 'outlet' => __('Outlet'), 'customer' => __('Customer')] as $value => $label)
                                    <option value="{{ $value }}" @selected($groupBy === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.finance.reports.sales') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3">
                <x-butcher.kpi-card :label="__('Total revenue')" :value="$fmtMoney($report['total_revenue'])" tone="emerald" icon="ti ti-cash" />
                <x-butcher.kpi-card :label="__('Sales count')" :value="(string) $report['total_sales']" tone="bucha" icon="ti ti-receipt" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Sales by group') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 sm:px-5">{{ __('Group') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Sales') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['groups'] as $group)
                                <tr class="border-b border-slate-50">
                                    <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $group['label'] }}</td>
                                    <td class="px-4 py-3 sm:px-5 tabular-nums text-slate-700">{{ $group['sales_count'] ?? '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5 font-semibold tabular-nums text-slate-900">{{ $fmtMoney($group['revenue']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-10 text-center text-slate-500 sm:px-5">{{ __('No sales in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
