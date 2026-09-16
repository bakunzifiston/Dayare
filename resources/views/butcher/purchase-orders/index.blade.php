@php
    $filters = $filters ?? ['q' => '', 'status' => 'all', 'meat_type' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'open' => 0, 'delivered' => 0, 'requested_kg' => 0];
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $statusLabel = static fn (string $status): string => ucfirst($status);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.purchase-orders.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="po_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input
                                id="po_q"
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="{{ __('PO #, supplier…') }}"
                                class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary"
                            >
                        </div>
                        <div>
                            <label for="po_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="po_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabel($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="po_meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat') }}</label>
                            <select id="po_meat_type" name="meat_type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['meat_type'] === 'all')>{{ __('All types') }}</option>
                                @foreach ($meatTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['meat_type'] === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.purchase-orders.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a
                            href="{{ route('butcher.purchase-orders.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10"
                        >
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('New purchase order') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Total orders')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-file-invoice" />
                <x-butcher.kpi-card :label="__('Open')" :value="(string) $kpis['open']" tone="amber" icon="ti ti-clock" />
                <x-butcher.kpi-card :label="__('Delivered')" :value="(string) $kpis['delivered']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Requested')" :value="$fmtKg($kpis['requested_kg'])" tone="sky" icon="ti ti-scale" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Purchase orders') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Orders for suppliers and receiving.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count order|:count orders', $orders->total(), ['count' => $orders->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('PO') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Supplier') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Meat') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Weight') }}</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-5">{{ __('Requested') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-file-invoice text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $order->po_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500 sm:hidden capitalize">{{ $order->meat_type }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $order->supplier?->name ?: '—' }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 capitalize text-slate-700">{{ $order->meat_type }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ number_format((float) $order->requested_weight_kg, 2) }} kg</td>
                                    <td class="hidden lg:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $order->requested_date?->format('Y-m-d') ?: '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$order->status" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a
                                            href="{{ route('butcher.purchase-orders.show', $order) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' || $filters['meat_type'] !== 'all'
                                            ? __('No purchase orders match your filters.')
                                            : __('No purchase orders yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $orders->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
