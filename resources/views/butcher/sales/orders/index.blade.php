@php
    $filters = $filters ?? ['q' => '', 'status' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'pending' => 0, 'ready' => 0, 'confirmed' => 0];
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.sales.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Sales') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.sales.orders.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="order_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="order_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Order #, customer…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="order_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="order_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.sales.orders.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.sales.orders.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('New order') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Orders')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-clipboard-list" />
                <x-butcher.kpi-card :label="__('Pending')" :value="(string) $kpis['pending']" tone="amber" icon="ti ti-clock" />
                <x-butcher.kpi-card :label="__('Confirmed')" :value="(string) $kpis['confirmed']" tone="sky" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Ready')" :value="(string) $kpis['ready']" tone="emerald" icon="ti ti-package" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Orders') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Pre-orders awaiting fulfillment.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count order|:count orders', $orders->total(), ['count' => $orders->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Order') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Customer') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Total') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Delivery') }}</th>
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
                                                <i class="ti ti-clipboard-list text-[1.15rem] leading-none"></i>
                                            </span>
                                            <p class="font-medium text-slate-900">{{ $order->order_number }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $order->customer?->name ?: '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums font-semibold text-slate-900">{{ $fmtMoney($order->total_amount) }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $order->delivery_date?->toDateString() ?? '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$order->status" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.sales.orders.show', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' ? __('No orders match your filters.') : __('No orders yet.') }}
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
