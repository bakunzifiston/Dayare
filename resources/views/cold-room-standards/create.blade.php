<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">{{ __('New temperature standard') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200/60 p-6">
                <form method="post" action="{{ route('cold-room-standards.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="chiller" @selected(old('type') === 'chiller')>{{ __('Chiller') }}</option>
                            <option value="freezer" @selected(old('type') === 'freezer')>{{ __('Freezer') }}</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="min_temperature" :value="__('Min temperature (°C)')" />
                            <x-text-input id="min_temperature" name="min_temperature" type="number" step="0.01" class="mt-1 block w-full" :value="old('min_temperature')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('min_temperature')" />
                        </div>
                        <div>
                            <x-input-label for="max_temperature" :value="__('Max temperature (°C)')" />
                            <x-text-input id="max_temperature" name="max_temperature" type="number" step="0.01" class="mt-1 block w-full" :value="old('max_temperature')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('max_temperature')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="tolerance_minutes" :value="__('Tolerance (minutes)')" />
                        <x-text-input id="tolerance_minutes" name="tolerance_minutes" type="number" min="0" class="mt-1 block w-full" :value="old('tolerance_minutes', 30)" required />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Time out of range before batches move to at risk (then compromised after 2× this value).') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('tolerance_minutes')" />
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Save') }}</button>
                        <a href="{{ route('cold-room-standards.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
