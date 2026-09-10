@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.catalog.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Catalog') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $product->name }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($product->is_active)
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ __('Inactive') }}</span>
                @endif
                @if ($canManage)
                    <a href="{{ route('butcher.catalog.products.edit', $product) }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ __('Edit') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">{{ __('Cut type') }}</dt><dd class="mt-1 font-medium">{{ $product->cutType?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Meat type') }}</dt><dd class="mt-1 font-medium capitalize">{{ $product->meat_type }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Unit') }}</dt><dd class="mt-1 font-medium">{{ str_replace('_', ' ', $product->unit) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Default price') }}</dt><dd class="mt-1 font-medium">{{ $fmtMoney($product->default_price) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Average cost') }}</dt><dd class="mt-1 font-medium">{{ $fmtMoney($avgCost) }}</dd></div>
                    <div>
                        <dt class="text-slate-500">{{ __('Default margin') }}</dt>
                        <dd class="mt-1 font-medium">{{ $fmtMoney((float) $product->default_price - $avgCost) }}</dd>
                    </div>
                </dl>
                @unless ($hasRetailRule)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        {{ __('This product needs an active retail price rule before it can be sold in POS.') }}
                    </p>
                @endunless
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Price rules') }}</h3>
                    @if ($canManage)
                        <a href="{{ route('butcher.catalog.price-rules.create', $product) }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add price rule') }}</a>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2">{{ __('Tier') }}</th>
                                <th class="px-3 py-2">{{ __('Outlet') }}</th>
                                <th class="px-3 py-2">{{ __('Price') }}</th>
                                <th class="px-3 py-2">{{ __('Avg cost') }}</th>
                                <th class="px-3 py-2">{{ __('Margin') }}</th>
                                <th class="px-3 py-2">{{ __('Valid') }}</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($product->priceRules->sortBy('customer_tier') as $rule)
                                @php $margin = (float) $rule->price - $avgCost; @endphp
                                <tr>
                                    <td class="px-3 py-2 capitalize">{{ $rule->customer_tier ?: __('All tiers') }}</td>
                                    <td class="px-3 py-2">{{ $rule->outlet?->name ?? __('All outlets') }}</td>
                                    <td class="px-3 py-2 font-medium">{{ $fmtMoney($rule->price) }}</td>
                                    <td class="px-3 py-2">{{ $fmtMoney($avgCost) }}</td>
                                    <td class="px-3 py-2 {{ $margin < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $fmtMoney($margin) }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-500">
                                        {{ $rule->valid_from?->format('Y-m-d') }}
                                        @if ($rule->valid_until) → {{ $rule->valid_until->format('Y-m-d') }} @else → ∞ @endif
                                        @unless ($rule->is_active) · {{ __('Inactive') }} @endunless
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @if ($canManage)
                                            <a href="{{ route('butcher.catalog.price-rules.edit', [$product, $rule]) }}" class="font-semibold text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">{{ __('No price rules yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
