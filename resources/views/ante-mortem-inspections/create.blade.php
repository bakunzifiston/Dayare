<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record ante-mortem inspection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('ante-mortem-inspections.store') }}" class="space-y-6" id="ante-mortem-form" novalidate>
                    @csrf

                    <div>
                        <x-input-label for="slaughter_plan_id" :value="__('Slaughter session')" />
                        <select id="slaughter_plan_id" name="slaughter_plan_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select slaughter session') }}</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p['id'] }}"
                                    data-facility-id="{{ $p['facility_id'] }}"
                                    @selected(old('slaughter_plan_id', $selectedPlan?->id ?? '') == $p['id'])>{{ $p['label'] }}</option>
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
                            $speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect();
                        @endphp
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', $selectedPlan?->species ?? '') === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div id="under-observation-banner" style="display:none;"
                         class="rounded-md bg-yellow-50 border border-yellow-200 p-3 mb-4">
                        <p class="text-sm text-yellow-800">
                            <span id="under-observation-count"></span>
                            {{ __('animal(s) flagged as under observation are assigned to this plan — review each individually below.') }}
                        </p>
                    </div>

                    <div id="assignment-gap-alert" class="hidden rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <p id="assignment-gap-alert-text"></p>
                    </div>

                    <div id="per-animal-outcomes-section"
                         class="rounded-lg border border-slate-200 bg-white @if (! isset($assignedItems) || $assignedItems->isEmpty()) hidden @endif">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Individual animal inspection') }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Record an outcome and complete the inspection checklist for each assigned animal.') }}</p>
                        </div>
                        <div id="per-animal-outcomes-container" class="p-4">
                            @if (isset($assignedItems) && $assignedItems->isNotEmpty())
                                @include('ante-mortem-inspections.partials._per-animal-outcomes', [
                                    'assignedItems' => $assignedItems,
                                    'inspectionItems' => collect(),
                                    'species' => old('species', $selectedPlan?->species ?? ''),
                                ])
                            @else
                                <p class="text-sm text-gray-500">
                                    {{ __('Select a slaughter session to load assigned animals.') }}
                                </p>
                            @endif
                        </div>
                        <x-input-error class="px-4 pb-3" :messages="$errors->get('item_outcomes')" />
                    </div>

                    <div id="inspection-checklist-section" class="rounded-lg border border-slate-200 bg-white @if (isset($assignedItems) && $assignedItems->isNotEmpty()) hidden @endif">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Inspection checklist') }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Complete this checklist for legacy sessions without individually assigned animals.') }}</p>
                        </div>
                        <div class="p-4">
                            <div class="overflow-hidden rounded-lg border border-slate-200">
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
                    </div>

                    <div id="aggregate-counts-section">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <x-input-label for="number_examined" :value="__('Number examined')" />
                                <x-text-input id="number_examined" name="number_examined" type="number" min="0" class="mt-1 block w-full" :value="old('number_examined', $selectedPlan?->assigned_count ?? 0)" required />
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
                        <p id="aggregate-counts-hint" class="text-xs text-gray-500 -mt-2">{{ __('Approved + Rejected cannot exceed Number Examined.') }}</p>
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div id="notes-for-under-observation-field" style="display:none;">
                        <label for="notes_for_under_observation" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Notes for under-observation animals') }}
                        </label>
                        <textarea id="notes_for_under_observation" name="notes_for_under_observation" rows="3" maxlength="2000"
                                  class="w-full rounded border-gray-300 text-sm"
                        >{{ old('notes_for_under_observation', '') }}</textarea>
                        @error('notes_for_under_observation')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
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

            window.anteMortemExcludeDecision = false;

            window.renderAnteMortemChecklist = function renderChecklist() {
                if (!checklistBody || !speciesSelect) return;

                const checklistSection = document.getElementById('inspection-checklist-section');
                if (checklistSection && checklistSection.classList.contains('hidden')) {
                    checklistBody.innerHTML = '';
                    if (checklistEmpty) checklistEmpty.classList.add('hidden');
                    return;
                }

                const key = speciesKey(speciesSelect.value);
                const items = key ? (checklists[key] || {}) : {};
                const entries = Object.entries(items).filter(([itemKey]) => {
                    return !(window.anteMortemExcludeDecision && itemKey === 'decision');
                });
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
                    const valueField = meta.type === 'free_text'
                        ? `<input type="text" name="observations[${itemKey}][value]" value="${String(oldValue).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required />`
                        : `<select name="observations[${itemKey}][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                                <option value="">{{ __('Select') }}</option>
                                ${options.map(v => `<option value="${v}" ${oldValue === v ? 'selected' : ''}>${v.charAt(0).toUpperCase() + v.slice(1)}</option>`).join('')}
                           </select>`;

                    row.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${meta.label}</td>
                        <td class="px-3 py-2">
                            ${valueField}
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" name="observations[${itemKey}][notes]" value="${String(oldNotes).replace(/"/g, '&quot;')}" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" />
                        </td>
                    `;
                    checklistBody.appendChild(row);
                });
            }

            if (planSelect) planSelect.addEventListener('change', filterInspectors);
            if (speciesSelect) speciesSelect.addEventListener('change', window.renderAnteMortemChecklist);
            document.addEventListener('DOMContentLoaded', function() {
                filterInspectors();
                window.renderAnteMortemChecklist();
            });
        })();
    </script>

    @include('ante-mortem-inspections.partials.form-plan-scripts', [
        'checklists' => $checklists,
        'existingInspectionOutcomes' => [],
        'preserveExistingOutcomes' => false,
    ])
</x-app-layout>
