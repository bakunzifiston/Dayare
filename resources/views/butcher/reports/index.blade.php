@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.reports.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label for="from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <x-butcher.outlet-filter :outlets="$outlets" :selected="$filterOutletId" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.reports.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Stock on hand')" :value="$fmtKg($kpis['stock_kg'])" tone="bucha" icon="ti ti-package" />
                <x-butcher.kpi-card :label="__('Received (30d)')" :value="$fmtKg($kpis['received_kg'])" tone="emerald" icon="ti ti-truck-delivery" />
                <x-butcher.kpi-card :label="__('Yield (30d)')" :value="$fmtKg($kpis['yield_kg'])" tone="sky" icon="ti ti-cut" />
                <x-butcher.kpi-card :label="__('Waste (30d)')" :value="$fmtKg($kpis['waste_kg'])" tone="rose" icon="ti ti-trash" />
                <x-butcher.kpi-card :label="__('Sales (period)')" :value="(string) $kpis['sales_count']" tone="indigo" icon="ti ti-shopping-cart" />
                <x-butcher.kpi-card :label="__('Revenue (period)')" :value="$fmtMoney($kpis['revenue'])" tone="teal" icon="ti ti-cash" />
                <x-butcher.kpi-card :label="__('Active batches')" :value="(string) $kpis['batches']" tone="amber" icon="ti ti-box" />
                <x-butcher.kpi-card :label="__('Open stock counts')" :value="(string) $kpis['open_stock_counts']" tone="slate" icon="ti ti-clipboard-check" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sections as $section)
                    <a href="{{ route($section['route']) }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-bucha-primary/40 transition-colors">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $section['title'] }}</h3>
                        @if (! empty($section['stats']))
                            <dl class="mt-4 space-y-1.5 text-sm">
                                @foreach ($section['stats'] as $label => $value)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">{{ $label }}</dt>
                                        <dd class="font-medium tabular-nums text-slate-900">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </a>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Stock by meat type') }}</h3>
                </div>
                <div class="divide-y divide-slate-100 p-4 sm:p-5">
                    @foreach ($stock_by_meat as $row)
                        <div class="flex items-center justify-between py-2 text-sm first:pt-0 last:pb-0">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-slate-900">{{ $fmtKg($row['kg']) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
