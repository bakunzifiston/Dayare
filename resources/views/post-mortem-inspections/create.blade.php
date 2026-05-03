<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record post-mortem inspection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('One post-mortem inspection per batch. Only batches without an inspection are listed.') }}</p>
                <form method="post" action="{{ route('post-mortem-inspections.store') }}" class="space-y-6" id="post-mortem-form">
                    @csrf

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" data-species="{{ $b['species'] }}" @selected(old('batch_id') == $b['id'])>{{ $b['label'] }}</option>
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
                                <option value="{{ $s }}" @selected(old('species') === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Species is auto-filled from batch, but can be reviewed before saving.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}">{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspection_date" :value="__('Inspection date')" />
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 block w-full" :value="old('inspection_date', date('Y-m-d'))" required />
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
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">{{ __('Decision & comment') }}</h3>
                        <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="decision-checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="total_examined" :value="__('Total examined')" />
                            <x-text-input id="total_examined" name="total_examined" type="number" min="0" class="mt-1 block w-full" :value="old('total_examined', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
                        </div>
                        <div>
                            <x-input-label for="approved_quantity" :value="__('Approved quantity')" />
                            <x-text-input id="approved_quantity" name="approved_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('approved_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('approved_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="condemned_quantity" :value="__('Condemned quantity')" />
                            <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('condemned_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Condemned cannot exceed Total Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save inspection') }}</x-primary-button>
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
            const decisionBody = document.getElementById('decision-checklist-body');
            const checklistEmpty = document.getElementById('checklist-empty');
            const checklists = @json($checklists);
            const aliases = @json(config('post_mortem_checklist.species_aliases'));
            const valueOptions = @json(config('post_mortem_checklist.value_options'));
            const oldBatchId = '{{ old('batch_id') }}';
            const oldInspectorId = '{{ old('inspector_id') }}';
            const oldObservations = @json(old('observations', []));

            function speciesKey(speciesName) {
                if (!speciesName) return null;
                return aliases[String(speciesName).toLowerCase().trim()] || null;
            }

            function setSpeciesFromBatch() {
                if (!batchSelect || !speciesSelect) return;
                const selected = batchSelect.options[batchSelect.selectedIndex];
                if (!selected || !selected.dataset.species) return;
                if (!speciesSelect.value || batchSelect.value !== oldBatchId) {
                    speciesSelect.value = selected.dataset.species;
                }
            }

            function filterInspectors() {
                const selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') {
                        opt.textContent = facilityId ? '{{ __('Select inspector') }}' : '{{ __('Select batch first') }}';
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                inspectorSelect.value = facilityId && oldBatchId === batchSelect.value ? oldInspectorId : '';
            }

            function renderChecklist() {
                if (!carcassBody || !organBody || !decisionBody || !speciesSelect) return;
                const key = speciesKey(speciesSelect.value);
                const items = key ? (checklists[key] || {}) : {};
                const entries = Object.entries(items);

                carcassBody.innerHTML = '';
                organBody.innerHTML = '';
                decisionBody.innerHTML = '';

                if (entries.length === 0) {
                    checklistEmpty.classList.remove('hidden');
                    return;
                }

                checklistEmpty.classList.add('hidden');

                entries.forEach(([itemKey, meta]) => {
                    const options = valueOptions[meta.type] || [];
                    const selectedValue = oldObservations[itemKey]?.value || '';
                    const selectedNotes = oldObservations[itemKey]?.notes || '';
                    const row = document.createElement('tr');
                    const valueField = meta.type === 'free_text'
                        ? `<input type="text" name="observations[${itemKey}][value]" value="${String(selectedValue).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required />`
                        : `<select name="observations[${itemKey}][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select') }}</option>
                                ${options.map(v => `<option value="${v}" ${selectedValue === v ? 'selected' : ''}>${v.charAt(0).toUpperCase() + v.slice(1)}</option>`).join('')}
                           </select>`;
                    row.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${meta.label}</td>
                        <td class="px-3 py-2">
                            ${valueField}
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" name="observations[${itemKey}][notes]" value="${String(selectedNotes).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" />
                        </td>
                    `;

                    if (meta.category === 'organ') {
                        organBody.appendChild(row);
                    } else if (meta.category === 'decision') {
                        decisionBody.appendChild(row);
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
