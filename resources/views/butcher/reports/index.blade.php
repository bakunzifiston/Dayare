@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reports') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Operational overview across receiving, processing, inventory, and sales.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input id="from" type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
                <div>
                    <label for="to" class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input id="to" type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Stock on hand')" :value="$fmtKg($kpis['stock_kg'])" :href="route('butcher.inventory.index')" />
                <x-kpi-card stat :title="__('Received (30d)')" :value="$fmtKg($kpis['received_kg'])" :href="route('butcher.receiving.index')" />
                <x-kpi-card stat :title="__('Yield (30d)')" :value="$fmtKg($kpis['yield_kg'])" :href="route('butcher.processing.index')" />
                <x-kpi-card stat :title="__('Waste (30d)')" :value="$fmtKg($kpis['waste_kg'])" :href="route('butcher.waste.index')" />
                <x-kpi-card stat :title="__('Sales (period)')" :value="$kpis['sales_count']" :href="route('butcher.sales.index')" />
                <x-kpi-card stat :title="__('Revenue (period)')" :value="$fmtMoney($kpis['revenue'])" :href="route('butcher.finance.index')" />
                <x-kpi-card stat :title="__('Active batches')" :value="$kpis['batches']" :href="route('butcher.inventory.batches.index')" />
                <x-kpi-card stat :title="__('Open stock counts')" :value="$kpis['open_stock_counts']" :href="route('butcher.stock-counts.index')" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sections as $section)
                    <a href="{{ route($section['route']) }}" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha hover:border-bucha-primary">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $section['title'] }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $section['description'] }}</p>
                        @if (! empty($section['stats']))
                            <dl class="mt-4 space-y-1 text-sm">
                                @foreach ($section['stats'] as $label => $value)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">{{ $label }}</dt>
                                        <dd class="font-medium text-slate-900">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </a>
                @endforeach
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Stock by meat type') }}</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($stock_by_meat as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-slate-600">{{ $fmtKg($row['kg']) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
