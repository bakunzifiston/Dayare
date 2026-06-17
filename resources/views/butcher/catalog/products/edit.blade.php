<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit product') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $product->name }}</p>
            </div>
            <a href="{{ route('butcher.catalog.products.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <x-kpi-card stat :title="__('Avg cost/kg')" :value="'RWF '.number_format((float) $product->avg_cost_per_kg, 0)" />
                <x-kpi-card stat :title="__('Margin')" :value="number_format((float) $product->margin_pct, 1).'%'" />
            </div>

            @include('butcher.catalog.products.partials.form', [
                'action' => route('butcher.catalog.products.update', $product),
                'method' => 'PUT',
                'product' => $product,
                'cutTypes' => $cutTypes,
                'meatTypes' => $meatTypes,
                'units' => $units,
            ])
        </div>
    </div>
</x-app-layout>
