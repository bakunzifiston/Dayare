<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Point of sale') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Record sales and deduct cut stock in real time.') }}</p>
            </div>
            <a href="{{ route('butcher.sales.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Sales list') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
<div
    x-data="butcherPos({
        products: @js($products),
        outlets: @js($outlets->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])->values()),
        customers: @js($customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'tier' => $c->tier])->values()),
        selectedOutletId: @js($selectedOutletId),
        selectedCustomerId: @js($selectedCustomerId),
        storeUrl: @js(route('butcher.sales.store')),
        csrf: @js(csrf_token()),
    })"
    class="space-y-6"
>
    <form method="post" :action="storeUrl" @submit.prevent="submitSale">
        @csrf
        <input type="hidden" name="outlet_id" :value="outletId">
        <input type="hidden" name="customer_id" :value="customerId || ''">
        <input type="hidden" name="payment_method" :value="paymentMethod">
        <input type="hidden" name="amount_paid" :value="amountPaid">
        <input type="hidden" name="discount_amount" :value="discount">
        <template x-for="(item, index) in cart" :key="index">
            <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
            <input type="hidden" :name="'items[' + index + '][quantity_kg]'" :value="item.quantity_kg">
            <input type="hidden" :name="'items[' + index + '][quantity_units]'" :value="item.quantity_units || ''">
        </template>
        <template x-for="(split, index) in splitPayments" :key="'split-' + index">
            <input type="hidden" :name="'split_payments[' + index + '][payment_method]'" :value="split.payment_method">
            <input type="hidden" :name="'split_payments[' + index + '][amount]'" :value="split.amount">
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <div class="flex flex-wrap gap-3">
                    <div class="flex-1 min-w-[140px]">
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Outlet') }}</label>
                        <select x-model.number="outletId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <template x-for="outlet in outlets" :key="outlet.id">
                                <option :value="outlet.id" x-text="outlet.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[140px]">
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Customer') }}</label>
                        <select x-model="customerId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Walk-in') }}</option>
                            <template x-for="customer in customers" :key="customer.id">
                                <option :value="customer.id" x-text="customer.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Search products') }}</label>
                        <input type="search" x-model="search" placeholder="{{ __('Type to filter…') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <template x-for="product in filteredProducts()" :key="product.id">
                        <button type="button" @click="addToCart(product)" class="rounded-bucha border border-slate-200 bg-white p-3 text-left shadow-bucha hover:border-bucha-primary hover:bg-slate-50">
                            <p class="text-sm font-semibold text-slate-900" x-text="product.name"></p>
                            <p class="mt-1 text-xs text-slate-500" x-text="product.unit.replace('_', ' ')"></p>
                            <p class="mt-2 text-sm font-bold text-bucha-primary" x-text="formatMoney(product.price)"></p>
                            <p class="text-xs text-slate-400" x-show="product.stock_kg > 0" x-text="product.stock_kg.toFixed(2) + ' kg in stock'"></p>
                        </button>
                    </template>
                </div>
            </div>

            <div class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Cart') }}</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <p class="text-sm text-slate-500">{{ __('Tap a product to add.') }}</p>
                    </template>
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="rounded-lg border border-slate-200 p-3 text-sm">
                            <div class="flex justify-between gap-2">
                                <span class="font-medium" x-text="item.name"></span>
                                <button type="button" @click="removeFromCart(index)" class="text-red-600 text-xs">{{ __('Remove') }}</button>
                            </div>
                            <div class="mt-2 flex gap-2" x-show="item.unit === 'per_kg'">
                                <input type="number" step="0.001" min="0.01" x-model.number="item.quantity_kg" @input="updateLine(index)" class="w-full rounded border-gray-300 text-sm" placeholder="kg">
                            </div>
                            <div class="mt-2 flex gap-2" x-show="item.unit !== 'per_kg'">
                                <input type="number" min="1" x-model.number="item.quantity_units" @input="updateLine(index)" class="w-full rounded border-gray-300 text-sm" placeholder="{{ __('Qty') }}">
                            </div>
                            <p class="mt-1 text-right font-semibold" x-text="formatMoney(item.line_total)"></p>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-200 pt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span x-text="formatMoney(subtotal())"></span></div>
                    <div class="flex justify-between items-center gap-2">
                        <span>{{ __('Discount') }}</span>
                        <input type="number" min="0" x-model.number="discount" class="w-24 rounded border-gray-300 text-sm text-right">
                    </div>
                    <div class="flex justify-between font-bold text-base"><span>{{ __('Total') }}</span><span x-text="formatMoney(total())"></span></div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Payment') }}</label>
                    <select x-model="paymentMethod" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($paymentMethods as $method)
                            @if ($method !== 'split')
                                <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                            @endif
                        @endforeach
                        <option value="split">{{ __('Split payment') }}</option>
                    </select>
                </div>

                <div x-show="paymentMethod !== 'credit' && paymentMethod !== 'split'">
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Amount paid') }}</label>
                    <input type="number" min="0" x-model.number="amountPaid" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    <p class="mt-1 text-xs text-slate-500" x-show="paymentMethod === 'cash' && amountPaid > total()">
                        {{ __('Change') }}: <span x-text="formatMoney(Math.max(amountPaid - total(), 0))"></span>
                    </p>
                </div>

                <div x-show="paymentMethod === 'split'" class="space-y-2">
                    <template x-for="(split, index) in splitPayments" :key="index">
                        <div class="flex gap-2">
                            <select x-model="split.payment_method" class="rounded border-gray-300 text-sm">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="momo">MoMo</option>
                                <option value="card">{{ __('Card') }}</option>
                            </select>
                            <input type="number" min="0" x-model.number="split.amount" class="w-full rounded border-gray-300 text-sm">
                        </div>
                    </template>
                    <button type="button" @click="addSplitRow()" class="text-xs font-semibold text-bucha-primary">{{ __('Add split line') }}</button>
                </div>

                <button type="submit" :disabled="cart.length === 0" class="w-full rounded-bucha bg-bucha-primary px-4 py-3 text-sm font-semibold text-white hover:bg-bucha-burgundy disabled:opacity-50">
                    {{ __('Complete sale') }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function butcherPos(config) {
    return {
        products: config.products,
        outlets: config.outlets,
        customers: config.customers,
        outletId: config.selectedOutletId || (config.outlets[0]?.id ?? null),
        customerId: config.selectedCustomerId || '',
        search: '',
        cart: [],
        paymentMethod: 'cash',
        amountPaid: 0,
        discount: 0,
        splitPayments: [{ payment_method: 'cash', amount: 0 }, { payment_method: 'momo', amount: 0 }],
        storeUrl: config.storeUrl,
        filteredProducts() {
            const q = this.search.toLowerCase().trim();
            if (!q) return this.products;
            return this.products.filter(p => p.name.toLowerCase().includes(q));
        },
        formatMoney(amount) {
            return 'RWF ' + Math.round(Number(amount) || 0).toLocaleString();
        },
        addToCart(product) {
            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                if (product.unit === 'per_kg') existing.quantity_kg = Number((existing.quantity_kg + 0.5).toFixed(3));
                else existing.quantity_units = (existing.quantity_units || 0) + 1;
                this.updateLine(this.cart.indexOf(existing));
                return;
            }
            this.cart.push({
                product_id: product.id,
                name: product.name,
                unit: product.unit,
                price: product.price,
                quantity_kg: product.unit === 'per_kg' ? 0.5 : 0,
                quantity_units: product.unit === 'per_kg' ? null : 1,
                line_total: product.unit === 'per_kg' ? product.price * 0.5 : product.price,
            });
            this.amountPaid = this.total();
        },
        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.amountPaid = this.total();
        },
        updateLine(index) {
            const item = this.cart[index];
            item.line_total = item.unit === 'per_kg'
                ? item.price * (item.quantity_kg || 0)
                : item.price * (item.quantity_units || 0);
            this.amountPaid = this.total();
        },
        subtotal() {
            return this.cart.reduce((sum, i) => sum + Number(i.line_total || 0), 0);
        },
        total() {
            return Math.max(this.subtotal() - (Number(this.discount) || 0), 0);
        },
        addSplitRow() {
            this.splitPayments.push({ payment_method: 'momo', amount: 0 });
        },
        submitSale(event) {
            if (this.cart.length === 0) return;
            if (this.paymentMethod === 'credit' && !this.customerId) {
                alert(@json(__('Select a customer for credit sales.')));
                return;
            }
            event.target.submit();
        },
    };
}
</script>
        </div>
    </div>
</x-app-layout>
