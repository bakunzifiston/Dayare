<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Record warehouse storage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <p class="text-sm text-slate-500 mb-4">{{ __('Only batches with an active certificate and not already in storage are listed. Cannot store without valid certificate.') }}</p>
                <form method="post" action="{{ route('warehouse-storages.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="warehouse_facility_id" :value="__('Warehouse (storage facility)')" />
                        <select id="warehouse_facility_id" name="warehouse_facility_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select warehouse') }}</option>
                            @foreach ($warehouseFacilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('warehouse_facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($warehouseFacilities->isEmpty())
                            <p class="mt-1 text-sm text-amber-600">{{ __('No facility with type "storage" found. Add a storage facility first.') }}</p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('warehouse_facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="certificate_id" :value="__('Certificate (batch)')" />
                        <select id="certificate_id" name="certificate_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select certificate') }}</option>
                            @foreach ($certificates as $c)
                                <option value="{{ $c['id'] }}" @selected(old('certificate_id') == $c['id'])>{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('certificate_id')" />
                    </div>

                    <div>
                        <x-input-label for="entry_date" :value="__('Entry date')" />
                        <x-text-input id="entry_date" name="entry_date" type="date" class="mt-1 block w-full" :value="old('entry_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('entry_date')" />
                    </div>

                    <div>
                        <x-input-label for="storage_location" :value="__('Storage location (room/freezer)')" />
                        <x-text-input id="storage_location" name="storage_location" type="text" class="mt-1 block w-full" :value="old('storage_location')" />
                        <x-input-error class="mt-2" :messages="$errors->get('storage_location')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="temperature_at_entry" :value="__('Temperature at entry (°C)')" />
                            <x-text-input id="temperature_at_entry" name="temperature_at_entry" type="number" step="0.01" class="mt-1 block w-full" :value="old('temperature_at_entry')" />
                            <x-input-error class="mt-2" :messages="$errors->get('temperature_at_entry')" />
                        </div>
                        <div>
                            <x-input-label for="quantity_stored" :value="__('Quantity stored')" />
                            <x-text-input id="quantity_stored" name="quantity_stored" type="number" min="1" class="mt-1 block w-full" :value="old('quantity_stored', 1)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('quantity_stored')" />
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Save') }}</button>
                        <a href="{{ route('warehouse-storages.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
