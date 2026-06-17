@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Products') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('All saleable products with cost and margin.') }}</p>
            </div>
            <a href="{{ route('butcher.catalog.products.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('New product') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                            <th class="py-2 pr-4">{{ __('Unit') }}</th>
                            <th class="py-2 pr-4">{{ __('Price') }}</th>
                            <th class="py-2 pr-4">{{ __('Avg cost/kg') }}</th>
                            <th class="py-2 pr-4">{{ __('Margin %') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-3 pr-4">
                                    <a href="{{ route('butcher.catalog.products.edit', $product) }}" class="font-semibold text-bucha-primary hover:underline">{{ $product->name }}</a>
                                    @if ($product->cutType)
                                        <p class="text-xs text-slate-500">{{ $product->cutType->name }}</p>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">{{ ucfirst($product->meat_type) }}</td>
                                <td class="py-3 pr-4">{{ str_replace('_', ' ', $product->unit) }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney($product->default_price) }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney($product->avg_cost_per_kg) }}</td>
                                <td class="py-3 pr-4">
                                    @php $health = $product->marginHealth(); @endphp
                                    <span class="@if($health === 'negative') text-red-700 @elseif($health === 'low') text-amber-700 @else text-emerald-700 @endif font-semibold">
                                        {{ number_format((float) $product->margin_pct, 1) }}%
                                    </span>
                                </td>
                                <td class="py-3">
                                    @if ($product->is_active)
                                        <x-butcher.status-badge status="in_storage" />
                                    @else
                                        <x-butcher.status-badge status="cancelled" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-slate-500">{{ __('No products yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $products->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
