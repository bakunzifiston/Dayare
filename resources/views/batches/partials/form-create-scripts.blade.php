@push('scripts')
<script>
(function () {
    'use strict';

    var sameDayBatchData = @json($sameDayBatchData ?? []);

    function dayKey(facilityId, slaughterDate) {
        return String(facilityId) + '|' + slaughterDate;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateSummary() {
        var checkboxes = document.querySelectorAll('.animal-checkbox:checked:not([disabled])');
        var count = checkboxes.length;
        var totalKg = 0;
        checkboxes.forEach(function (cb) { totalKg += parseFloat(cb.dataset.meatKg || 0); });
        var countEl = document.getElementById('selected-animal-count');
        var yieldEl = document.getElementById('selected-yield');
        var qtyInput = document.getElementById('quantity');
        if (countEl) countEl.textContent = String(count);
        if (yieldEl) yieldEl.textContent = totalKg.toFixed(2);
        if (qtyInput && totalKg > 0) qtyInput.value = totalKg.toFixed(2);
    }

    function attachSelectorHandlers() {
        var selectAll = document.getElementById('select-all-animals');
        var deselectAll = document.getElementById('deselect-all-animals');
        if (selectAll) {
            selectAll.onclick = function () {
                document.querySelectorAll('.animal-checkbox:not([disabled])')
                    .forEach(function (cb) { cb.checked = true; });
                updateSummary();
            };
        }
        if (deselectAll) {
            deselectAll.onclick = function () {
                document.querySelectorAll('.animal-checkbox:not([disabled])')
                    .forEach(function (cb) { cb.checked = false; });
                updateSummary();
            };
        }
    }

    function buildAnimalSelector(dayData) {
        var wrapper = document.getElementById('animal-selector-wrapper');
        var summary = document.getElementById('same-day-summary');
        var summaryText = document.getElementById('same-day-summary-text');
        var executionInput = document.getElementById('slaughter_execution_id');
        var speciesInput = document.getElementById('species_display');
        var qtyInput = document.getElementById('quantity');

        if (!wrapper) return;

        if (!dayData || !dayData.items || !dayData.items.length) {
            wrapper.innerHTML = '<p class="text-sm text-gray-400">' + @json(__('No completed slaughter executions with per-animal data were found for this facility and date.')) + '</p>';
            if (summary) summary.classList.add('hidden');
            if (executionInput) executionInput.value = '';
            if (speciesInput) speciesInput.value = '';
            if (qtyInput) qtyInput.value = '';
            return;
        }

        if (summary) summary.classList.remove('hidden');
        if (summaryText) {
            summaryText.innerHTML = String(dayData.execution_count) + ' '
                + (dayData.execution_count === 1
                    ? @json(__('completed slaughter execution on this day'))
                    : @json(__('completed slaughter executions on this day')))
                + ' — <strong>' + dayData.available_animal_count + '</strong> '
                + @json(__('animals available'));
            if (dayData.available_meat_kg > 0) {
                summaryText.innerHTML += ' (' + Number(dayData.available_meat_kg).toFixed(2) + ' kg)';
            }
        }

        if (executionInput) executionInput.value = String(dayData.primary_execution_id || '');
        if (speciesInput) speciesInput.value = dayData.species || '';
        if (qtyInput && dayData.available_meat_kg > 0) {
            qtyInput.value = Number(dayData.available_meat_kg).toFixed(2);
        }

        var rows = dayData.items.map(function (animal) {
            var alreadyBatched = !!animal.already_batched;
            var liveWeight = animal.live_weight_kg ? Number(animal.live_weight_kg).toFixed(2) + ' kg' : '—';
            var legacy = String(animal.ear_tag || '').startsWith('LEGACY-')
                ? '<span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>' : '';
            var alreadyBadge = alreadyBatched
                ? '<span class="ml-1 text-xs text-amber-600 bg-amber-50 px-1 rounded">' + @json(__('Already batched')) + '</span>' : '';

            return '<tr class="border-t border-gray-100' + (alreadyBatched ? ' opacity-50' : '') + '">'
                + '<td class="py-1 px-2"><input type="checkbox" name="selected_animal_ids[]" value="' + animal.animal_id + '"'
                + ' class="animal-checkbox" data-meat-kg="' + animal.meat_quantity_kg + '" data-execution-id="' + animal.execution_id + '"'
                + (alreadyBatched ? ' disabled' : ' checked') + '></td>'
                + '<td class="py-1 px-2 text-xs text-slate-500">' + escapeHtml(animal.session_label) + '</td>'
                + '<td class="py-1 px-2 font-mono text-xs">' + escapeHtml(animal.ear_tag) + legacy + '</td>'
                + '<td class="py-1 px-2">' + escapeHtml(animal.species) + '</td>'
                + '<td class="py-1 px-2">' + escapeHtml(animal.sex) + '</td>'
                + '<td class="py-1 px-2">' + liveWeight + '</td>'
                + '<td class="py-1 px-2">' + Number(animal.meat_quantity_kg).toFixed(2) + ' kg' + alreadyBadge + '</td>'
                + '</tr>';
        }).join('');

        wrapper.innerHTML =
            '<div class="border border-gray-200 rounded-md">'
            + '<div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">'
            + '<p class="text-sm font-medium text-gray-700">' + @json(__('Select animals for this batch')) + '</p>'
            + '<div class="flex gap-3 text-xs">'
            + '<button type="button" id="select-all-animals" class="text-blue-600 hover:underline">' + @json(__('Select all')) + '</button>'
            + '<button type="button" id="deselect-all-animals" class="text-gray-500 hover:underline">' + @json(__('Deselect all')) + '</button>'
            + '</div></div>'
            + '<div class="p-3">'
            + '<div id="animal-selection-summary" class="mb-3 text-sm text-gray-600">'
            + '<span id="selected-animal-count">0</span> ' + @json(__('animals selected'))
            + ' — ' + @json(__('estimated yield')) + ': <strong><span id="selected-yield">0.00</span> kg</strong>'
            + '</div>'
            + '<table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500">'
            + '<th class="pb-1 px-2 w-8"></th>'
            + '<th class="pb-1 px-2">' + @json(__('Session')) + '</th>'
            + '<th class="pb-1 px-2">' + @json(__('Ear tag')) + '</th>'
            + '<th class="pb-1 px-2">' + @json(__('Species')) + '</th>'
            + '<th class="pb-1 px-2">' + @json(__('Sex')) + '</th>'
            + '<th class="pb-1 px-2">' + @json(__('Live weight')) + '</th>'
            + '<th class="pb-1 px-2">' + @json(__('Meat qty (execution)')) + '</th>'
            + '</tr></thead><tbody>' + rows + '</tbody></table>'
            + '</div></div>';

        attachSelectorHandlers();
        updateSummary();
    }

    function filterInspectors() {
        var facilitySelect = document.getElementById('facility_id');
        var inspectorSelect = document.getElementById('inspector_id');
        if (!facilitySelect || !inspectorSelect) return;

        var facilityId = facilitySelect.value;
        Array.from(inspectorSelect.options).forEach(function (opt) {
            if (opt.value === '') {
                opt.textContent = facilityId ? @json(__('Select inspector')) : @json(__('Select facility first'));
                opt.hidden = false;
                return;
            }
            opt.hidden = opt.dataset.facilityId !== facilityId;
        });

        if (!facilityId) {
            inspectorSelect.value = '';
        }
    }

    function updateFromDaySelection() {
        var facilitySelect = document.getElementById('facility_id');
        var dateInput = document.getElementById('slaughter_date');
        if (!facilitySelect || !dateInput || !facilitySelect.value || !dateInput.value) {
            buildAnimalSelector(null);
            return;
        }

        var data = sameDayBatchData[dayKey(facilitySelect.value, dateInput.value)] || null;
        buildAnimalSelector(data);
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('animal-checkbox')) {
            updateSummary();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var facilitySelect = document.getElementById('facility_id');
        var dateInput = document.getElementById('slaughter_date');

        if (facilitySelect) {
            facilitySelect.addEventListener('change', function () {
                filterInspectors();
                updateFromDaySelection();
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', updateFromDaySelection);
        }

        filterInspectors();
        if (!document.querySelector('.animal-checkbox')) {
            updateFromDaySelection();
        } else {
            attachSelectorHandlers();
            updateSummary();
        }
    });
}());
</script>
@endpush
