@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $marginClass = static fn (string $health): string => match ($health) {
        'negative' => 'border-red-200 bg-red-50',
        'low' => 'border-amber-200 bg-amber-50',
        default => 'border-emerald-200 bg-emerald-50',
    };
    $marginTextClass = static fn (string $health): string => match ($health) {
        'negative' => 'text-red-800',
        'low' => 'text-amber-900',
        default => 'text-emerald-800',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Product catalog') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Saleable products, margins, and outlet pricing.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.catalog.pricing.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Price rules') }}</a>
                <a href="{{ route('butcher.catalog.products.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('New product') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Active products')" :value="$summary['products_active']" :href="route('butcher.catalog.products.index')" />
                <x-kpi-card stat :title="__('Avg margin')" :value="number_format((float) $summary['avg_margin_pct'], 1).'%'" :href="route('butcher.catalog.products.index')" />
                <x-kpi-card stat :title="__('Low margin')" :value="$summary['low_margin_count']" :href="route('butcher.catalog.products.index')" />
                <x-kpi-card stat :title="__('Promotions')" :value="$summary['active_promotions']" :href="route('butcher.catalog.pricing.index')" />
            </div>

            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Products') }}</h3>
                    <a href="{{ route('butcher.catalog.products.index') }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('View all') }}</a>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($summary['products'] as $product)
                        @php $health = $product->marginHealth(); @endphp
                        <a href="{{ route('butcher.catalog.products.edit', $product) }}" class="block rounded-bucha border p-4 shadow-bucha hover:shadow-md transition {{ $marginClass($health) }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ ucfirst($product->meat_type) }} · {{ str_replace('_', ' ', $product->unit) }}</p>
                                </div>
                                <span class="text-xs font-bold {{ $marginTextClass($health) }}">{{ number_format((float) $product->margin_pct, 1) }}%</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-900">{{ $fmtMoney($product->default_price) }}</span>
                                <span class="text-xs text-slate-500">{{ __('Cost') }} {{ $fmtMoney($product->avg_cost_per_kg) }}/kg</span>
                            </div>
                            @if ($product->cutType)
                                <p class="mt-2 text-xs text-slate-500">{{ __('Cut') }}: {{ $product->cutType->name }}</p>
                            @endif
                        </a>
                    @empty
                        <div class="col-span-full rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                            {{ __('No products yet.') }}
                            <a href="{{ route('butcher.catalog.products.create') }}" class="ml-1 font-semibold text-bucha-primary hover:underline">{{ __('Create one') }}</a>
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($summary['recent_price_rules']->isNotEmpty())
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Active price rules') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @foreach ($summary['recent_price_rules'] as $rule)
                            <div class="flex flex-wrap items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                                <span>{{ $rule->product?->name }} · {{ $rule->labelDescription() }}</span>
                                <span class="font-semibold">{{ $fmtMoney($rule->price) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
