@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.sales.orders.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Orders') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-clipboard-list text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('New order') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Create a pre-order for later fulfillment.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ route('butcher.sales.orders.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
                x-data="{ items: [{ product_id: '', quantity_kg: '', quantity_units: '' }] }"
            >
                @csrf
                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Order') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</label>
                                <select name="customer_id" required class="{{ $fieldClass }}">
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} ({{ ucfirst($customer->tier) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                                <select name="outlet_id" class="{{ $fieldClass }}">
                                    <option value="">{{ __('Select later') }}</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Order date') }}</label>
                                <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Delivery date') }}</label>
                                <input type="date" name="delivery_date" value="{{ old('delivery_date') }}" class="{{ $fieldClass }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Deposit paid') }}</label>
                                <input type="number" name="deposit_paid" min="0" value="{{ old('deposit_paid', 0) }}" class="{{ $fieldClass }}">
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Items') }}</h3>
                            <button type="button" @click="items.push({ product_id: '', quantity_kg: '', quantity_units: '' })" class="inline-flex items-center gap-1 text-sm font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                {{ __('Add line') }}
                            </button>
                        </div>
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50/40 p-3 sm:grid-cols-4">
                                <select :name="'items[' + index + '][product_id]'" required class="{{ $fieldClass }} mt-0">
                                    <option value="">{{ __('Product…') }}</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.001" min="0" :name="'items[' + index + '][quantity_kg]'" placeholder="kg" class="{{ $fieldClass }} mt-0">
                                <input type="number" min="1" :name="'items[' + index + '][quantity_units]'" placeholder="{{ __('Units') }}" class="{{ $fieldClass }} mt-0">
                                <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1" class="inline-flex items-center justify-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700">
                                    <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </template>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.sales.orders.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Create order') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
