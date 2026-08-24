@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 1).' kg';
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Receiving') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Record incoming meat deliveries and track received stock.') }}</p>
            </div>
            <a href="{{ route('butcher.receiving.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                {{ __('Receive delivery') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Deliveries')" :value="$summary['deliveries_total']" />
                <x-kpi-card stat :title="__('Received (kg)')" :value="$fmtKg($summary['received_weight_kg'])" />
                <x-kpi-card stat :title="__('Total spend')" :value="$fmtMoney($summary['total_spend'])" />
                <x-kpi-card stat :title="__('Rejected')" :value="$summary['rejected_deliveries']" />
            </div>

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Delivery history') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Delivery #') }}</th>
                                <th class="px-4 py-3">{{ __('Supplier') }}</th>
                                <th class="px-4 py-3">{{ __('Outlet') }}</th>
                                <th class="px-4 py-3">{{ __('Weight (kg)') }}</th>
                                <th class="px-4 py-3">{{ __('Total cost') }}</th>
                                <th class="px-4 py-3">{{ __('Condition') }}</th>
                                <th class="px-4 py-3">{{ __('Received') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($deliveries as $delivery)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium">
                                        <a href="{{ route('butcher.receiving.show', $delivery) }}" class="text-bucha-primary hover:underline">{{ $delivery->delivery_number }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $delivery->supplier?->name }}</td>
                                    <td class="px-4 py-3">{{ $delivery->outlet?->name }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $delivery->received_weight_kg, 2) }}</td>
                                    <td class="px-4 py-3">{{ $fmtMoney($delivery->total_cost) }}</td>
                                    <td class="px-4 py-3"><x-butcher.status-badge :status="$delivery->condition" /></td>
                                    <td class="px-4 py-3">{{ $delivery->received_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No deliveries recorded yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">{{ $deliveries->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
