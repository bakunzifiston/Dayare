<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.procurement.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Procurement') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Deliveries') }}</h2>
            </div>
            <a href="{{ route('butcher.procurement.deliveries.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                {{ __('Receive delivery') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
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
                                    <a href="{{ route('butcher.procurement.deliveries.show', $delivery) }}" class="text-bucha-primary hover:underline">{{ $delivery->delivery_number }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $delivery->supplier?->name }}</td>
                                <td class="px-4 py-3">{{ $delivery->outlet?->name }}</td>
                                <td class="px-4 py-3">{{ number_format((float) $delivery->received_weight_kg, 2) }}</td>
                                <td class="px-4 py-3">RWF {{ number_format((float) $delivery->total_cost, 0) }}</td>
                                <td class="px-4 py-3"><x-butcher.status-badge :status="$delivery->condition" /></td>
                                <td class="px-4 py-3">{{ $delivery->received_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No deliveries recorded yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $deliveries->links() }}</div>
        </div>
    </div>
</x-app-layout>
