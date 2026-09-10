@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Purchase orders') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Agree to buy before goods arrive, then receive against the order.') }}</p>
            </div>
            <a href="{{ route('butcher.purchase-orders.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                {{ __('New purchase order') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Orders (period)')" :value="$summary['orders_total']" />
                <x-kpi-card stat :title="__('Open orders')" :value="$summary['orders_open']" />
                <x-kpi-card stat :title="__('Received (kg)')" :value="$fmtKg($summary['received_weight_kg'])" />
                <x-kpi-card stat :title="__('Total spend')" :value="$fmtMoney($summary['total_spend'])" />
            </div>

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Purchase order list') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('PO #') }}</th>
                                <th class="px-4 py-3">{{ __('Supplier') }}</th>
                                <th class="px-4 py-3">{{ __('Meat') }}</th>
                                <th class="px-4 py-3">{{ __('Weight (kg)') }}</th>
                                <th class="px-4 py-3">{{ __('Requested') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium">
                                        <a href="{{ route('butcher.purchase-orders.show', $order) }}" class="text-bucha-primary hover:underline">{{ $order->po_number }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $order->supplier?->name }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $order->meat_type }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $order->requested_weight_kg, 2) }}</td>
                                    <td class="px-4 py-3">{{ $order->requested_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3"><x-butcher.status-badge :status="$order->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No purchase orders yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">{{ $orders->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
