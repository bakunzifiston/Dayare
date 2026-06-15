@php
    use App\Support\PostMortemChecklist;
@endphp

@push('scripts')
<script>
(function () {
    'use strict';

    window.postMortemBatchAnimals = @json($batchAnimalsByBatchId ?? []);
    window.postMortemChecklists = @json($checklists ?? PostMortemChecklist::all());
    window.postMortemSpeciesAliases = @json(config('post_mortem_checklist.species_aliases'));
    window.postMortemValueOptions = @json(config('post_mortem_checklist.value_options'));

    var existingInspectionOutcomes = @json($existingInspectionOutcomes ?? []);
    var preserveExistingOutcomes = @json($preserveExistingOutcomes ?? false);

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

    function speciesChecklistKey(speciesName) {
        if (!speciesName) return 'all_species';
        return window.postMortemSpeciesAliases[String(speciesName).toLowerCase().trim()] || 'all_species';
    }

    function checklistItemsForSpecies(speciesName) {
        var key = speciesChecklistKey(speciesName);
        var items = window.postMortemChecklists[key] || window.postMortemChecklists.all_species || {};
        return Object.entries(items).filter(function (entry) {
            return entry[0] !== 'decision';
        });
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
        var condemnedInput = document.getElementById('condemned_quantity');
        if (!container || !examinedInput || !approvedInput || !condemnedInput) return;

        var examinedKg = 0;
        var approvedKg = 0;
        var condemnedKg = 0;

        container.querySelectorAll('[data-pm-animal-card]').forEach(function (card) {
            var beforeKg = parseFloat(card.dataset.meatKg || '0');
            var select = card.querySelector('.pm-animal-outcome');
            var carcassInput = card.querySelector('.pm-carcass-weight');
            var outcome = select ? select.value : '';
            var afterKg = carcassInput ? parseFloat(carcassInput.value) : NaN;

            if (outcome !== 'approved' && outcome !== 'condemned' && outcome !== 'deferred') {
                return;
            }

            examinedKg += Number.isFinite(beforeKg) ? beforeKg : 0;

            if (outcome === 'approved') {
                approvedKg += Number.isFinite(afterKg) && afterKg > 0 ? afterKg : beforeKg;
            } else if (outcome === 'condemned') {
                condemnedKg += Number.isFinite(beforeKg) ? beforeKg : 0;
            }
        });

        examinedInput.value = formatKgDisplay(examinedKg);
        approvedInput.value = formatKgDisplay(approvedKg);
        condemnedInput.value = formatKgDisplay(condemnedKg);

        var examinedDisplay = document.getElementById('pm-summary-examined');
        var approvedDisplay = document.getElementById('pm-summary-approved');
        var condemnedDisplay = document.getElementById('pm-summary-condemned');
        if (examinedDisplay) examinedDisplay.textContent = formatKgDisplay(examinedKg);
        if (approvedDisplay) approvedDisplay.textContent = formatKgDisplay(approvedKg);
        if (condemnedDisplay) condemnedDisplay.textContent = formatKgDisplay(condemnedKg);
    }

    function bindPerAnimalAggregateListeners(container) {
        if (!container) return;
        container.querySelectorAll('.pm-animal-outcome').forEach(function (select) {
            select.addEventListener('change', syncAggregateCounts);
        });
        container.querySelectorAll('.pm-carcass-weight').forEach(function (input) {
            input.addEventListener('input', syncAggregateCounts);
        });
    }

    function toggleAggregateCountsSection(hasAnimals) {
        var section = document.getElementById('aggregate-counts-section');
        var summary = document.getElementById('per-animal-aggregate-summary');
        if (section) {
            section.classList.toggle('hidden', !!hasAnimals);
        }
        if (summary) {
            summary.classList.toggle('hidden', !hasAnimals);
        }
        if (hasAnimals) {
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

    function buildAnimalCard(animal, index, existing, speciesName) {
        var legacy = String(animal.ear_tag || '').startsWith('LEGACY-')
            ? '<span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>' : '';
        var sourceLabel = animal.source === 'execution'
            ? @json(__('From slaughter execution'))
            : @json(__('In batch'));
        var currentOutcome = existing.outcome || '';
        var outcomeOptions = '<option value="">' + @json(__('Select outcome')) + '</option>'
            + ['approved', 'condemned', 'deferred'].map(function (outcome) {
                var selected = currentOutcome === outcome ? ' selected' : '';
                return '<option value="' + outcome + '"' + selected + '>' + outcome.charAt(0).toUpperCase() + outcome.slice(1) + '</option>';
            }).join('');
        var batchItemField = animal.batch_item_id
            ? '<input type="hidden" name="item_outcomes[' + index + '][batch_item_id]" value="' + animal.batch_item_id + '">'
            : '';

        var meatKgLabel = formatMeatKg(animal.meat_quantity_kg);

        return '<div class="overflow-hidden rounded-lg border border-slate-200" data-pm-animal-card data-meat-kg="' + escapeHtml(animal.meat_quantity_kg || '0') + '">'
            + '<div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">'
            + '<div class="min-w-0 flex-1"><p class="font-mono text-sm font-medium text-slate-900">' + escapeHtml(animal.ear_tag) + legacy + '</p>'
            + '<p class="mt-0.5 text-xs text-slate-500">' + escapeHtml(animal.species) + ' · ' + escapeHtml(animal.sex)
            + ' · ' + escapeHtml(animal.session_label) + ' · ' + sourceLabel
            + ' · <span class="font-medium text-slate-700 sm:hidden">' + meatKgLabel + ' kg</span></p></div>'
            + '<div class="w-full sm:w-36">' + batchItemField
            + '<input type="hidden" name="item_outcomes[' + index + '][animal_intake_item_id]" value="' + animal.animal_intake_item_id + '">'
            + '<select name="item_outcomes[' + index + '][outcome]" class="pm-animal-outcome block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required>'
            + outcomeOptions + '</select></div>'
            + '<div class="hidden w-24 text-right sm:block"><p class="text-sm font-medium tabular-nums text-slate-900">' + meatKgLabel + '</p>'
            + '<p class="text-[10px] uppercase tracking-wide text-slate-400">' + @json(__('Slaughter')) + '</p></div>'
            + '<div class="w-full sm:w-28"><input type="number" name="item_outcomes[' + index + '][carcass_weight_kg]" value="' + escapeHtml(existing.carcass_weight_kg || '') + '" min="0.1" max="9999" step="0.01" placeholder="kg" class="pm-carcass-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"></div>'
            + '<div class="w-full sm:flex-1"><input type="text" name="item_outcomes[' + index + '][outcome_notes]" value="' + escapeHtml(existing.outcome_notes || '') + '" placeholder=' + @json(__('Outcome notes (optional)')) + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"></div>'
            + '</div><div class="p-4"><h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">' + @json(__('Post-mortem checklist')) + '</h4>'
            + '<div class="overflow-hidden rounded-lg border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm">'
            + '<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Item')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Result')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Notes (optional)')) + '</th></tr></thead>'
            + '<tbody class="divide-y divide-slate-100 bg-white">' + buildChecklistRows(index, existing.observations || {}, speciesName) + '</tbody></table></div></div></div>';
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
                : '<select name="observations[' + itemKey + '][value]" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>'
                    + '<option value="">' + @json(__('Select')) + '</option>'
                    + options.map(function (v) {
                        return '<option value="' + v + '"' + (selectedValue === v ? ' selected' : '') + '>' + v.charAt(0).toUpperCase() + v.slice(1) + '</option>';
                    }).join('')
                    + '</select>';
            row.innerHTML = '<td class="px-3 py-2 text-slate-700">' + escapeHtml(meta.label) + '</td>'
                + '<td class="px-3 py-2">' + valueField + '</td>'
                + '<td class="px-3 py-2"><input type="text" name="observations[' + itemKey + '][notes]" value="' + escapeHtml(selectedNotes) + '" class="block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" maxlength="5000" /></td>';

            if (meta.category === 'organ') organBody.appendChild(row);
            else if (meta.category === 'decision') decisionBody.appendChild(row);
            else carcassBody.appendChild(row);
        });
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

    function toggleInspectionSections(batchData) {
        var legacySection = document.getElementById('legacy-checklist-section');
        var perAnimalSection = document.getElementById('per-animal-outcomes-section');
        var sourceNotice = document.getElementById('animal-source-notice');
        var hasAnimals = batchData && batchData.animals && batchData.animals.length > 0;

        if (perAnimalSection) {
            perAnimalSection.classList.toggle('hidden', !hasAnimals);
        }
        if (legacySection) {
            legacySection.classList.toggle('hidden', hasAnimals);
        }
        setLegacyFieldsEnabled(!hasAnimals);

        if (hasAnimals) {
            ['carcass-checklist-body', 'organ-checklist-body', 'decision-checklist-body'].forEach(function (id) {
                var body = document.getElementById(id);
                if (body) {
                    body.innerHTML = '';
                }
            });
        }

        if (sourceNotice) {
            var showExecutionNotice = hasAnimals && batchData.source === 'execution';
            sourceNotice.classList.toggle('hidden', !showExecutionNotice);
            if (showExecutionNotice) {
                sourceNotice.textContent = @json(__('Showing slaughtered animals from the linked execution because this batch has no individual animal rows yet.'));
            }
        }

        toggleAggregateCountsSection(hasAnimals);

        if (!hasAnimals) {
            renderLegacyChecklist();
        }
    }

    function rebuildOutcomesTable(batchData) {
        var container = document.getElementById('per-animal-outcomes-container');
        if (!container) return;

        var animals = batchData && batchData.animals ? batchData.animals : [];
        var speciesName = currentSpeciesName();

        if (animals.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">' + @json(__('Select a batch to load animals for individual post-mortem inspection.')) + '</p>';
            toggleInspectionSections(batchData);
            return;
        }

        container.innerHTML = '<div class="space-y-4">'
            + '<div class="hidden flex-wrap items-end gap-3 px-4 sm:flex">'
            + '<div class="min-w-0 flex-1 text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('Animal')) + '</div>'
            + '<div class="w-36 text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('Outcome')) + '</div>'
            + '<div class="w-24 text-right text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('Before PM (kg)')) + '</div>'
            + '<div class="w-28 text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('After PM (kg)')) + '</div>'
            + '<div class="flex-1 text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('Notes')) + '</div>'
            + '</div>'
            + animals.map(function (animal, index) {
            var existing = existingOutcomeForAnimal(animal.animal_intake_item_id);
            return buildAnimalCard(animal, index, existing, speciesName);
        }).join('') + '</div>';

        bindPerAnimalAggregateListeners(container);

        toggleInspectionSections(batchData);
        syncAggregateCounts();
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
        rebuildOutcomesTable(batchData);
    }

    function preparePostMortemFormForSubmit(form) {
        syncAggregateCounts();

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
        var batchSelect = document.getElementById('batch_id');
        var inspectorSelect = document.getElementById('inspector_id');
        if (!batchSelect || !inspectorSelect) return;

        var option = batchSelect.options[batchSelect.selectedIndex];
        var facilityId = option ? option.dataset.facilityId : '';
        var selectedOption = inspectorSelect.options[inspectorSelect.selectedIndex];
        var selectedFacilityMismatch = selectedOption
            && selectedOption.value !== ''
            && selectedOption.dataset.facilityId !== facilityId;

        Array.from(inspectorSelect.options).forEach(function (opt) {
            if (opt.value === '') {
                opt.textContent = facilityId ? @json(__('Select inspector')) : @json(__('Select batch first'));
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
        var batchSelect = document.getElementById('batch_id');
        var speciesSelect = document.getElementById('species');

        if (batchSelect) {
            batchSelect.addEventListener('change', function () {
                preserveExistingOutcomes = false;
                existingInspectionOutcomes = {};
                updateFromBatch(this);
            });

            if (batchSelect.value) {
                var container = document.getElementById('per-animal-outcomes-container');
                var hasRenderedCards = container && container.querySelector('[data-pm-animal-card]');

                if (hasRenderedCards && preserveExistingOutcomes) {
                    var batchData = window.postMortemBatchAnimals[batchSelect.value]
                        || window.postMortemBatchAnimals[String(batchSelect.value)]
                        || null;
                    toggleInspectionSections(batchData);
                    bindPerAnimalAggregateListeners(container);
                    syncAggregateCounts();
                } else {
                    updateFromBatch(batchSelect);
                }
            } else {
                rebuildOutcomesTable(null);
            }
        }

        if (speciesSelect) {
            speciesSelect.addEventListener('change', function () {
                if (batchSelect && batchSelect.value) {
                    updateFromBatch(batchSelect);
                } else {
                    renderLegacyChecklist();
                }
            });
        }

        filterInspectors();
        bindPerAnimalAggregateListeners(document.getElementById('per-animal-outcomes-container'));
        syncAggregateCounts();
    });
}());
</script>
@endpush
