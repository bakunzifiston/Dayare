<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record ante-mortem inspection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('ante-mortem-inspections.store') }}" class="space-y-6" id="ante-mortem-form">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_plan_id" :value="__('Slaughter session')" />
                        <select id="slaughter_plan_id" name="slaughter_plan_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select slaughter session') }}</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p['id'] }}" data-facility-id="{{ $p['facility_id'] }}" @selected(old('slaughter_plan_id') == $p['id'])>{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Slaughter Session ID') }}: selected plan</p>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_plan_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select session first') }}</option>
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
                        <x-input-label for="species" :value="__('Species')" />
                        @php
                            $speciesOptions = \App\Models\Species::active()->pluck('name');
                        @endphp
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label :value="__('Species checklist')" />
                        <div class="mt-2 rounded-lg border border-slate-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Result') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes (optional)') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="checklist-body" class="divide-y divide-slate-100 bg-white"></tbody>
                            </table>
                        </div>
                        <p id="checklist-empty" class="mt-2 text-xs text-slate-500 hidden">{{ __('No checklist configured for this species.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('observations')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="number_examined" :value="__('Number examined')" />
                            <x-text-input id="number_examined" name="number_examined" type="number" min="0" class="mt-1 block w-full" :value="old('number_examined', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('number_examined')" />
                        </div>
                        <div>
                            <x-input-label for="number_approved" :value="__('Number approved')" />
                            <x-text-input id="number_approved" name="number_approved" type="number" min="0" class="mt-1 block w-full" :value="old('number_approved', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('number_approved')" />
                        </div>
                        <div>
                            <x-input-label for="number_rejected" :value="__('Number rejected')" />
                            <x-text-input id="number_rejected" name="number_rejected" type="number" min="0" class="mt-1 block w-full" :value="old('number_rejected', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('number_rejected')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Rejected cannot exceed Number Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save inspection') }}</x-primary-button>
                        <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const planSelect = document.getElementById('slaughter_plan_id');
            const inspectorSelect = document.getElementById('inspector_id');
            const speciesSelect = document.getElementById('species');
            const checklistBody = document.getElementById('checklist-body');
            const checklistEmpty = document.getElementById('checklist-empty');
            const checklists = @json($checklists);
            const aliases = @json(config('ante_mortem_checklist.species_aliases'));
            const valueOptions = @json(config('ante_mortem_checklist.value_options'));
            const oldPlanId = '{{ old('slaughter_plan_id') }}';
            const oldInspectorId = '{{ old('inspector_id') }}';
            const oldObservations = @json(old('observations', []));

            function speciesKey(speciesName) {
                if (!speciesName) return null;
                return aliases[String(speciesName).toLowerCase().trim()] || null;
            }

            function filterInspectors() {
                const selectedPlan = planSelect && planSelect.options[planSelect.selectedIndex];
                const facilityId = selectedPlan && selectedPlan.dataset.facilityId;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') {
                        opt.textContent = facilityId ? '{{ __('Select inspector') }}' : '{{ __('Select session first') }}';
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                inspectorSelect.value = facilityId && oldPlanId === planSelect.value ? oldInspectorId : '';
            }

            function renderChecklist() {
                if (!checklistBody || !speciesSelect) return;
                const key = speciesKey(speciesSelect.value);
                const items = key ? (checklists[key] || {}) : {};
                const entries = Object.entries(items);
                checklistBody.innerHTML = '';

                if (entries.length === 0) {
                    checklistEmpty.classList.remove('hidden');
                    return;
                }

                checklistEmpty.classList.add('hidden');

                entries.forEach(([itemKey, meta]) => {
                    const row = document.createElement('tr');
                    const options = valueOptions[meta.type] || [];
                    const oldValue = oldObservations[itemKey]?.value || '';
                    const oldNotes = oldObservations[itemKey]?.notes || '';

                    row.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${meta.label}</td>
                        <td class="px-3 py-2">
                            <select name="observations[${itemKey}][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select') }}</option>
                                ${options.map(v => `<option value="${v}" ${oldValue === v ? 'selected' : ''}>${v.charAt(0).toUpperCase() + v.slice(1)}</option>`).join('')}
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" name="observations[${itemKey}][notes]" value="${String(oldNotes).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" />
                        </td>
                    `;
                    checklistBody.appendChild(row);
                });
            }

            if (planSelect) planSelect.addEventListener('change', filterInspectors);
            if (speciesSelect) speciesSelect.addEventListener('change', renderChecklist);
            document.addEventListener('DOMContentLoaded', function() {
                filterInspectors();
                renderChecklist();
            });
        })();
    </script>
</x-app-layout>
