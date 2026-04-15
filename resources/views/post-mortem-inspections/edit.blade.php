<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit post-mortem inspection') }} — {{ $inspection->batch->batch_code }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('post-mortem-inspections.update', $inspection) }}" class="space-y-6" id="post-mortem-edit-form">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" data-species="{{ $b['species'] }}" @selected(old('batch_id', $inspection->batch_id) == $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                            <option value="">{{ __('Select species') }}</option>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', $inspection->species ?? $inspection->batch->species) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select inspector') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id', $inspection->inspector_id) == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspection_date" :value="__('Inspection date')" />
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 block w-full" :value="old('inspection_date', $inspection->inspection_date?->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('inspection_date')" />
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Carcass inspection') }}</h3>
                        <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="carcass-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Organ inspection') }}</h3>
                        <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="organ-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                            </table>
                        </div>
                        <p id="checklist-empty" class="mt-2 text-xs text-slate-500 hidden">{{ __('No checklist configured for this species.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('observations')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="total_examined" :value="__('Total examined')" />
                            <x-text-input id="total_examined" name="total_examined" type="number" min="0" class="mt-1 block w-full" :value="old('total_examined', $inspection->total_examined)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
                        </div>
                        <div>
                            <x-input-label for="approved_quantity" :value="__('Approved quantity')" />
                            <x-text-input id="approved_quantity" name="approved_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('approved_quantity', $inspection->approved_quantity)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('approved_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="condemned_quantity" :value="__('Condemned quantity')" />
                            <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('condemned_quantity', $inspection->condemned_quantity)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Condemned cannot exceed Total Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes', $inspection->notes) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update inspection') }}</x-primary-button>
                        <a href="{{ route('post-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const batchSelect = document.getElementById('batch_id');
            const speciesSelect = document.getElementById('species');
            const inspectorSelect = document.getElementById('inspector_id');
            const carcassBody = document.getElementById('carcass-checklist-body');
            const organBody = document.getElementById('organ-checklist-body');
            const checklistEmpty = document.getElementById('checklist-empty');
            const checklists = @json($checklists);
            const aliases = @json(config('post_mortem_checklist.species_aliases'));
            const valueOptions = @json(config('post_mortem_checklist.value_options'));

            const storedObservations = @json($inspection->observations->mapWithKeys(fn($o) => [$o->item => ['value' => $o->value, 'notes' => $o->notes]])->toArray());
            const oldObservations = @json(old('observations', []));
            const observationState = Object.keys(oldObservations).length ? oldObservations : storedObservations;

            function speciesKey(speciesName) {
                if (!speciesName) return null;
                return aliases[String(speciesName).toLowerCase().trim()] || null;
            }

            function setSpeciesFromBatch() {
                if (!batchSelect || !speciesSelect) return;
                const selected = batchSelect.options[batchSelect.selectedIndex];
                if (!selected || !selected.dataset.species) return;
                if (!speciesSelect.value) {
                    speciesSelect.value = selected.dataset.species;
                }
            }

            function filterInspectors() {
                const selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                const currentOpt = inspectorSelect.options[inspectorSelect.selectedIndex];
                if (currentOpt && currentOpt.hidden) {
                    const visible = Array.from(inspectorSelect.options).find(o => o.value && !o.hidden);
                    inspectorSelect.value = visible ? visible.value : '';
                }
            }

            function renderChecklist() {
                if (!carcassBody || !organBody || !speciesSelect) return;
                const key = speciesKey(speciesSelect.value);
                const items = key ? (checklists[key] || {}) : {};
                const entries = Object.entries(items);

                carcassBody.innerHTML = '';
                organBody.innerHTML = '';

                if (entries.length === 0) {
                    checklistEmpty.classList.remove('hidden');
                    return;
                }

                checklistEmpty.classList.add('hidden');

                entries.forEach(([itemKey, meta]) => {
                    const options = valueOptions[meta.type] || [];
                    const selectedValue = observationState[itemKey]?.value || '';
                    const selectedNotes = observationState[itemKey]?.notes || '';
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${meta.label}</td>
                        <td class="px-3 py-2">
                            <select name="observations[${itemKey}][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select') }}</option>
                                ${options.map(v => `<option value="${v}" ${selectedValue === v ? 'selected' : ''}>${v.charAt(0).toUpperCase() + v.slice(1)}</option>`).join('')}
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" name="observations[${itemKey}][notes]" value="${String(selectedNotes).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" />
                        </td>
                    `;

                    if (meta.category === 'organ') {
                        organBody.appendChild(row);
                    } else {
                        carcassBody.appendChild(row);
                    }
                });
            }

            if (batchSelect) batchSelect.addEventListener('change', function() {
                setSpeciesFromBatch();
                filterInspectors();
                renderChecklist();
            });
            if (speciesSelect) speciesSelect.addEventListener('change', renderChecklist);
            document.addEventListener('DOMContentLoaded', function() {
                setSpeciesFromBatch();
                filterInspectors();
                renderChecklist();
            });
        })();
    </script>
</x-app-layout>
