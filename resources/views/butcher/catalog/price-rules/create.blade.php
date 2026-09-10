@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
    $previewPrice = old('price');
    $previewMargin = $previewPrice !== null && $previewPrice !== '' ? ((float) $previewPrice - $avgCost) : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.catalog.products.show', $product) }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← :name', ['name' => $product->name]) }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Add price rule') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.catalog.price-rules.store', $product) }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <p>{{ __('Average cost') }}: <span class="font-semibold">{{ $fmtMoney($avgCost) }}</span> <span class="text-xs text-slate-500">({{ __('read-only') }})</span></p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="customer_tier" :value="__('Customer tier')" />
                        <select id="customer_tier" name="customer_tier" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('All tiers') }}</option>
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier }}" @selected(old('customer_tier', 'retail') === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="outlet_id" :value="__('Outlet (optional)')" />
                        <select id="outlet_id" name="outlet_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('All outlets') }}</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="price" :value="__('Price')" />
                    <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price')" required />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Margin vs avg cost') }}: <span id="margin-preview">{{ $previewMargin === null ? '—' : $fmtMoney($previewMargin) }}</span></p>
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="valid_from" :value="__('Valid from')" />
                        <x-text-input id="valid_from" name="valid_from" type="date" class="mt-1 block w-full" :value="old('valid_from', now()->toDateString())" required />
                    </div>
                    <div>
                        <x-input-label for="valid_until" :value="__('Valid until (optional)')" />
                        <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full" :value="old('valid_until')" />
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', true))>
                    {{ __('Active') }}
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save price rule') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const price = document.getElementById('price');
            const preview = document.getElementById('margin-preview');
            const avgCost = {{ json_encode((float) $avgCost) }};
            price?.addEventListener('input', () => {
                const v = parseFloat(price.value);
                if (!Number.isFinite(v)) { preview.textContent = '—'; return; }
                preview.textContent = 'RWF ' + Math.round(v - avgCost).toLocaleString();
            });
        })();
    </script>
</x-app-layout>
