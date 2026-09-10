@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.catalog.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Catalog') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit product') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.catalog.products.update', $product) }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" :value="__('Product name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="cut_type_id" :value="__('Cut type')" />
                    <select id="cut_type_id" name="cut_type_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($cutTypes as $cutType)
                            <option value="{{ $cutType->id }}" @selected((string) old('cut_type_id', $product->cut_type_id) === (string) $cutType->id)>{{ $cutType->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="meat_type" :value="__('Meat type')" />
                        <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($meatTypes as $type)
                                <option value="{{ $type }}" @selected(old('meat_type', $product->meat_type) === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="unit" :value="__('Unit of measure')" />
                        <select id="unit" name="unit" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ str_replace('_', ' ', $unit) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="default_price" :value="__('Default / fallback price')" />
                    <x-text-input id="default_price" name="default_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('default_price', $product->default_price)" required />
                </div>

                <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <p>{{ __('Average cost (read-only)') }}: <span class="font-semibold">{{ $fmtMoney($product->avg_cost_per_kg) }}</span></p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Updated automatically when cutting sessions close for the linked cut type.') }}</p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $product->is_active)) @disabled(! $hasRetailRule && ! $product->is_active)>
                    {{ __('Active for POS') }}
                </label>
                @unless ($hasRetailRule)
                    <p class="text-xs text-amber-700">{{ __('Add an active retail price rule before activating.') }}</p>
                @endunless
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save product') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
