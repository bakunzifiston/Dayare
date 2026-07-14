@php
    use App\Support\PostMortemChecklist;
@endphp

@push('scripts')
<script>
(function () {
    'use strict';

    window.postMortemExecutionAnimals = @json($executionAnimalsByExecutionId ?? []);
    window.postMortemBatchAnimals = @json($batchAnimalsByBatchId ?? []);
    window.postMortemChecklists = @json($checklists ?? PostMortemChecklist::all());
    window.postMortemSpeciesAliases = @json(config('post_mortem_checklist.species_aliases'));
    window.postMortemValueOptions = @json(config('post_mortem_checklist.value_options'));

    var incrementalAnimalSelection = @json($incrementalAnimalSelection ?? false);
    var existingInspectionOutcomes = @json($existingInspectionOutcomes ?? []);
    var preserveExistingOutcomes = @json($preserveExistingOutcomes ?? false);
    var selectedAnimals = @json($selectedAnimals ?? []);
    var currentExecutionData = null;

    function existingOutcomeForAnimal(animalId) {
        return existingInspectionOutcomes[animalId]
            || existingInspectionOutcomes[String(animalId)]
            || {};
    }
    var oldObservations = @json(old('observations', $legacyObservations ?? []));

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeTag(value) {
        return String(value || '').trim().toLowerCase();
    }

    function animalSearchTags(animal) {
        return [
            normalizeTag(animal.ear_tag),
            normalizeTag(animal.tag_number),
        ].filter(function (tag) {
            return tag !== '';
        });
    }

    function speciesChecklistKey(speciesName) {
        if (!speciesName) return 'all_species';
        return window.postMortemSpeciesAliases[String(speciesName).toLowerCase().trim()] || 'all_species';
    }

    function checklistItemsForSpecies(speciesName) {
        var key = speciesChecklistKey(speciesName);
        var items = window.postMortemChecklists[key] || window.postMortemChecklists.all_species || {};
        return Object.entries(items).filter(function (entry) {
            return entry[0] !== 'decision' && entry[0] !== 'comment';
        });
    }

    function organOptionsForSpecies(speciesName) {
        var key = speciesChecklistKey(speciesName);
        var items = window.postMortemChecklists[key] || window.postMortemChecklists.all_species || {};
        var organs = Object.entries(items)
            .filter(function (entry) {
                return (entry[1].category || '') === 'organ';
            })
            .map(function (entry) {
                return entry[1].label;
            });

        organs.push(@json(__('Whole carcass')));

        return organs;
    }

    function buildOrganSelectField(name, selectedValue, className) {
        var options = organOptionsForSpecies(currentSpeciesName()).map(function (organ) {
            return '<option value="' + escapeHtml(organ) + '"' + (selectedValue === organ ? ' selected' : '') + '>' + escapeHtml(organ) + '</option>';
        }).join('');

        if (selectedValue && organOptionsForSpecies(currentSpeciesName()).indexOf(selectedValue) === -1) {
            options += '<option value="' + escapeHtml(selectedValue) + '" selected>' + escapeHtml(selectedValue) + '</option>';
        }

        return '<select name="' + name + '" class="' + className + ' block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">'
            + '<option value="">' + @json(__('Select organ')) + '</option>'
            + options
            + '</select>';
    }

    function currentSpeciesName() {
        var speciesEl = document.getElementById('species');
        return speciesEl ? speciesEl.value : '';
    }

    function formatKgDisplay(value) {
        var amount = Number(value);
        if (!Number.isFinite(amount)) return '0.00';
        return amount.toFixed(2);
    }

    function syncAggregateCounts() {
        var container = document.getElementById('per-animal-outcomes-container');
        var examinedInput = document.getElementById('total_examined');
        var approvedInput = document.getElementById('approved_quantity');
        var condemnedQuantityInput = document.getElementById('condemned_quantity');
        var carcassField = document.getElementById('approved_carcass_kg');
        var otherMeatField = document.getElementById('approved_other_meat_kg');
        if (!container || !examinedInput || !approvedInput || !condemnedQuantityInput) return;

        var examinedKg = 0;
        var carcassApprovedKg = 0;
        var otherApprovedKg = 0;
        var condemnedKg = 0;

        container.querySelectorAll('[data-pm-animal-card]').forEach(function (card) {
            var beforeKg = parseFloat(card.dataset.meatKg || '0');
            var select = card.querySelector('.pm-animal-outcome');
            var carcassInput = card.querySelector('.pm-carcass-weight');
            var condemnedWeightInput = card.querySelector('.pm-condemned-weight');
            var outcome = select ? select.value : '';
            var afterKg = carcassInput ? parseFloat(carcassInput.value) : NaN;
            var condemnedPartKg = condemnedWeightInput ? parseFloat(condemnedWeightInput.value) : NaN;

            examinedKg += Number.isFinite(beforeKg) ? beforeKg : 0;

            if (outcome === 'approved') {
                var carcassPart = Number.isFinite(afterKg) && afterKg > 0 ? afterKg : beforeKg;
                carcassApprovedKg += Number.isFinite(carcassPart) ? carcassPart : 0;
                if (Number.isFinite(afterKg) && afterKg > 0 && beforeKg > afterKg) {
                    otherApprovedKg += beforeKg - afterKg;
                }
            } else if (outcome === 'condemned') {
                condemnedKg += Number.isFinite(condemnedPartKg) && condemnedPartKg > 0
                    ? condemnedPartKg
                    : (Number.isFinite(beforeKg) ? beforeKg : 0);
            }
        });

        var approvedKg = carcassApprovedKg + otherApprovedKg;

        examinedInput.value = formatKgDisplay(examinedKg);
        approvedInput.value = formatKgDisplay(approvedKg);
        condemnedQuantityInput.value = formatKgDisplay(condemnedKg);
        if (carcassField) carcassField.value = formatKgDisplay(carcassApprovedKg);
        if (otherMeatField) otherMeatField.value = formatKgDisplay(otherApprovedKg);

        var examinedDisplay = document.getElementById('pm-summary-examined');
        var carcassDisplay = document.getElementById('pm-summary-carcass');
        var otherMeatDisplay = document.getElementById('pm-summary-other-meat');
        var condemnedDisplay = document.getElementById('pm-summary-condemned');
        if (examinedDisplay) examinedDisplay.textContent = formatKgDisplay(examinedKg);
        if (carcassDisplay) carcassDisplay.textContent = formatKgDisplay(carcassApprovedKg);
        if (otherMeatDisplay) otherMeatDisplay.textContent = formatKgDisplay(otherApprovedKg);
        if (condemnedDisplay) condemnedDisplay.textContent = formatKgDisplay(condemnedKg);
    }

    function syncLegacyApprovedTotal() {
        var carcassField = document.getElementById('approved_carcass_kg');
        var otherMeatField = document.getElementById('approved_other_meat_kg');
        var approvedInput = document.getElementById('approved_quantity');
        if (!approvedInput) {
            return;
        }

        var carcassKg = carcassField ? parseFloat(carcassField.value) : 0;
        var otherKg = otherMeatField ? parseFloat(otherMeatField.value) : 0;
        var total = (Number.isFinite(carcassKg) ? carcassKg : 0) + (Number.isFinite(otherKg) ? otherKg : 0);
        approvedInput.value = formatKgDisplay(total);
    }

    function toggleCondemnationFields(card) {
        if (!card) {
            return;
        }

        var select = card.querySelector('.pm-animal-outcome');
        var approvedWeightField = card.querySelector('.pm-approved-weight-field');
        if (!select) {
            return;
        }

        var isCondemned = select.value === 'condemned';

        card.querySelectorAll('[data-pm-condemnation-row], .pm-condemnation-row').forEach(function (row) {
            row.classList.toggle('hidden', !isCondemned);
            row.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !isCondemned;
            });
        });

        if (approvedWeightField) {
            approvedWeightField.classList.toggle('hidden', isCondemned);
        }
    }

    function bindPerAnimalAggregateListeners(container) {
        if (!container) return;
        container.querySelectorAll('[data-pm-animal-card]').forEach(function (card) {
            toggleCondemnationFields(card);
        });
        container.querySelectorAll('.pm-carcass-weight').forEach(function (input) {
            if (input.dataset.bound === '1') return;
            input.dataset.bound = '1';
            input.addEventListener('input', syncAggregateCounts);
        });
        container.querySelectorAll('.pm-condemned-weight').forEach(function (input) {
            if (input.dataset.bound === '1') return;
            input.dataset.bound = '1';
            input.addEventListener('input', syncAggregateCounts);
        });
    }

    function toggleAggregateCountsSection(hasSelectedAnimals) {
        var section = document.getElementById('aggregate-counts-section');
        var summary = document.getElementById('per-animal-aggregate-summary');
        var perAnimalSection = document.getElementById('per-animal-outcomes-section');
        var perAnimalActive = perAnimalSection && !perAnimalSection.classList.contains('hidden');

        if (section) {
            section.classList.toggle('hidden', perAnimalActive || !!hasSelectedAnimals);
        }
        if (summary) {
            summary.classList.toggle('hidden', !hasSelectedAnimals);
        }
        if (hasSelectedAnimals || perAnimalActive) {
            syncAggregateCounts();
        }
    }

    function buildChecklistRows(index, observations, speciesName) {
        observations = observations || {};
        return checklistItemsForSpecies(speciesName).map(function (entry) {
            var itemKey = entry[0];
            var meta = entry[1];
            var options = window.postMortemValueOptions[meta.type] || [];
            var obsValue = observations[itemKey] ? (observations[itemKey].value || '') : '';
            var obsNotes = observations[itemKey] ? (observations[itemKey].notes || '') : '';
            var valueField = meta.type === 'free_text'
                ? '<input type="text" name="item_outcomes[' + index + '][observations][' + itemKey + '][value]" value="' + escapeHtml(obsValue) + '" class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required />'
                : '<select name="item_outcomes[' + index + '][observations][' + itemKey + '][value]" class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required>'
                    + '<option value="">' + @json(__('Select')) + '</option>'
                    + options.map(function (option) {
                        return '<option value="' + option + '"' + (obsValue === option ? ' selected' : '') + '>'
                            + option.charAt(0).toUpperCase() + option.slice(1) + '</option>';
                    }).join('')
                    + '</select>';

            return '<tr><td class="px-3 py-2 text-slate-700">' + escapeHtml(meta.label) + '</td>'
                + '<td class="px-3 py-2">' + valueField + '</td>'
                + '<td class="px-3 py-2"><input type="text" name="item_outcomes[' + index + '][observations][' + itemKey + '][notes]" value="' + escapeHtml(obsNotes) + '" maxlength="5000" class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" /></td></tr>';
        }).join('');
    }

    function formatMeatKg(value) {
        var amount = Number(value);
        if (!Number.isFinite(amount)) return '—';
        return amount.toFixed(2);
    }

    function buildDecisionChecklistRows(index, existing, speciesName) {
        var currentOutcome = existing.outcome || '';
        var seizedPart = existing.seized_part || '';
        var reason = existing.reason || '';
        var condemnedWeight = existing.condemned_weight_kg || '';
        var isCondemned = currentOutcome === 'condemned';
        var outcomeOptions = '<option value="">' + @json(__('Select decision')) + '</option>'
            + ['approved', 'condemned', 'deferred'].map(function (outcome) {
                var selected = currentOutcome === outcome ? ' selected' : '';
                return '<option value="' + outcome + '"' + selected + '>' + outcome.charAt(0).toUpperCase() + outcome.slice(1) + '</option>';
            }).join('');
        var hiddenClass = isCondemned ? '' : ' hidden';

        return '<tr class="bg-slate-50/80"><td class="px-3 py-2 font-medium text-slate-800">' + @json(__('Decision')) + '</td>'
            + '<td class="px-3 py-2" colspan="2"><select name="item_outcomes[' + index + '][outcome]" class="pm-animal-outcome block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required>'
            + outcomeOptions + '</select></td></tr>'
            + '<tr class="pm-condemnation-row bg-amber-50/70' + hiddenClass + '" data-pm-condemnation-row><td class="px-3 py-2 font-medium text-amber-900">' + @json(__('Condemned organ')) + '</td>'
            + '<td class="px-3 py-2" colspan="2">' + buildOrganSelectField('item_outcomes[' + index + '][seized_part]', seizedPart, 'pm-condemned-organ') + '</td></tr>'
            + '<tr class="pm-condemnation-row bg-amber-50/70' + hiddenClass + '" data-pm-condemnation-row><td class="px-3 py-2 font-medium text-amber-900">' + @json(__('Condemned weight (kg)')) + '</td>'
            + '<td class="px-3 py-2" colspan="2"><input type="number" name="item_outcomes[' + index + '][condemned_weight_kg]" value="' + escapeHtml(condemnedWeight) + '" min="0.1" max="9999" step="0.01" placeholder="kg" class="pm-condemned-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"></td></tr>'
            + '<tr class="pm-condemnation-row bg-amber-50/70' + hiddenClass + '" data-pm-condemnation-row><td class="px-3 py-2 font-medium text-amber-900">' + @json(__('Reason for condemnation')) + '</td>'
            + '<td class="px-3 py-2" colspan="2"><input type="text" name="item_outcomes[' + index + '][reason]" value="' + escapeHtml(reason) + '" placeholder=' + @json(__('e.g. Cysts, abscess')) + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"></td></tr>';
    }

    function buildAnimalCard(animal, index, existing, speciesName) {
        var legacy = String(animal.ear_tag || '').startsWith('LEGACY-')
            ? '<span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>' : '';
        var tagLine = animal.tag_number && normalizeTag(animal.tag_number) !== normalizeTag(animal.ear_tag)
            ? ' · ' + @json(__('Tag')) + ': ' + escapeHtml(animal.tag_number)
            : '';
        var currentOutcome = existing.outcome || '';
        var meatKgLabel = formatMeatKg(animal.meat_quantity_kg);
        var batchItemField = animal.batch_item_id
            ? '<input type="hidden" name="item_outcomes[' + index + '][batch_item_id]" value="' + animal.batch_item_id + '">'
            : '';

        return '<div class="overflow-hidden rounded-lg border border-slate-200" data-pm-animal-card data-animal-id="' + animal.animal_intake_item_id + '" data-meat-kg="' + escapeHtml(animal.meat_quantity_kg || '0') + '">'
            + '<div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">'
            + '<div class="min-w-0 flex-1"><p class="font-mono text-sm font-medium text-slate-900">' + escapeHtml(animal.ear_tag) + legacy + tagLine + '</p>'
            + '<p class="mt-0.5 text-xs text-slate-500">' + escapeHtml(animal.species) + ' · ' + escapeHtml(animal.sex)
            + ' · ' + escapeHtml(animal.session_label)
            + ' · <span class="font-medium text-slate-700">' + meatKgLabel + ' kg ' + @json(__('before PM')) + '</span></p></div>'
            + batchItemField
            + '<input type="hidden" name="item_outcomes[' + index + '][animal_intake_item_id]" value="' + animal.animal_intake_item_id + '">'
            + '<div class="w-full sm:w-28 pm-approved-weight-field' + (currentOutcome === 'condemned' ? ' hidden' : '') + '">'
            + '<label class="mb-1 block text-xs font-medium text-slate-600">' + @json(__('After PM (kg)')) + '</label>'
            + '<input type="number" name="item_outcomes[' + index + '][carcass_weight_kg]" value="' + escapeHtml(existing.carcass_weight_kg || '') + '" min="0.1" max="9999" step="0.01" placeholder="kg" class="pm-carcass-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"></div>'
            + '</div>'
            + '<div class="p-4"><h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">' + @json(__('Post-mortem checklist')) + '</h4>'
            + '<div class="overflow-hidden rounded-lg border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm">'
            + '<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Item')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Result')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Notes (optional)')) + '</th></tr></thead>'
            + '<tbody class="divide-y divide-slate-100 bg-white">' + buildChecklistRows(index, existing.observations || {}, speciesName)
            + buildDecisionChecklistRows(index, existing, speciesName)
            + '</tbody></table></div></div></div>';
    }

    function renderLegacyChecklist() {
        var species = currentSpeciesName();
        var key = speciesChecklistKey(species);
        var items = window.postMortemChecklists[key] || window.postMortemChecklists.all_species || {};
        var carcassBody = document.getElementById('carcass-checklist-body');
        var organBody = document.getElementById('organ-checklist-body');
        var decisionBody = document.getElementById('decision-checklist-body');
        var checklistEmpty = document.getElementById('checklist-empty');
        if (!carcassBody || !organBody || !decisionBody) return;

        carcassBody.innerHTML = '';
        organBody.innerHTML = '';
        decisionBody.innerHTML = '';

        var entries = Object.entries(items);
        if (entries.length === 0) {
            if (checklistEmpty) checklistEmpty.classList.remove('hidden');
            return;
        }
        if (checklistEmpty) checklistEmpty.classList.add('hidden');

        entries.forEach(function (entry) {
            var itemKey = entry[0];
            var meta = entry[1];
            var options = window.postMortemValueOptions[meta.type] || [];
            var selectedValue = oldObservations[itemKey] ? (oldObservations[itemKey].value || '') : '';
            var selectedNotes = oldObservations[itemKey] ? (oldObservations[itemKey].notes || '') : '';
            var row = document.createElement('tr');
            var valueField = meta.type === 'free_text'
                ? '<input type="text" name="observations[' + itemKey + '][value]" value="' + escapeHtml(selectedValue) + '" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required />'
                : '<select name="observations[' + itemKey + '][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm' + (itemKey === 'decision' ? ' pm-legacy-decision' : '') + '" required>'
                    + '<option value="">' + @json(__('Select')) + '</option>'
                    + options.map(function (v) {
                        return '<option value="' + v + '"' + (selectedValue === v ? ' selected' : '') + '>' + v.charAt(0).toUpperCase() + v.slice(1) + '</option>';
                    }).join('')
                    + '</select>';
            row.innerHTML = '<td class="px-3 py-2 text-slate-700">' + escapeHtml(meta.label) + '</td>'
                + '<td class="px-3 py-2">' + valueField + '</td>'
                + '<td class="px-3 py-2"><input type="text" name="observations[' + itemKey + '][notes]" value="' + escapeHtml(selectedNotes) + '" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" /></td>';

            if (meta.category === 'organ') organBody.appendChild(row);
            else if (meta.category === 'decision') {
                decisionBody.appendChild(row);
                if (itemKey === 'decision') {
                    appendLegacyCondemnationRows(decisionBody);
                }
            } else carcassBody.appendChild(row);
        });

        bindLegacyDecisionToggle();
    }

    function appendLegacyCondemnationRows(decisionBody) {
        var organValue = oldObservations.condemned_organ ? (oldObservations.condemned_organ.value || '') : '';
        var weightValue = oldObservations.condemned_weight_kg ? (oldObservations.condemned_weight_kg.value || '') : '';
        var organRow = document.createElement('tr');
        organRow.className = 'pm-legacy-condemnation-row hidden';
        organRow.innerHTML = '<td class="px-3 py-2 text-slate-700">' + @json(__('Condemned organ')) + '</td>'
            + '<td class="px-3 py-2">' + buildOrganSelectField('observations[condemned_organ][value]', organValue, 'pm-legacy-condemned-organ') + '</td>'
            + '<td class="px-3 py-2"></td>';

        var weightRow = document.createElement('tr');
        weightRow.className = 'pm-legacy-condemnation-row hidden';
        weightRow.innerHTML = '<td class="px-3 py-2 text-slate-700">' + @json(__('Condemned weight (kg)')) + '</td>'
            + '<td class="px-3 py-2"><input type="number" name="observations[condemned_weight_kg][value]" value="' + escapeHtml(weightValue) + '" min="0.1" max="9999" step="0.01" placeholder="kg" class="pm-legacy-condemned-weight block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" /></td>'
            + '<td class="px-3 py-2"></td>';

        decisionBody.appendChild(organRow);
        decisionBody.appendChild(weightRow);
    }

    function toggleLegacyCondemnationRows() {
        var decisionSelect = document.querySelector('.pm-legacy-decision');
        var rows = document.querySelectorAll('.pm-legacy-condemnation-row');
        if (!decisionSelect || rows.length === 0) {
            return;
        }

        var show = decisionSelect.value === 'rejected';
        rows.forEach(function (row) {
            row.classList.toggle('hidden', !show);
            row.querySelectorAll('input, select').forEach(function (field) {
                field.disabled = !show;
                if (show) {
                    field.setAttribute('required', 'required');
                } else {
                    field.removeAttribute('required');
                }
            });
        });
    }

    function bindLegacyDecisionToggle() {
        var decisionSelect = document.querySelector('.pm-legacy-decision');
        if (!decisionSelect || decisionSelect.dataset.bound === '1') {
            toggleLegacyCondemnationRows();
            return;
        }

        decisionSelect.dataset.bound = '1';
        decisionSelect.addEventListener('change', toggleLegacyCondemnationRows);
        toggleLegacyCondemnationRows();
    }

    function isSectionHidden(section) {
        if (!section) {
            return true;
        }

        return section.classList.contains('hidden') || section.style.display === 'none';
    }

    function setLegacyFieldsEnabled(enabled) {
        var legacySection = document.getElementById('legacy-checklist-section');
        if (!legacySection) {
            return;
        }

        legacySection.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (enabled) {
                field.disabled = false;
            } else {
                field.removeAttribute('required');
                field.disabled = true;
            }
        });
    }

    function pendingAnimalsFromExecution(executionData) {
        if (!executionData || !executionData.animals) {
            return [];
        }

        var inspected = (executionData.inspected_animal_ids || []).map(String);

        return executionData.animals.filter(function (animal) {
            return inspected.indexOf(String(animal.animal_intake_item_id)) === -1;
        });
    }

    function animalsForDisplay(executionData) {
        if (incrementalAnimalSelection) {
            return selectedAnimals;
        }

        if (preserveExistingOutcomes && selectedAnimals.length > 0) {
            return selectedAnimals;
        }

        return pendingAnimalsFromExecution(executionData);
    }

    function syncSelectedAnimalsFromExecution(executionData) {
        if (incrementalAnimalSelection) {
            selectedAnimals = [];
            return;
        }

        selectedAnimals = animalsForDisplay(executionData);
    }

    function updateExecutionAnimalsRoster(executionData) {
        var roster = document.getElementById('execution-animals-roster');
        var listEl = document.getElementById('execution-animals-list');
        var countEl = document.getElementById('execution-animals-count');
        if (!roster || !listEl || !executionData || !executionData.animals) {
            return;
        }

        var inspected = (executionData.inspected_animal_ids || []).map(String);
        var animals = executionData.animals;

        if (countEl) {
            countEl.textContent = String(animals.length);
        }

        listEl.innerHTML = animals.map(function (animal) {
            var isInspected = inspected.indexOf(String(animal.animal_intake_item_id)) !== -1;
            var tag = escapeHtml(animal.ear_tag || '—');
            var statusClass = isInspected
                ? 'border-slate-200 bg-slate-100 text-slate-500'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800';
            var statusLabel = isInspected
                ? @json(__('Inspected'))
                : @json(__('Pending'));

            return '<li class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium ' + statusClass + '">'
                + '<span class="font-mono">' + tag + '</span>'
                + '<span class="text-[10px] uppercase tracking-wide">' + statusLabel + '</span>'
                + '</li>';
        }).join('');
    }

    function updatePendingCount() {
        var pendingEl = document.getElementById('pending-animal-count');
        if (!pendingEl || !currentExecutionData) {
            return;
        }

        var inspected = (currentExecutionData.inspected_animal_ids || []).map(String);
        var selected = selectedAnimals.map(function (animal) {
            return String(animal.animal_intake_item_id);
        });
        var pending = (currentExecutionData.animals || []).filter(function (animal) {
            var id = String(animal.animal_intake_item_id);
            return inspected.indexOf(id) === -1 && selected.indexOf(id) === -1;
        }).length;

        pendingEl.textContent = String(pending);
    }

    function toggleInspectionSections(executionData) {
        var legacySection = document.getElementById('legacy-checklist-section');
        var perAnimalSection = document.getElementById('per-animal-outcomes-section');
        var tagLookup = document.getElementById('animal-tag-lookup');
        var hasPool = executionData && executionData.animals && executionData.animals.length > 0;
        var hasSelected = animalsForDisplay(executionData).length > 0;

        if (perAnimalSection) {
            perAnimalSection.classList.toggle('hidden', !hasPool);
        }
        if (tagLookup) {
            tagLookup.classList.toggle('hidden', !hasPool || !incrementalAnimalSelection);
        }
        var animalsRoster = document.getElementById('execution-animals-roster');
        if (animalsRoster) {
            animalsRoster.classList.toggle('hidden', !hasPool || incrementalAnimalSelection);
            updateExecutionAnimalsRoster(executionData);
        }
        if (legacySection) {
            legacySection.classList.toggle('hidden', hasPool);
        }
        setLegacyFieldsEnabled(!hasPool);

        if (hasPool) {
            ['carcass-checklist-body', 'organ-checklist-body', 'decision-checklist-body'].forEach(function (id) {
                var body = document.getElementById(id);
                if (body) {
                    body.innerHTML = '';
                }
            });
        }

        toggleAggregateCountsSection(hasSelected);
        updatePendingCount();

        if (!hasPool) {
            renderLegacyChecklist();
        }
    }

    function rebuildOutcomesTable(executionData) {
        var container = document.getElementById('per-animal-outcomes-container');
        if (!container) return;

        currentExecutionData = executionData;
        var animals = animalsForDisplay(executionData);
        var speciesName = currentSpeciesName();

        if (!executionData || !executionData.animals || executionData.animals.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">' + @json(__('No slaughtered animals are linked to this execution.')) + '</p>';
            toggleInspectionSections(executionData);
            return;
        }

        if (animals.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">' + @json(__('All animals in this slaughter execution already have post-mortem outcomes recorded.')) + '</p>';
            toggleInspectionSections(executionData);
            return;
        }

        container.innerHTML = '<div class="space-y-4">'
            + animals.map(function (animal, index) {
                var existing = existingOutcomeForAnimal(animal.animal_intake_item_id);
                return buildAnimalCard(animal, index, existing, speciesName);
            }).join('') + '</div>';

        bindPerAnimalAggregateListeners(container);
        toggleInspectionSections(executionData);
        syncAggregateCounts();
    }

    function findAnimalByTag(tag) {
        var needle = normalizeTag(tag);
        if (!needle || !currentExecutionData || !currentExecutionData.animals) {
            return null;
        }

        return currentExecutionData.animals.find(function (animal) {
            return animalSearchTags(animal).some(function (candidate) {
                return candidate === needle;
            });
        }) || null;
    }

    function showTagFeedback(message, isError) {
        var feedback = document.getElementById('animal-tag-feedback');
        if (!feedback) return;

        feedback.textContent = message;
        feedback.classList.remove('hidden', 'text-red-700', 'text-emerald-700');
        feedback.classList.add(isError ? 'text-red-700' : 'text-emerald-700');
    }

    function addAnimalByTag() {
        var input = document.getElementById('animal_tag_search');
        if (!input || !currentExecutionData) {
            return;
        }

        var tag = input.value;
        var animal = findAnimalByTag(tag);
        if (!animal) {
            showTagFeedback(@json(__('No slaughtered animal found for that ear tag or tag number in this execution.')), true);
            return;
        }

        var animalId = String(animal.animal_intake_item_id);
        var inspected = (currentExecutionData.inspected_animal_ids || []).map(String);
        if (inspected.indexOf(animalId) !== -1) {
            showTagFeedback(@json(__('This animal already has a post-mortem outcome recorded.')), true);
            return;
        }

        if (selectedAnimals.some(function (entry) {
            return String(entry.animal_intake_item_id) === animalId;
        })) {
            showTagFeedback(@json(__('This animal is already in the inspection list.')), true);
            return;
        }

        selectedAnimals.push(animal);
        input.value = '';
        showTagFeedback(@json(__('Animal added.')), false);
        rebuildOutcomesTable(currentExecutionData);
    }

    function updateFromExecution(selectEl) {
        var executionId = selectEl.value;
        var executionData = executionId
            ? (window.postMortemExecutionAnimals[executionId] || window.postMortemExecutionAnimals[String(executionId)] || null)
            : null;

        if (executionData && executionData.species) {
            var speciesSelect = document.getElementById('species');
            if (speciesSelect) {
                speciesSelect.value = executionData.species;
            }
        }

        if (!preserveExistingOutcomes) {
            syncSelectedAnimalsFromExecution(executionData);
        }

        filterInspectors();
        rebuildOutcomesTable(executionData);
    }

    function updateFromBatch(selectEl) {
        var batchId = selectEl.value;
        var batchData = batchId
            ? (window.postMortemBatchAnimals[batchId] || window.postMortemBatchAnimals[String(batchId)] || null)
            : null;

        if (batchData && batchData.species) {
            var speciesSelect = document.getElementById('species');
            if (speciesSelect) {
                speciesSelect.value = batchData.species;
            }
        }

        filterInspectors();
        currentExecutionData = batchData;
        selectedAnimals = batchData && batchData.animals ? batchData.animals : [];
        rebuildOutcomesTable(batchData);
    }

    function preparePostMortemFormForSubmit(form) {
        syncAggregateCounts();
        syncLegacyApprovedTotal();

        var legacySection = document.getElementById('legacy-checklist-section');
        if (isSectionHidden(legacySection)) {
            ['carcass-checklist-body', 'organ-checklist-body', 'decision-checklist-body'].forEach(function (id) {
                var body = document.getElementById(id);
                if (body) {
                    body.innerHTML = '';
                }
            });
            setLegacyFieldsEnabled(false);
        }

        var aggregateSection = document.getElementById('aggregate-counts-section');
        if (isSectionHidden(aggregateSection)) {
            aggregateSection.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.removeAttribute('required');
            });
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('button[type="submit"]');
        if (!button) {
            return;
        }

        var form = button.closest('#post-mortem-form, #post-mortem-edit-form');
        if (form) {
            preparePostMortemFormForSubmit(form);
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || (form.id !== 'post-mortem-form' && form.id !== 'post-mortem-edit-form')) {
            return;
        }

        preparePostMortemFormForSubmit(form);
    }, true);

    function filterInspectors() {
        var executionSelect = document.getElementById('slaughter_execution_id');
        var batchSelect = document.getElementById('batch_id');
        var inspectorSelect = document.getElementById('inspector_id');
        if (!inspectorSelect) return;

        var facilityId = '';
        if (executionSelect && executionSelect.tagName === 'SELECT') {
            var option = executionSelect.options[executionSelect.selectedIndex];
            facilityId = option ? option.dataset.facilityId : '';
        } else if (executionSelect && executionSelect.value && currentExecutionData) {
            facilityId = String(currentExecutionData.facility_id || '');
        } else if (batchSelect) {
            var batchOption = batchSelect.options[batchSelect.selectedIndex];
            facilityId = batchOption ? batchOption.dataset.facilityId : '';
        }
        var selectedOption = inspectorSelect.options[inspectorSelect.selectedIndex];
        var selectedFacilityMismatch = selectedOption
            && selectedOption.value !== ''
            && selectedOption.dataset.facilityId !== facilityId;

        Array.from(inspectorSelect.options).forEach(function (opt) {
            if (opt.value === '') {
                opt.textContent = facilityId
                    ? @json(__('Select inspector'))
                    : @json(__('Select slaughter execution first'));
                opt.hidden = false;
                opt.disabled = false;
                return;
            }

            var allowed = opt.dataset.facilityId === facilityId;
            opt.hidden = false;
            opt.disabled = !allowed;
        });

        if (selectedFacilityMismatch) {
            inspectorSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var outcomesContainer = document.getElementById('per-animal-outcomes-container');
        if (outcomesContainer) {
            outcomesContainer.addEventListener('change', function (event) {
                if (event.target.matches('.pm-animal-outcome')) {
                    toggleCondemnationFields(event.target.closest('[data-pm-animal-card]'));
                    syncAggregateCounts();
                }
            });
        }

        var executionSelect = document.getElementById('slaughter_execution_id');
        var batchSelect = document.getElementById('batch_id');
        var speciesSelect = document.getElementById('species');
        var addAnimalButton = document.getElementById('add-animal-by-tag');
        var tagSearchInput = document.getElementById('animal_tag_search');

        if (addAnimalButton) {
            addAnimalButton.addEventListener('click', addAnimalByTag);
        }

        if (tagSearchInput) {
            tagSearchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addAnimalByTag();
                }
            });
        }

        if (executionSelect) {
            executionSelect.addEventListener('change', function () {
                preserveExistingOutcomes = false;
                existingInspectionOutcomes = {};
                selectedAnimals = [];
                updateFromExecution(this);
            });

            if (executionSelect.value) {
                if (selectedAnimals.length > 0) {
                    updateFromExecution(executionSelect);
                } else {
                    var container = document.getElementById('per-animal-outcomes-container');
                    var hasRenderedCards = container && container.querySelector('[data-pm-animal-card]');

                    if (hasRenderedCards && preserveExistingOutcomes) {
                        currentExecutionData = window.postMortemExecutionAnimals[executionSelect.value]
                            || window.postMortemExecutionAnimals[String(executionSelect.value)]
                            || null;
                        toggleInspectionSections(currentExecutionData);
                        bindPerAnimalAggregateListeners(container);
                        syncAggregateCounts();
                        updatePendingCount();
                    } else {
                        updateFromExecution(executionSelect);
                    }
                }
            } else {
                rebuildOutcomesTable(null);
            }
        } else if (batchSelect) {
            batchSelect.addEventListener('change', function () {
                preserveExistingOutcomes = false;
                existingInspectionOutcomes = {};
                updateFromBatch(this);
            });

            if (batchSelect.value) {
                updateFromBatch(batchSelect);
            } else {
                rebuildOutcomesTable(null);
            }
        } else {
            var hiddenExecutionInput = document.getElementById('slaughter_execution_id');
            if (hiddenExecutionInput && hiddenExecutionInput.value) {
                currentExecutionData = window.postMortemExecutionAnimals[hiddenExecutionInput.value]
                    || window.postMortemExecutionAnimals[String(hiddenExecutionInput.value)]
                    || null;

                var container = document.getElementById('per-animal-outcomes-container');
                var hasRenderedCards = container && container.querySelector('[data-pm-animal-card]');

                if (hasRenderedCards && preserveExistingOutcomes) {
                    toggleInspectionSections(currentExecutionData);
                    bindPerAnimalAggregateListeners(container);
                    syncAggregateCounts();
                    updatePendingCount();
                } else {
                    rebuildOutcomesTable(currentExecutionData);
                }
            }
        }

        if (speciesSelect) {
            speciesSelect.addEventListener('change', function () {
                if (executionSelect && executionSelect.value) {
                    updateFromExecution(executionSelect);
                } else if (batchSelect && batchSelect.value) {
                    updateFromBatch(batchSelect);
                } else {
                    renderLegacyChecklist();
                }
            });
        }

        filterInspectors();
        bindPerAnimalAggregateListeners(document.getElementById('per-animal-outcomes-container'));
        ['approved_carcass_kg', 'approved_other_meat_kg'].forEach(function (fieldId) {
            var field = document.getElementById(fieldId);
            if (!field || field.dataset.bound === '1') {
                return;
            }
            field.dataset.bound = '1';
            field.addEventListener('input', syncLegacyApprovedTotal);
        });
        syncAggregateCounts();
        syncLegacyApprovedTotal();
        updatePendingCount();
    });
}());
</script>
@endpush
