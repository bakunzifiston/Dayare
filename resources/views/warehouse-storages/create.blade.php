<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Record cold room storage') }}
            </h2>
        </div>
    </x-slot>

    @php
        $oldSelectedIds = collect(old('post_mortem_inspection_item_ids', []))->map(fn ($id) => (int) $id);
        $defaultUnit = old('quantity_unit', $units->first()?->code ?? 'kg');
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-sm text-slate-500 mb-4">{{ __('Select one or more animals approved at post-mortem. Each selected animal is stored as its own cold room record.') }}</p>
                <p class="text-sm text-slate-600 mb-4">
                    {{ __('For automated monitoring, configure rooms and standards from the Cold Room module, then pick a physical room below.') }}
                    <a href="{{ route('cold-rooms.hub') }}" class="text-bucha-primary font-medium hover:underline">{{ __('Open Cold Room') }}</a>
                </p>
                <input type="hidden" id="cold-rooms-by-facility"
                       value="{{ json_encode($coldRoomsByFacility ?? []) }}">

                <form method="post" action="{{ route('warehouse-storages.store') }}" id="warehouse-storage-form" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="warehouse_facility_id" :value="__('Cold Room (storage facility)')" />
                        <select id="warehouse_facility_id" name="warehouse_facility_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select cold room') }}</option>
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
                        <x-input-label for="cold_room_id" :value="__('Physical cold room')" />
                        <select id="cold_room_id" name="cold_room_id" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" data-selected="{{ old('cold_room_id') }}">
                            <option value="">{{ __('Select a cold room') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Required when the facility has registered cold rooms. Temperature logs on this storage feed automated violation tracking.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('cold_room_id')" />
                    </div>

                    <div>
                        <x-input-label for="entry_date" :value="__('Entry date')" />
                        <input id="entry_date" name="entry_date" type="date"
                               class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-bucha shadow-sm"
                               value="{{ old('entry_date', date('Y-m-d')) }}" required>
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
                            <x-input-label for="quantity_unit" :value="__('Unit')" />
                            <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full border-slate-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                @if (isset($units) && $units->isNotEmpty())
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->code }}" @selected($defaultUnit === $unit->code)>{{ $unit->name }}</option>
                                    @endforeach
                                @else
                                    @foreach (\App\Models\Demand::QUANTITY_UNITS as $value => $label)
                                        <option value="{{ $value }}" @selected($defaultUnit === $value)>{{ $label }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Leave kg blank per row to use the post-mortem carcass weight.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('quantity_unit')" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <x-input-label :value="__('Animal meat (post-mortem approved)')" class="mb-0" />
                            @if ($storableMeat->isNotEmpty())
                                <button type="button" id="toggle-all-animals" class="text-xs font-medium text-bucha-primary hover:text-bucha-burgundy">
                                    {{ __('Select all') }}
                                </button>
                            @endif
                        </div>

                        @if ($storableMeat->isEmpty())
                            <p class="text-sm text-amber-600">{{ __('No post-mortem approved meat is available. Complete post-mortem inspections first.') }}</p>
                        @else
                            <div class="rounded-lg border border-slate-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left w-10"></th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Ear tag') }}</th>
                                            <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Batch') }}</th>
                                            <th class="px-3 py-2 text-right font-medium text-slate-600">{{ __('Kg after PM') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($storableMeat as $item)
                                            <tr class="hover:bg-slate-50/80 animal-meat-row">
                                                <td class="px-3 py-2 align-middle">
                                                    <input type="checkbox"
                                                           name="post_mortem_inspection_item_ids[]"
                                                           value="{{ $item['id'] }}"
                                                           class="animal-meat-checkbox rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary"
                                                           data-meat-kg="{{ $item['meat_kg'] }}"
                                                           @checked($oldSelectedIds->contains($item['id']))>
                                                </td>
                                                <td class="px-3 py-2 text-slate-900">{{ $item['ear_tag'] }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $item['batch_code'] }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <input type="number"
                                                           name="quantities[{{ $item['id'] }}]"
                                                           step="0.01"
                                                           min="0.01"
                                                           placeholder="{{ number_format($item['meat_kg'], 2) }}"
                                                           value="{{ old('quantities.'.$item['id']) }}"
                                                           class="animal-qty-input w-24 rounded-md border-slate-300 text-right text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                                           @disabled(! $oldSelectedIds->contains($item['id']))>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p id="selected-summary" class="mt-2 text-xs text-slate-500"></p>
                            <p id="animal-selection-error" class="mt-2 text-sm text-red-600 hidden"></p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('post_mortem_inspection_item_ids')" />
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Save') }}</button>
                        <a href="{{ route('warehouse-storages.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        'use strict';
        document.addEventListener('DOMContentLoaded', function () {
            var form           = document.getElementById('warehouse-storage-form');
            var facilitySelect = document.getElementById('warehouse_facility_id');
            var roomSelect     = document.getElementById('cold_room_id');
            var toggleAllBtn   = document.getElementById('toggle-all-animals');
            var summaryEl      = document.getElementById('selected-summary');
            var selectionError = document.getElementById('animal-selection-error');
            var checkboxes     = document.querySelectorAll('.animal-meat-checkbox');
            var roomsMap;
            try {
                roomsMap = JSON.parse(document.getElementById('cold-rooms-by-facility')?.value || '{}');
            } catch (e) { roomsMap = {}; }

            var roomPlaceholder = @json(__('Select a cold room'));
            var selectAllLabel  = @json(__('Select all'));
            var clearAllLabel   = @json(__('Clear all'));
            var summaryTemplate = @json(__(':count selected · :kg kg total'));
            var selectAnimalMsg = @json(__('Select at least one animal.'));

            function rowInputs(checkbox) {
                var row = checkbox.closest('tr');
                return row ? row.querySelectorAll('input:not(.animal-meat-checkbox)') : [];
            }

            function syncRowEnabled(checkbox) {
                rowInputs(checkbox).forEach(function (input) {
                    input.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        input.value = '';
                    }
                });
            }

            function updateRooms(facilityId, selectedId) {
                if (!roomSelect) return;
                var rooms = roomsMap[facilityId] || [];
                roomSelect.innerHTML = '<option value="">' + roomPlaceholder + '</option>';
                rooms.forEach(function (r) {
                    var opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    if (selectedId && String(r.id) === String(selectedId)) {
                        opt.selected = true;
                    }
                    roomSelect.appendChild(opt);
                });
            }

            function selectedMeatKg() {
                var total = 0;
                checkboxes.forEach(function (cb) {
                    if (!cb.checked) return;
                    var row = cb.closest('tr');
                    var qtyInput = row ? row.querySelector('.animal-qty-input') : null;
                    var kg = qtyInput && qtyInput.value ? parseFloat(qtyInput.value) : parseFloat(cb.getAttribute('data-meat-kg') || '0');
                    if (!isNaN(kg)) total += kg;
                });
                return total;
            }

            function updateSummary() {
                if (!summaryEl) return;
                var count = 0;
                checkboxes.forEach(function (cb) { if (cb.checked) count++; });
                if (count === 0) {
                    summaryEl.textContent = '';
                    return;
                }
                summaryEl.textContent = summaryTemplate
                    .replace(':count', String(count))
                    .replace(':kg', selectedMeatKg().toFixed(2));
            }

            checkboxes.forEach(function (cb) {
                syncRowEnabled(cb);
                cb.addEventListener('change', function () {
                    syncRowEnabled(cb);
                    updateSummary();
                    if (selectionError) {
                        selectionError.classList.add('hidden');
                    }
                });
                var row = cb.closest('tr');
                var qtyInput = row ? row.querySelector('.animal-qty-input') : null;
                if (qtyInput) {
                    qtyInput.addEventListener('input', updateSummary);
                }
            });

            if (toggleAllBtn) {
                toggleAllBtn.addEventListener('click', function () {
                    var allChecked = Array.prototype.every.call(checkboxes, function (cb) { return cb.checked; });
                    checkboxes.forEach(function (cb) {
                        cb.checked = !allChecked;
                        syncRowEnabled(cb);
                    });
                    toggleAllBtn.textContent = allChecked ? selectAllLabel : clearAllLabel;
                    updateSummary();
                });
                var allChecked = checkboxes.length > 0 && Array.prototype.every.call(checkboxes, function (cb) { return cb.checked; });
                toggleAllBtn.textContent = allChecked ? clearAllLabel : selectAllLabel;
            }

            updateSummary();

            if (facilitySelect) {
                var selectedRoom = roomSelect ? roomSelect.getAttribute('data-selected') : '';
                facilitySelect.addEventListener('change', function () {
                    updateRooms(this.value, '');
                });
                if (facilitySelect.value) {
                    updateRooms(facilitySelect.value, selectedRoom);
                }
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    var selectedCount = 0;
                    checkboxes.forEach(function (cb) {
                        syncRowEnabled(cb);
                        if (cb.checked) selectedCount++;
                    });

                    if (checkboxes.length > 0 && selectedCount === 0) {
                        event.preventDefault();
                        if (selectionError) {
                            selectionError.textContent = selectAnimalMsg;
                            selectionError.classList.remove('hidden');
                        }
                    }
                });
            }
        });
    }());
    </script>
    @endpush
</x-app-layout>
