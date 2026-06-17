<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('butcher.procurement.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Procurement') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Purchase orders') }}</h2>
            </div>
            <a href="{{ route('butcher.procurement.orders.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                {{ __('New order') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('PO number') }}</th>
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
                                    <a href="{{ route('butcher.procurement.orders.show', $order) }}" class="text-bucha-primary hover:underline">{{ $order->po_number }}</a>
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
            <div class="mt-4">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
