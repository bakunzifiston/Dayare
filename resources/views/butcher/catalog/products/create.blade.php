<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.catalog.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Catalog') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('New product') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.catalog.products.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Product name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="cut_type_id" :value="__('Cut type')" />
                    <select id="cut_type_id" name="cut_type_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($cutTypes as $cutType)
                            <option value="{{ $cutType->id }}" @selected(old('cut_type_id') == $cutType->id)>{{ $cutType->name }} ({{ ucfirst($cutType->meat_type) }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('cut_type_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="meat_type" :value="__('Meat type')" />
                        <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($meatTypes as $type)
                                <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="unit" :value="__('Unit of measure')" />
                        <select id="unit" name="unit" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected(old('unit', 'per_kg') === $unit)>{{ str_replace('_', ' ', $unit) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="default_price" :value="__('Default / fallback price')" />
                    <x-text-input id="default_price" name="default_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('default_price')" required />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Used when no matching tier rule applies. Set a retail price rule before activating for POS.') }}</p>
                    <x-input-error :messages="$errors->get('default_price')" class="mt-2" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active'))>
                    {{ __('Activate for POS (requires a retail price rule)') }}
                </label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create product') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
