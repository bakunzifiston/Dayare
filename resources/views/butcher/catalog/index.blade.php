@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Catalog') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Products and price rules for POS.') }}</p>
            </div>
            @if ($canManage)
                <a href="{{ route('butcher.catalog.products.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('New product') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="get" class="flex flex-wrap gap-3 rounded-bucha border border-slate-200/80 bg-white p-4 shadow-bucha">
                <div class="min-w-[12rem] flex-1">
                    <x-text-input name="q" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search products…') }}" />
                </div>
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="all" @selected($status === 'all')>{{ __('All') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('Inactive') }}</option>
                </select>
                <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ __('Filter') }}</button>
            </form>

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Product') }}</th>
                                <th class="px-4 py-3">{{ __('Cut type') }}</th>
                                <th class="px-4 py-3">{{ __('Unit') }}</th>
                                <th class="px-4 py-3">{{ __('Default price') }}</th>
                                <th class="px-4 py-3">{{ __('Avg cost') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $product)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium">
                                        <a href="{{ route('butcher.catalog.products.show', $product) }}" class="text-bucha-primary hover:underline">{{ $product->name }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $product->cutType?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ str_replace('_', ' ', $product->unit) }}</td>
                                    <td class="px-4 py-3">{{ $fmtMoney($product->default_price) }}</td>
                                    <td class="px-4 py-3">{{ $fmtMoney($product->avg_cost_per_kg) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($product->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No products yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">{{ $products->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
