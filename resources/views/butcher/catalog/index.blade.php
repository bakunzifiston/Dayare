@php
    $filters = $filters ?? ['q' => '', 'status' => 'all', 'meat_type' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'with_rules' => 0];
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.catalog.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="catalog_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input
                                id="catalog_q"
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="{{ __('Name, cut, meat…') }}"
                                class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary"
                            >
                        </div>
                        <div>
                            <label for="catalog_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="catalog_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                <option value="active" @selected($filters['status'] === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="catalog_meat" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat') }}</label>
                            <select id="catalog_meat" name="meat_type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['meat_type'] === 'all')>{{ __('All types') }}</option>
                                @foreach ($meatTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['meat_type'] === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.catalog.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        @if ($canManage)
                            <a
                                href="{{ route('butcher.catalog.products.create') }}"
                                class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10"
                            >
                                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                {{ __('New product') }}
                            </a>
                        @endif
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Products')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-meat" />
                <x-butcher.kpi-card :label="__('Active')" :value="(string) $kpis['active']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Inactive')" :value="(string) $kpis['inactive']" tone="amber" icon="ti ti-ban" />
                <x-butcher.kpi-card :label="__('With price rules')" :value="(string) $kpis['with_rules']" tone="sky" icon="ti ti-tags" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Product catalog') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Products for POS and orders.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Product') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Cut type') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Unit') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Price') }}</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Avg cost') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($products as $product)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-meat text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500 capitalize">{{ $product->meat_type }}@if($product->cutType)<span class="sm:hidden"> · {{ $product->cutType->name }}</span>@endif</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $product->cutType?->name ?? '—' }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ str_replace('_', ' ', $product->unit) }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($product->default_price) }}</td>
                                    <td class="hidden lg:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtMoney($product->avg_cost_per_kg) }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($product->is_active)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200/80">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('butcher.catalog.products.show', $product) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                                {{ __('View') }}
                                            </a>
                                            @if ($canManage)
                                                <a
                                                    href="{{ route('butcher.catalog.products.edit', $product) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                >
                                                    <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                                    {{ __('Edit') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' || $filters['meat_type'] !== 'all'
                                            ? __('No products match your filters.')
                                            : __('No products yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $products->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
