<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Edit cold room storage') }}
                </h2>
            </div>
            <a href="{{ route('warehouse-storages.show', $warehouseStorage) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-bucha font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Back to storage') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                <form method="post" action="{{ route('warehouse-storages.update', $warehouseStorage) }}" class="bucha-wizard-form">
                    @csrf
                    @method('patch')

                    <x-wizard-section :title="__('Location')">
                        <x-wizard-field for="warehouse_facility_id" :label="__('Cold Room (storage facility)')" required>
                            <select id="warehouse_facility_id" name="warehouse_facility_id" class="bucha-wizard-select" required>
                                @foreach ($warehouseFacilities as $f)
                                    <option value="{{ $f['id'] }}" @selected(old('warehouse_facility_id', $warehouseStorage->warehouse_facility_id) == $f['id'])>{{ $f['label'] }}</option>
                                @endforeach
                            </select>
                        </x-wizard-field>
                        <x-input-error class="mt-2" :messages="$errors->get('warehouse_facility_id')" />

                        <x-wizard-field for="cold_room_id" :label="__('Physical cold room (optional)')">
                            <select id="cold_room_id" name="cold_room_id" class="bucha-wizard-select">
                                <option value="">{{ __('— Not linked —') }}</option>
                                @foreach ($coldRooms ?? [] as $cr)
                                    <option value="{{ $cr['id'] }}" @selected(old('cold_room_id', $warehouseStorage->cold_room_id) == $cr['id'])>{{ $cr['label'] }}</option>
                                @endforeach
                            </select>
                        </x-wizard-field>
                        <x-input-error class="mt-2" :messages="$errors->get('cold_room_id')" />
                    </x-wizard-section>

                    <x-wizard-section :title="__('Storage')">
                        <div class="bucha-wizard-grid">
                            <div>
                                <x-wizard-field for="temperature_at_entry" :label="__('Temperature at entry (°C)')">
                                    <input id="temperature_at_entry" name="temperature_at_entry" type="number" step="0.01" class="bucha-wizard-input" value="{{ old('temperature_at_entry', $warehouseStorage->temperature_at_entry) }}" />
                                </x-wizard-field>
                                <x-input-error class="mt-2" :messages="$errors->get('temperature_at_entry')" />
                            </div>
                            <div>
                                <x-wizard-field for="quantity_stored" :label="__('Quantity stored')" required>
                                    <input id="quantity_stored" name="quantity_stored" type="number" min="0" class="bucha-wizard-input" value="{{ old('quantity_stored', $warehouseStorage->quantity_stored) }}" required />
                                </x-wizard-field>
                                <x-input-error class="mt-2" :messages="$errors->get('quantity_stored')" />
                            </div>
                        </div>

                        <x-wizard-field for="quantity_unit" :label="__('Unit')" required>
                            <select id="quantity_unit" name="quantity_unit" class="bucha-wizard-select" required>
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
                        </x-wizard-field>
                        <x-input-error class="mt-2" :messages="$errors->get('quantity_unit')" />
                    </x-wizard-section>

                    <x-wizard-section :title="__('Status')">
                        <x-wizard-field for="status" :label="__('Status')" required>
                            <select id="status" name="status" class="bucha-wizard-select" required>
                                @foreach (['in_storage' => __('In storage'), 'released' => __('Released'), 'disposed' => __('Disposed')] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('status', $warehouseStorage->status) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-wizard-field>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />

                        <x-wizard-field for="released_date" :label="__('Released date')">
                            <input id="released_date" name="released_date" type="date" class="bucha-wizard-input" value="{{ old('released_date', $warehouseStorage->released_date?->format('Y-m-d')) }}" />
                        </x-wizard-field>
                        <x-input-error class="mt-2" :messages="$errors->get('released_date')" />
                    </x-wizard-section>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                        <a href="{{ route('warehouse-storages.show', $warehouseStorage) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const statusEl = document.getElementById('status');
                const releasedDateEl = document.getElementById('released_date');
                if (!statusEl || !releasedDateEl) return;

                const today = @json(now()->toDateString());

                function syncReleasedDate() {
                    if (statusEl.value === 'released' && !releasedDateEl.value) {
                        releasedDateEl.value = today;
                    }
                    if (statusEl.value !== 'released') {
                        releasedDateEl.value = '';
                    }
                }

                statusEl.addEventListener('change', syncReleasedDate);
                syncReleasedDate();
            });
        </script>
    @endpush
</x-app-layout>
