<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Edit warehouse storage') }}
            </h2>
            <a href="{{ route('warehouse-storages.show', $warehouseStorage) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Back to storage') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <form method="post" action="{{ route('warehouse-storages.update', $warehouseStorage) }}" class="space-y-6">
                    @csrf
                    @method('patch')

                    <div>
                        <x-input-label for="warehouse_facility_id" :value="__('Warehouse (storage facility)')" />
                        <select id="warehouse_facility_id" name="warehouse_facility_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($warehouseFacilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('warehouse_facility_id', $warehouseStorage->warehouse_facility_id) == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('warehouse_facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="storage_location" :value="__('Storage location (room/freezer)')" />
                        <x-text-input id="storage_location" name="storage_location" type="text" class="mt-1 block w-full" :value="old('storage_location', $warehouseStorage->storage_location)" />
                        <x-input-error class="mt-2" :messages="$errors->get('storage_location')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="temperature_at_entry" :value="__('Temperature at entry (°C)')" />
                            <x-text-input id="temperature_at_entry" name="temperature_at_entry" type="number" step="0.01" class="mt-1 block w-full" :value="old('temperature_at_entry', $warehouseStorage->temperature_at_entry)" />
                            <x-input-error class="mt-2" :messages="$errors->get('temperature_at_entry')" />
                        </div>
                        <div>
                            <x-input-label for="quantity_stored" :value="__('Quantity stored')" />
                            <x-text-input id="quantity_stored" name="quantity_stored" type="number" min="0" class="mt-1 block w-full" :value="old('quantity_stored', $warehouseStorage->quantity_stored)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('quantity_stored')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="quantity_unit" :value="__('Unit')" />
                        <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @if (isset($units) && $units->isNotEmpty())
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->code }}" @selected(old('quantity_unit', $warehouseStorage->quantity_unit ?? 'kg') === $unit->code)>{{ $unit->name }}</option>
                                @endforeach
                            @else
                                @foreach (\App\Models\Demand::QUANTITY_UNITS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('quantity_unit', $warehouseStorage->quantity_unit ?? 'kg') === $value)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('quantity_unit')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach (['in_storage' => __('In storage'), 'released' => __('Released'), 'disposed' => __('Disposed')] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $warehouseStorage->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <x-input-label for="released_date" :value="__('Released date')" />
                        <x-text-input id="released_date" name="released_date" type="date" class="mt-1 block w-full" :value="old('released_date', $warehouseStorage->released_date?->format('Y-m-d'))" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Required when status is Released. Transport can only start when storage is released.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('released_date')" />
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Update') }}
                        </button>
                        <a href="{{ route('warehouse-storages.show', $warehouseStorage) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
