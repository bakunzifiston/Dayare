<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.procurement.deliveries.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Deliveries') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Receive delivery') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.procurement.deliveries.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf

                <div>
                    <x-input-label for="purchase_order_id" :value="__('Link to purchase order (optional)')" />
                    <select id="purchase_order_id" name="purchase_order_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ __('No purchase order') }}</option>
                        @foreach ($openOrders as $order)
                            <option value="{{ $order->id }}" data-supplier="{{ $order->supplier_id }}" data-meat="{{ $order->meat_type }}" @selected((string) old('purchase_order_id', $selectedOrderId) === (string) $order->id)>
                                {{ $order->po_number }} — {{ $order->supplier?->name }} ({{ ucfirst($order->meat_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="supplier_id" :value="__('Supplier')" />
                    <select id="supplier_id" name="supplier_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="outlet_id" :value="__('Receiving outlet')" />
                    <select id="outlet_id" name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="meat_type" :value="__('Meat type')" />
                    <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach (\App\Models\ButcherDelivery::MEAT_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('meat_type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="received_weight_kg" :value="__('Received weight (kg)')" />
                        <x-text-input id="received_weight_kg" name="received_weight_kg" type="number" step="0.001" min="0.1" class="mt-1 block w-full" :value="old('received_weight_kg')" required />
                        <x-input-error :messages="$errors->get('received_weight_kg')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit_cost_per_kg" :value="__('Unit cost / kg')" />
                        <x-text-input id="unit_cost_per_kg" name="unit_cost_per_kg" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_cost_per_kg')" required />
                        <x-input-error :messages="$errors->get('unit_cost_per_kg')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="condition" :value="__('Condition')" />
                        <select id="condition" name="condition" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach (\App\Models\ButcherDelivery::CONDITIONS as $condition)
                                <option value="{{ $condition }}" @selected(old('condition', 'good') === $condition)>{{ ucfirst($condition) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('condition')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="certificate_ref" :value="__('Certificate reference (optional)')" />
                        <x-text-input id="certificate_ref" name="certificate_ref" type="text" maxlength="100" class="mt-1 block w-full" :value="old('certificate_ref')" placeholder="e.g. CERT-2026-00123" />
                        <x-input-error :messages="$errors->get('certificate_ref')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="certificate_issuer" :value="__('Certificate issuer (optional)')" />
                        <x-text-input id="certificate_issuer" name="certificate_issuer" type="text" class="mt-1 block w-full" :value="old('certificate_issuer')" placeholder="e.g. RFA, Abattoir X" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>

                <p class="text-xs text-slate-500">{{ __('Good and fair deliveries automatically add stock to inventory. Rejected deliveries are logged separately.') }}</p>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Record delivery') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('purchase_order_id')?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            if (!option || !option.value) return;
            const supplier = document.getElementById('supplier_id');
            const meat = document.getElementById('meat_type');
            if (option.dataset.supplier) supplier.value = option.dataset.supplier;
            if (option.dataset.meat) meat.value = option.dataset.meat;
        });
    </script>
</x-app-layout>
