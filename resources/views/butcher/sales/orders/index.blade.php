@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Orders') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Pre-orders and wholesale order pipeline.') }}</p>
            </div>
            <a href="{{ route('butcher.sales.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back to sales') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha" x-data="{ items: [{ product_id: '', quantity_kg: '', quantity_units: '' }] }">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('New order') }}</h3>
                <form method="post" action="{{ route('butcher.sales.orders.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Customer') }}</label>
                            <select name="customer_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ ucfirst($customer->tier) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Order date') }}</label>
                            <input type="date" name="order_date" value="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Delivery date') }}</label>
                            <input type="date" name="delivery_date" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Deposit paid') }}</label>
                            <input type="number" name="deposit_paid" min="0" value="0" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <select :name="'items[' + index + '][product_id]'" required class="rounded-lg border-gray-300 text-sm">
                                <option value="">{{ __('Product…') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" min="0" :name="'items[' + index + '][quantity_kg]'" placeholder="kg" class="rounded-lg border-gray-300 text-sm">
                            <input type="number" min="1" :name="'items[' + index + '][quantity_units]'" placeholder="{{ __('Units') }}" class="rounded-lg border-gray-300 text-sm">
                            <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1" class="text-sm text-red-600">{{ __('Remove') }}</button>
                        </div>
                    </template>
                    <button type="button" @click="items.push({ product_id: '', quantity_kg: '', quantity_units: '' })" class="text-sm font-semibold text-bucha-primary">{{ __('Add line') }}</button>
                    <div>
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Create order') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Order') }}</th>
                            <th class="py-2 pr-4">{{ __('Customer') }}</th>
                            <th class="py-2 pr-4">{{ __('Total') }}</th>
                            <th class="py-2 pr-4">{{ __('Delivery') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-semibold">{{ $order->order_number }}</td>
                                <td class="py-3 pr-4">{{ $order->customer?->name }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney($order->total_amount) }}</td>
                                <td class="py-3 pr-4">{{ $order->delivery_date?->toDateString() ?? '—' }}</td>
                                <td class="py-3">
                                    <form method="post" action="{{ route('butcher.sales.orders.status', $order) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="rounded border-gray-300 text-xs" onchange="this.form.submit()">
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No orders yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $orders->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
