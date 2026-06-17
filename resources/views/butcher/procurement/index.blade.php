@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stock procurement') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Purchase orders, deliveries, and incoming stock.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.procurement.orders.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('New purchase order') }}
                </a>
                <a href="{{ route('butcher.procurement.deliveries.create') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Receive delivery') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="period" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Period') }}</label>
                    <select id="period" name="period" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                        @foreach (['7d' => __('Last 7 days'), '30d' => __('Last 30 days'), '90d' => __('Last 90 days'), 'month' => __('This month')] as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Purchase orders')" :value="$summary['orders_total']" :href="route('butcher.procurement.orders.index')" />
                <x-kpi-card stat :title="__('Open orders')" :value="$summary['orders_open']" :href="route('butcher.procurement.orders.index')" />
                <x-kpi-card stat :title="__('Received (kg)')" :value="$fmtKg($summary['received_weight_kg'])" :href="route('butcher.procurement.deliveries.index')" />
                <x-kpi-card stat :title="__('Total spend')" :value="$fmtMoney($summary['total_spend'])" :href="route('butcher.procurement.deliveries.index')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent purchase orders') }}</h3>
                        <a href="{{ route('butcher.procurement.orders.index') }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('View all') }}</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($summary['recent_orders'] as $order)
                            <a href="{{ route('butcher.procurement.orders.show', $order) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ $order->po_number }}</p>
                                    <x-butcher.status-badge :status="$order->status" />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $order->supplier?->name }} · {{ ucfirst($order->meat_type) }} · {{ $fmtKg($order->requested_weight_kg) }} kg</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No purchase orders yet.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent deliveries') }}</h3>
                        <a href="{{ route('butcher.procurement.deliveries.index') }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('View all') }}</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($summary['recent_deliveries'] as $delivery)
                            <a href="{{ route('butcher.procurement.deliveries.show', $delivery) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ $delivery->delivery_number }}</p>
                                    <x-butcher.status-badge :status="$delivery->condition" />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $delivery->supplier?->name }} · {{ $fmtKg($delivery->received_weight_kg) }} kg · {{ $fmtMoney($delivery->total_cost) }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No deliveries recorded yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
