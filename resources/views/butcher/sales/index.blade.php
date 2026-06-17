@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sales') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Completed sales, receipts, and daily totals.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.sales.customers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Customers') }}</a>
                <a href="{{ route('butcher.sales.orders.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Orders') }}</a>
                <a href="{{ route('butcher.sales.pos') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open POS') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Date') }}</label>
                    <input id="date" type="date" name="date" value="{{ $filterDate }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
                <div>
                    <label for="status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                    <select id="status" name="status" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        @foreach (\App\Models\ButcherSale::STATUSES as $st)
                            <option value="{{ $st }}" @selected($filterStatus === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Sales today')" :value="$summary['sales_count']" />
                <x-kpi-card stat :title="__('Gross total')" :value="$fmtMoney($summary['gross_total'])" />
                <x-kpi-card stat :title="__('Discounts')" :value="$fmtMoney($summary['discount_total'])" />
                <x-kpi-card stat :title="__('Cancelled')" :value="$summary['cancelled_count']" />
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Sale #') }}</th>
                            <th class="py-2 pr-4">{{ __('Customer') }}</th>
                            <th class="py-2 pr-4">{{ __('Outlet') }}</th>
                            <th class="py-2 pr-4">{{ __('Total') }}</th>
                            <th class="py-2 pr-4">{{ __('Payment') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-3 pr-4">
                                    <a href="{{ route('butcher.sales.show', $sale) }}" class="font-semibold text-bucha-primary hover:underline">{{ $sale->sale_number }}</a>
                                    <p class="text-xs text-slate-500">{{ $sale->sale_date?->toDateString() }}</p>
                                </td>
                                <td class="py-3 pr-4">{{ $sale->customer?->name ?? __('Walk-in') }}</td>
                                <td class="py-3 pr-4">{{ $sale->outlet?->name }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ $fmtMoney($sale->total_amount) }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($sale->payment_method) }}</td>
                                <td class="py-3"><x-butcher.status-badge :status="$sale->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-slate-500">{{ __('No sales for this filter.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $sales->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
