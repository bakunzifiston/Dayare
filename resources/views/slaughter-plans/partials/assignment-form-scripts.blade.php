@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const facilitySelect = document.getElementById('facility_id');
    const inspectorSelect = document.getElementById('inspector_id');
    const intakeSelect = document.getElementById('animal_intake_id');
    const speciesSelect = document.getElementById('species');
    const countInput = document.getElementById('number_of_animals_scheduled');
    const previewPanel = document.getElementById('animal-preview-panel');

    let currentIntakeAnimals = [];
    let debounceTimer = null;
    const defaultSpeciesHtml = speciesSelect ? speciesSelect.innerHTML : '';

    @if (isset($createForm) && $createForm)
    const oldFacilityId = @json(old('facility_id', request('facility_id')));
    const oldInspectorId = @json(old('inspector_id'));
    const oldIntakeId = @json(old('animal_intake_id', request('animal_intake_id')));
    @endif

    function capitalize(str) {
        if (!str) {
            return '';
        }

        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function healthBadgeClass(status) {
        if (status === 'healthy') {
            return 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800';
        }
        if (status === 'under_observation') {
            return 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800';
        }

        return 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800';
    }

    function hidePreviewPanel() {
        if (previewPanel) {
            previewPanel.style.display = 'none';
        }
    }

    function parseIntakeAnimals(option) {
        if (!option || !option.value) {
            currentIntakeAnimals = [];

            return null;
        }

        try {
            const animals = JSON.parse(option.getAttribute('data-animals') || '[]');
            currentIntakeAnimals = Array.isArray(animals) ? animals : [];

            return currentIntakeAnimals;
        } catch (e) {
            currentIntakeAnimals = [];
            hidePreviewPanel();

            return null;
        }
    }

    function buildSpeciesCountMap(animals) {
        return animals.reduce(function (map, animal) {
            if (animal.species) {
                map[animal.species] = (map[animal.species] || 0) + 1;
            }

            return map;
        }, {});
    }

    function getIntakeReference(option) {
        if (!option) {
            return 'this intake';
        }

        const text = option.textContent.trim();
        const separatorIndex = text.indexOf(' — ');

        return separatorIndex >= 0 ? text.substring(0, separatorIndex).trim() : text;
    }

    function refreshPreviewPanel() {
        if (!previewPanel) {
            return;
        }

        if (!intakeSelect || !intakeSelect.value || currentIntakeAnimals.length === 0) {
            hidePreviewPanel();

            return;
        }

        const species = speciesSelect ? speciesSelect.value : '';
        const count = parseInt(countInput ? countInput.value : '0', 10) || 0;
        const availableAnimals = currentIntakeAnimals.filter(function (animal) {
            return animal.species === species;
        });
        const available = availableAnimals.length;
        const take = Math.min(Math.max(0, count), available);
        const selectedAnimals = availableAnimals.slice(0, take);
        const option = intakeSelect.options[intakeSelect.selectedIndex];
        const intakeReference = getIntakeReference(option);

        let html = '<p class="text-sm font-semibold text-slate-800 mb-3">'
            + take + ' ' + species + ' animals will be assigned from ' + intakeReference
            + '</p>';

        if (count > available) {
            html += '<div class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">'
                + 'Only ' + available + ' ' + species + ' animals available — showing all ' + available
                + '</div>';
        }

        html += '<div class="overflow-x-auto rounded-lg border border-slate-200"><table class="min-w-full text-sm">';
        html += '<thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr>';
        html += '<th class="px-3 py-2">Ear tag</th>';
        html += '<th class="px-3 py-2">Sex</th>';
        html += '<th class="px-3 py-2">Age</th>';
        html += '<th class="px-3 py-2">Weight</th>';
        html += '<th class="px-3 py-2">Body condition</th>';
        html += '<th class="px-3 py-2">Health status</th>';
        html += '</tr></thead><tbody class="divide-y divide-slate-100">';

        selectedAnimals.forEach(function (animal) {
            const earTag = animal.ear_tag || '—';
            const legacyBadge = earTag.indexOf('LEGACY-') === 0
                ? ' <span class="ml-1 inline-flex items-center rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">[legacy]</span>'
                : '';
            const sex = animal.sex
                ? capitalize(String(animal.sex).toLowerCase())
                : '—';
            const age = animal.age_months != null ? animal.age_months + ' months' : '—';
            const weight = animal.live_weight_kg != null ? animal.live_weight_kg + ' kg' : '—';
            const bodyCondition = animal.body_condition_score
                ? capitalize(String(animal.body_condition_score))
                : '—';
            const statusLabel = animal.health_status_label || capitalize(String(animal.health_status || '').replace(/_/g, ' '));
            const observationNote = animal.health_status === 'under_observation'
                ? ' <small class="text-amber-700 ml-1">Under observation — will be reviewed at ante-mortem</small>'
                : '';

            html += '<tr>';
            html += '<td class="px-3 py-2 font-mono text-xs">' + earTag + legacyBadge + '</td>';
            html += '<td class="px-3 py-2">' + sex + '</td>';
            html += '<td class="px-3 py-2">' + age + '</td>';
            html += '<td class="px-3 py-2">' + weight + '</td>';
            html += '<td class="px-3 py-2">' + bodyCondition + '</td>';
            html += '<td class="px-3 py-2"><span class="' + healthBadgeClass(animal.health_status) + '">' + statusLabel + '</span>' + observationNote + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        previewPanel.innerHTML = html;
        previewPanel.style.display = '';
    }

    function onSpeciesChange() {
        const species = speciesSelect ? speciesSelect.value : '';

        if (countInput && currentIntakeAnimals.length > 0 && species) {
            const available = currentIntakeAnimals.filter(function (animal) {
                return animal.species === species;
            }).length;
            countInput.setAttribute('max', String(available));
        } else if (countInput) {
            countInput.removeAttribute('max');
        }

        refreshPreviewPanel();
    }

    function onIntakeChange() {
        if (!intakeSelect) {
            return;
        }

        const option = intakeSelect.options[intakeSelect.selectedIndex];
        const animals = parseIntakeAnimals(option);

        if (!animals || animals.length === 0) {
            if (speciesSelect) {
                const previousSpecies = speciesSelect.value;
                speciesSelect.innerHTML = defaultSpeciesHtml;
                if (previousSpecies) {
                    speciesSelect.value = previousSpecies;
                }
            }
            if (countInput) {
                countInput.removeAttribute('max');
            }
            hidePreviewPanel();

            return;
        }

        const speciesMap = buildSpeciesCountMap(animals);
        const previousSpecies = speciesSelect ? speciesSelect.value : '';

        if (speciesSelect) {
            speciesSelect.innerHTML = '';
            Object.keys(speciesMap).sort().forEach(function (species) {
                const opt = document.createElement('option');
                opt.value = species;
                opt.textContent = species + ' (' + speciesMap[species] + ' available)';
                speciesSelect.appendChild(opt);
            });

            if (previousSpecies && speciesMap[previousSpecies]) {
                speciesSelect.value = previousSpecies;
            }
        }

        if (speciesSelect) {
            speciesSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function filterByFacility(select, dataAttr) {
        if (!select || !facilitySelect) {
            return;
        }

        const facilityId = facilitySelect.value;
        const placeholder = select.options[0];

        Array.from(select.options).forEach(function (opt, index) {
            if (index === 0) {
                opt.hidden = false;
                opt.disabled = false;
                opt.textContent = facilityId
                    ? @json(__('Select animal intake'))
                    : @json(__('Select facility first'));

                return;
            }

            const matches = opt.getAttribute(dataAttr) === facilityId;
            opt.hidden = !matches;
            opt.disabled = !matches;
        });

        const current = select.options[select.selectedIndex];
        if (!facilityId || (current && (current.hidden || current.disabled))) {
            const visible = Array.from(select.options).find(function (o, index) {
                return index > 0 && o.value && !o.hidden && !o.disabled;
            });
            select.value = visible ? visible.value : '';
        }
    }

    function filterInspectors() {
        const facilityId = facilitySelect && facilitySelect.value;

        if (!inspectorSelect) {
            return;
        }

        Array.from(inspectorSelect.options).forEach(function (opt) {
            if (opt.value === '') {
                @if (isset($createForm) && $createForm)
                opt.textContent = facilityId ? @json(__('Select inspector')) : @json(__('Select facility first'));
                @else
                opt.textContent = @json(__('Select inspector'));
                @endif
                opt.hidden = false;

                return;
            }

            opt.hidden = opt.dataset.facilityId !== facilityId;
        });

        @if (isset($createForm) && $createForm)
        inspectorSelect.value = facilityId && oldFacilityId === facilityId ? oldInspectorId : '';
        @else
        const currentOpt = inspectorSelect.options[inspectorSelect.selectedIndex];
        if (currentOpt && currentOpt.hidden) {
            const visible = Array.from(inspectorSelect.options).find(function (o) {
                return o.value && !o.hidden;
            });
            inspectorSelect.value = visible ? visible.value : '';
        }
        @endif
    }

    function filterIntakes() {
        filterByFacility(intakeSelect, 'data-facility-id');
    }

    if (facilitySelect) {
        facilitySelect.addEventListener('change', function () {
            filterInspectors();
            filterIntakes();
            if (intakeSelect) {
                intakeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    if (intakeSelect) {
        intakeSelect.addEventListener('change', onIntakeChange);
    }

    if (speciesSelect) {
        speciesSelect.addEventListener('change', onSpeciesChange);
    }

    if (countInput) {
        countInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(refreshPreviewPanel, 300);
        });
    }

    filterInspectors();
    filterIntakes();

    @if (isset($createForm) && $createForm)
    if (intakeSelect && oldIntakeId) {
        const match = Array.from(intakeSelect.options).find(function (opt) {
            return opt.value === String(oldIntakeId) && !opt.hidden && !opt.disabled;
        });
        if (match) {
            intakeSelect.value = match.value;
        }
    }
    @endif

    if (intakeSelect && intakeSelect.value) {
        intakeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
});
</script>
@endpush
