@php
    use App\Support\AnteMortemChecklist;

    $planMetaById = collect($plans)->mapWithKeys(fn (array $plan) => [
        $plan['id'] => [
            'species' => $plan['species'] ?? null,
            'scheduled_count' => $plan['scheduled_count'] ?? 0,
            'assigned_count' => $plan['assigned_count'] ?? 0,
        ],
    ]);
@endphp

@push('scripts')
<script>
(function () {
    'use strict';

    window.anteMortemAssignedItemsByPlan = @json($assignedItemsByPlan);
    window.anteMortemPlanMeta = @json($planMetaById);
    window.anteMortemChecklists = @json($checklists ?? AnteMortemChecklist::all());
    window.anteMortemSpeciesAliases = @json(config('ante_mortem_checklist.species_aliases'));
    window.anteMortemValueOptions = @json(config('ante_mortem_checklist.value_options'));

    var existingInspectionOutcomes = @json($existingInspectionOutcomes ?? []);
    var preserveExistingOutcomes = @json($preserveExistingOutcomes ?? false);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function speciesChecklistKey(speciesName) {
        if (!speciesName) {
            return 'all_species';
        }

        return window.anteMortemSpeciesAliases[String(speciesName).toLowerCase().trim()] || 'all_species';
    }

    function checklistItemsForSpecies(speciesName) {
        var key = speciesChecklistKey(speciesName);
        var items = window.anteMortemChecklists[key] || window.anteMortemChecklists.all_species || {};
        var entries = Object.entries(items).filter(function (entry) {
            return entry[0] !== 'decision';
        });

        return entries;
    }

    function getHealthBadgeClass(status) {
        if (status === 'healthy') return 'bg-green-100 text-green-800';
        if (status === 'under_observation') return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    }

    function planAnimals(planId) {
        if (!planId) {
            return [];
        }

        return window.anteMortemAssignedItemsByPlan[planId]
            || window.anteMortemAssignedItemsByPlan[String(planId)]
            || [];
    }

    function filterAnimalsBySpecies(animals) {
        var speciesEl = document.getElementById('species');
        if (!speciesEl || !speciesEl.value) {
            return animals;
        }

        return animals.filter(function (animal) {
            return animal.species === speciesEl.value;
        });
    }

    function currentSpeciesName() {
        var speciesEl = document.getElementById('species');
        return speciesEl ? speciesEl.value : '';
    }

    function syncAggregateCounts() {
        var container = document.getElementById('per-animal-outcomes-container');
        var examinedInput = document.getElementById('number_examined');
        var approvedInput = document.getElementById('number_approved');
        var rejectedInput = document.getElementById('number_rejected');
        if (!container || !examinedInput || !approvedInput || !rejectedInput) {
            return;
        }

        var selects = container.querySelectorAll('select[name*="[outcome]"]');
        var examined = selects.length;
        var approved = 0;
        var rejected = 0;

        selects.forEach(function (select) {
            if (select.value === 'approved') approved++;
            if (select.value === 'rejected') rejected++;
        });

        examinedInput.value = examined;
        approvedInput.value = approved;
        rejectedInput.value = rejected;
    }

    function toggleAggregateCountsSection(animals) {
        var section = document.getElementById('aggregate-counts-section');
        if (!section) {
            return;
        }

        if (animals && animals.length > 0) {
            section.style.display = 'none';
            syncAggregateCounts();
        } else {
            section.style.display = '';
        }
    }

    function buildChecklistRows(index, observations, speciesName) {
        observations = observations || {};

        return checklistItemsForSpecies(speciesName).map(function (entry) {
            var itemKey = entry[0];
            var meta = entry[1];
            var options = window.anteMortemValueOptions[meta.type] || [];
            var obsValue = observations[itemKey] ? (observations[itemKey].value || '') : '';
            var obsNotes = observations[itemKey] ? (observations[itemKey].notes || '') : '';
            var valueField = meta.type === 'free_text'
                ? '<input type="text" name="item_outcomes[' + index + '][observations][' + itemKey + '][value]"'
                    + ' value="' + escapeHtml(obsValue) + '"'
                    + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required />'
                : '<select name="item_outcomes[' + index + '][observations][' + itemKey + '][value]"'
                    + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" required>'
                    + '<option value="">' + @json(__('Select')) + '</option>'
                    + options.map(function (option) {
                        var selected = obsValue === option ? ' selected' : '';
                        return '<option value="' + option + '"' + selected + '>'
                            + option.charAt(0).toUpperCase() + option.slice(1) + '</option>';
                    }).join('')
                    + '</select>';

            return '<tr>'
                + '<td class="px-3 py-2 text-slate-700">' + escapeHtml(meta.label) + '</td>'
                + '<td class="px-3 py-2">' + valueField + '</td>'
                + '<td class="px-3 py-2"><input type="text"'
                + ' name="item_outcomes[' + index + '][observations][' + itemKey + '][notes]"'
                + ' value="' + escapeHtml(obsNotes) + '" maxlength="5000"'
                + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" /></td>'
                + '</tr>';
        }).join('');
    }

    function buildAnimalCard(animal, index, existing, speciesName) {
        var isObs = animal.health_status === 'under_observation';
        var badge = getHealthBadgeClass(animal.health_status);
        var legacy = String(animal.ear_tag || '').startsWith('LEGACY-')
            ? '<span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>'
            : '';
        var outcomeOptions = ['approved', 'rejected', 'deferred'].map(function (outcome) {
            var selected = existing.outcome
                ? (outcome === existing.outcome ? ' selected' : '')
                : (!isObs && outcome === 'approved' ? ' selected' : '');

            return '<option value="' + outcome + '"' + selected + '>'
                + outcome.charAt(0).toUpperCase() + outcome.slice(1) + '</option>';
        }).join('');

        return '<div class="overflow-hidden rounded-lg border border-slate-200' + (isObs ? ' ring-1 ring-yellow-200' : '') + '">'
            + '<div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">'
            + '<div class="min-w-0 flex-1">'
            + '<p class="font-mono text-sm font-medium text-slate-900">' + escapeHtml(animal.ear_tag || '—') + legacy + '</p>'
            + '<p class="mt-0.5 text-xs text-slate-500">' + escapeHtml(animal.sex || '—')
            + ' <span class="mx-1">·</span>'
            + '<span class="inline-flex rounded-full px-2 py-0.5 text-xs ' + badge + '">'
            + escapeHtml(animal.health_status_label || animal.health_status || '—') + '</span></p>'
            + '</div>'
            + '<div class="w-full sm:w-40">'
            + '<input type="hidden" name="item_outcomes[' + index + '][animal_intake_item_id]" value="' + animal.id + '">'
            + '<select name="item_outcomes[' + index + '][outcome]"'
            + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">'
            + outcomeOptions + '</select>'
            + '</div>'
            + '<div class="w-full sm:flex-1">'
            + '<textarea name="item_outcomes[' + index + '][outcome_notes]" rows="1"'
            + ' placeholder=' + @json(__('Outcome notes (optional)'))
            + ' class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">'
            + escapeHtml(existing.outcome_notes || '') + '</textarea>'
            + '</div>'
            + '</div>'
            + '<div class="p-4">'
            + '<h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">'
            + @json(__('Inspection checklist')) + '</h4>'
            + '<div class="overflow-hidden rounded-lg border border-slate-200">'
            + '<table class="min-w-full divide-y divide-slate-200 text-sm">'
            + '<thead class="bg-slate-50"><tr>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Item')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Result')) + '</th>'
            + '<th class="px-3 py-2 text-left font-medium text-slate-600">' + @json(__('Notes (optional)')) + '</th>'
            + '</tr></thead><tbody class="divide-y divide-slate-100 bg-white">'
            + buildChecklistRows(index, existing.observations || {}, speciesName)
            + '</tbody></table></div></div></div>';
    }

    function toggleInspectionSections(animals, meta) {
        var checklistSection = document.getElementById('inspection-checklist-section');
        var perAnimalSection = document.getElementById('per-animal-outcomes-section');
        var gapAlert = document.getElementById('assignment-gap-alert');
        var gapText = document.getElementById('assignment-gap-alert-text');

        if (animals.length > 0) {
            if (checklistSection) {
                checklistSection.classList.add('hidden');
            }
            var legacyChecklistBody = document.getElementById('checklist-body');
            if (legacyChecklistBody) {
                legacyChecklistBody.innerHTML = '';
            }
            if (perAnimalSection) {
                perAnimalSection.classList.remove('hidden');
            }
            if (gapAlert) {
                gapAlert.classList.add('hidden');
            }

            return;
        }

        if (perAnimalSection) {
            perAnimalSection.classList.remove('hidden');
        }

        var scheduled = meta ? (meta.scheduled_count || 0) : 0;
        var assigned = meta ? (meta.assigned_count || 0) : 0;

        if (scheduled > 0 && assigned === 0) {
            if (checklistSection) {
                checklistSection.classList.add('hidden');
            }
            if (gapAlert) {
                gapAlert.classList.remove('hidden');
            }
            if (gapText) {
                gapText.textContent = String(scheduled) + ' '
                    + @json(__('animals are scheduled on this session but no individual animals are linked yet. Edit the slaughter plan to assign animals before recording ante-mortem.'));
            }
            return;
        }

        if (checklistSection) {
            checklistSection.classList.remove('hidden');
        }
        if (gapAlert) {
            gapAlert.classList.add('hidden');
        }

        if (typeof window.renderAnteMortemChecklist === 'function') {
            window.anteMortemExcludeDecision = false;
            window.renderAnteMortemChecklist();
        }
    }

    function rebuildOutcomesTable(animals, outcomes) {
        var container = document.getElementById('per-animal-outcomes-container');
        if (!container) {
            return;
        }

        outcomes = outcomes || {};
        var speciesName = currentSpeciesName();

        if (!animals || animals.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">'
                + @json(__('No individually assigned animals for the selected species. Choose another session or species, or use the manual counts below for legacy plans.'))
                + '</p>';
            toggleAggregateCountsSection([]);
            return;
        }

        container.innerHTML = '<div class="space-y-4">'
            + animals.map(function (animal, index) {
                return buildAnimalCard(animal, index, outcomes[animal.id] || {}, speciesName);
            }).join('')
            + '</div>';

        container.querySelectorAll('select[name*="[outcome]"]').forEach(function (select) {
            select.addEventListener('change', syncAggregateCounts);
        });

        toggleAggregateCountsSection(animals);
    }

    function updateFromPlan(selectEl) {
        var option = selectEl.options[selectEl.selectedIndex];
        var speciesSelect = document.getElementById('species');
        var meta = null;

        if (!option || !option.value) {
            rebuildOutcomesTable([], {});
            toggleInspectionSections([], null);
            toggleAggregateCountsSection([]);

            var banner = document.getElementById('under-observation-banner');
            var notesField = document.getElementById('notes-for-under-observation-field');
            if (banner) banner.style.display = 'none';
            if (notesField) notesField.style.display = 'none';
            return;
        }

        meta = window.anteMortemPlanMeta[option.value]
            || window.anteMortemPlanMeta[String(option.value)]
            || null;

        if (meta && meta.species && speciesSelect) {
            speciesSelect.value = meta.species;
            speciesSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        var allAnimals = planAnimals(option.value);
        var animals = filterAnimalsBySpecies(allAnimals);
        var outcomes = preserveExistingOutcomes ? existingInspectionOutcomes : {};

        var obsCount = animals.filter(function (animal) {
            return animal.health_status === 'under_observation';
        }).length;

        var banner = document.getElementById('under-observation-banner');
        var countEl = document.getElementById('under-observation-count');
        var notesField = document.getElementById('notes-for-under-observation-field');

        if (obsCount > 0) {
            if (banner) banner.style.display = '';
            if (countEl) countEl.textContent = String(obsCount);
            if (notesField) notesField.style.display = '';
        } else {
            if (banner) banner.style.display = 'none';
            var notesTextarea = document.getElementById('notes_for_under_observation');
            if (notesField && !(notesTextarea && notesTextarea.value.trim())) {
                notesField.style.display = 'none';
            }
        }

        rebuildOutcomesTable(animals, outcomes);
        toggleInspectionSections(animals, meta);
        preserveExistingOutcomes = false;
    }

    function refreshAnimalsForSpecies() {
        var planSelect = document.querySelector('[name="slaughter_plan_id"]:not([type="hidden"])');
        if (!planSelect || !planSelect.value) {
            if (typeof window.renderAnteMortemChecklist === 'function') {
                window.renderAnteMortemChecklist();
            }
            return;
        }

        var meta = window.anteMortemPlanMeta[planSelect.value]
            || window.anteMortemPlanMeta[String(planSelect.value)]
            || null;
        var animals = filterAnimalsBySpecies(planAnimals(planSelect.value));

        rebuildOutcomesTable(animals, existingInspectionOutcomes);
        toggleInspectionSections(animals, meta);
    }

    function isSectionHidden(section) {
        if (!section) {
            return true;
        }

        return section.classList.contains('hidden') || section.style.display === 'none';
    }

    function prepareAnteMortemFormForSubmit(form) {
        syncAggregateCounts();

        var checklistSection = document.getElementById('inspection-checklist-section');
        if (checklistSection && isSectionHidden(checklistSection)) {
            var checklistBody = document.getElementById('checklist-body');
            if (checklistBody) {
                checklistBody.innerHTML = '';
            }
            checklistSection.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.removeAttribute('required');
                field.disabled = true;
            });
        }

        var aggregateSection = document.getElementById('aggregate-counts-section');
        if (aggregateSection && isSectionHidden(aggregateSection)) {
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

        var form = button.closest('#ante-mortem-form, #ante-mortem-edit-form');
        if (form) {
            prepareAnteMortemFormForSubmit(form);
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        var planSelect = document.querySelector('[name="slaughter_plan_id"]:not([type="hidden"])');
        var speciesSelect = document.getElementById('species');

        if (planSelect) {
            planSelect.addEventListener('change', function () {
                preserveExistingOutcomes = false;
                updateFromPlan(this);
            });

            if (planSelect.value) {
                updateFromPlan(planSelect);
            } else {
                var container = document.getElementById('per-animal-outcomes-container');
                if (container && container.querySelectorAll('select[name*="[outcome]"]').length > 0) {
                    container.querySelectorAll('select[name*="[outcome]"]').forEach(function (select) {
                        select.addEventListener('change', syncAggregateCounts);
                    });
                    toggleAggregateCountsSection(
                        Array.from(container.querySelectorAll('input[name*="[animal_intake_item_id]"]')).map(function () {
                            return {};
                        })
                    );
                    toggleInspectionSections(
                        Array.from(container.querySelectorAll('input[name*="[animal_intake_item_id]"]')),
                        null
                    );
                }
            }
        }

        if (speciesSelect) {
            speciesSelect.addEventListener('change', refreshAnimalsForSpecies);
        }
    });
}());
</script>
@endpush
