<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Livestock') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Add livestock') }}</h2>
    </x-slot>

    <div class="max-w-lg space-y-6">
        <form method="post" action="{{ route('farmer.farms.livestock.store', $farm) }}" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="type" :value="__('Animal type')" />
                <select name="type" id="type" required class="mt-1 block w-full rounded-lg border-gray-300">
                    @foreach ($types as $t)
                        <option value="{{ $t }}" @selected(old('type') === $t)>{{ \App\Support\FarmerAnimalType::label($t) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="breed" :value="__('Breed (optional)')" />
                <x-text-input id="breed" name="breed" type="text" class="mt-1 block w-full" :value="old('breed')" placeholder="{{ __('e.g. Angus') }}" />
                <x-input-error :messages="$errors->get('breed')" class="mt-2" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="total_quantity" :value="__('Total quantity')" />
                    <x-text-input id="total_quantity" name="total_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('total_quantity', 0)" required />
                    <x-input-error :messages="$errors->get('total_quantity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="available_quantity" :value="__('Available quantity')" />
                    <x-text-input id="available_quantity" name="available_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('available_quantity', 0)" required />
                    <x-input-error :messages="$errors->get('available_quantity')" class="mt-2" />
                </div>
            </div>

            <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 p-4 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Optional') }}</p>
                <div>
                    <x-input-label for="feeding_type" :value="__('Feeding')" />
                    <select name="feeding_type" id="feeding_type" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">{{ __('—') }}</option>
                        @foreach (\App\Models\Livestock::FEEDING_TYPES as $f)
                            <option value="{{ $f }}" @selected(old('feeding_type') === $f)>{{ ucfirst(str_replace('_', ' ', $f)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('feeding_type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="health_status" :value="__('Health')" />
                    <select name="health_status" id="health_status" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">{{ __('—') }}</option>
                        @foreach (\App\Models\Livestock::HEALTH_STATUSES as $h)
                            <option value="{{ $h }}" @selected(old('health_status') === $h)>{{ ucfirst($h) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('health_status')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="base_price" :value="__('Base price')" />
                    <x-text-input id="base_price" name="base_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('base_price')" />
                    <x-input-error :messages="$errors->get('base_price')" class="mt-2" />
                </div>
            </div>

            <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('Extended details') }}</h3>
                <p class="text-xs text-slate-500">{{ __('Age, weight, and notes — all optional.') }}</p>
                <div>
                    <x-input-label for="age_range" :value="__('Age range')" />
                    <x-text-input id="age_range" name="age_range" type="text" class="mt-1 block w-full" :value="old('age_range')" placeholder="{{ __('e.g. 12–18 mo') }}" />
                    <x-input-error :messages="$errors->get('age_range')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="weight_range" :value="__('Weight range')" />
                    <x-text-input id="weight_range" name="weight_range" type="text" class="mt-1 block w-full" :value="old('weight_range')" placeholder="{{ __('e.g. 400–500 kg') }}" />
                    <x-input-error :messages="$errors->get('weight_range')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="{{ __('Optional notes') }}">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Save') }}</button>
        </form>
    </div>
</x-app-layout>
