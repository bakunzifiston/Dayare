@php
    $existingExecutionItems = isset($executionItems)
        ? $executionItems->mapWithKeys(fn ($item) => [
            $item->animal_intake_item_id => [
                'meat_quantity_kg' => $item->meat_quantity_kg,
                'notes' => $item->notes,
            ],
        ])->all()
        : [];

    $slaughterExecutionPlanData = [];
    foreach ($plans ?? [] as $plan) {
        $planId = $plan['id'];
        $slaughterExecutionPlanData[$planId] = [
            'approved_items' => $approvedItemsByPlan->get($planId, $approvedItemsByPlan[$planId] ?? []),
            'slaughtered_ids' => $slaughteredItemIdsByPlan->get($planId, $slaughteredItemIdsByPlan[$planId] ?? []),
            'slaughtered_details' => $slaughteredDetailsByPlan->get($planId, $slaughteredDetailsByPlan[$planId] ?? []),
            'am_date' => $amDateByPlan->get($planId, $amDateByPlan[$planId] ?? ''),
            'continue_edit_url' => $plan['continue_edit_url'] ?? null,
        ];
    }
@endphp

@push('scripts')
<script>
(function () {
    'use strict';

    var currentApprovedAnimals = [];
    var currentSlaughteredIds = [];
    var currentSlaughteredDetails = {};
    var currentExecutionIds = @json($currentExecutionItemIds ?? []);
    var existingExecutionItems = @json($existingExecutionItems);
    var slaughterExecutionPlanData = @json($slaughterExecutionPlanData);
    var isCreateForm = document.querySelector('[data-form-mode="create"]') !== null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function planDataForId(planId) {
        if (!planId) {
            return {};
        }

        return slaughterExecutionPlanData[planId] || slaughterExecutionPlanData[String(planId)] || {};
    }

    function computeDeadline(amDateStr) {
        if (!amDateStr) return null;
        var d = new Date(amDateStr + 'T23:59:59');
        d.setDate(d.getDate() + 1);
        return d;
    }

    function checkGate() {
        var planSelect = document.querySelector('[name="slaughter_plan_id"]:not([type="hidden"])');
        var timeInput = document.getElementById('slaughter_time');
        var warningEl = document.getElementById('am-gate-warning');
        if (!planSelect || !timeInput || !warningEl) return;

        var planData = planDataForId(planSelect.value);
        var amDate = planData.am_date || '';
        var deadline = computeDeadline(amDate);

        if (!deadline || !timeInput.value) {
            warningEl.style.display = 'none';
            return;
        }

        var slaughterTime = new Date(timeInput.value);
        var amDayStart = new Date(amDate + 'T00:00:00');
        var amEndOfDay = new Date(amDate + 'T23:59:59');
        var diffHours = (slaughterTime - amEndOfDay) / 3600000;

        if (slaughterTime < amDayStart) {
            warningEl.className = 'mt-2 rounded p-2 text-sm bg-red-50 border border-red-200 text-red-800';
            warningEl.textContent = @json(__('Slaughter time cannot be before the ante-mortem inspection date.'));
            warningEl.style.display = '';
        } else if (diffHours > 24) {
            warningEl.className = 'mt-2 rounded p-2 text-sm bg-amber-50 border border-amber-200 text-amber-900';
            warningEl.textContent = @json(__('This slaughter time exceeds the 24-hour ante-mortem window. This will be noted on the execution report.'));
            warningEl.style.display = '';
        } else if (diffHours > 20) {
            warningEl.className = 'mt-2 rounded p-2 text-sm bg-yellow-50 border border-yellow-200 text-yellow-800';
            warningEl.textContent = @json(__('Ante-mortem window closing — less than')) + ' ' + Math.ceil(24 - diffHours) + ' ' + @json(__('hour(s) remaining.'));
            warningEl.style.display = '';
        } else if (amDate) {
            warningEl.className = 'mt-2 rounded p-2 text-sm bg-green-50 border border-green-200 text-green-800';
            warningEl.textContent = @json(__('Within ante-mortem window.'));
            warningEl.style.display = '';
        } else {
            warningEl.style.display = 'none';
        }
    }

    function defaultMeatQuantity(animal) {
        if (animal.live_weight_kg && Number(animal.live_weight_kg) > 0) {
            return (Number(animal.live_weight_kg) * 0.5).toFixed(2);
        }

        return '';
    }

    function isAlreadySlaughtered(animalId) {
        return currentSlaughteredIds.indexOf(Number(animalId)) !== -1;
    }

    function recordedMeatQuantity(animalId, detail) {
        var existing = existingExecutionItems[animalId] || {};

        return existing.meat_quantity_kg || detail.meat_quantity_kg || '';
    }

    function recordedNotes(animalId) {
        return (existingExecutionItems[animalId] || {}).notes || '';
    }

    function updateYieldTotal() {
        var total = 0;
        var newCount = 0;

        document.querySelectorAll('.slaughter-recorded-inputs [data-field="meat_quantity_kg"]').forEach(function (input) {
            var value = parseFloat(input.value);
            if (!isNaN(value) && value > 0) {
                total += value;
            }
        });

        document.querySelectorAll('.slaughter-animal-card').forEach(function (card) {
            var checkbox = card.querySelector('.slaughter-animal-checkbox');
            var qtyInput = card.querySelector('.slaughter-meat-qty');
            if (!checkbox || !checkbox.checked || !qtyInput) return;

            var value = parseFloat(qtyInput.value);
            if (!isNaN(value) && value > 0) {
                total += value;
                newCount++;
            }
        });

        var recordedCount = document.querySelectorAll('.slaughter-recorded-row').length;
        var selectedPending = document.querySelectorAll('.slaughter-animal-checkbox:checked').length;
        var totalCount = recordedCount + selectedPending;

        var recordedEl = document.getElementById('slaughter-session-recorded-count');
        var approvedEl = document.getElementById('slaughter-session-approved-count');
        var pendingEl = document.getElementById('slaughter-session-pending-count');
        var approvedCount = currentApprovedAnimals.length || (recordedCount + document.querySelectorAll('.slaughter-animal-card').length);
        if (recordedEl) recordedEl.textContent = String(totalCount);
        if (approvedEl) approvedEl.textContent = String(approvedCount);
        if (pendingEl) pendingEl.textContent = String(Math.max(0, approvedCount - totalCount));

        var summary = document.getElementById('yield-summary');
        var totalEl = document.getElementById('yield-total');
        var countEl = document.getElementById('yield-count');
        if (summary) summary.style.display = totalCount > 0 ? '' : 'none';
        if (totalEl) totalEl.textContent = total.toFixed(2);
        if (countEl) countEl.textContent = String(newCount > 0 ? newCount : totalCount);

        var countInput = document.getElementById('actual_animals_slaughtered');
        if (countInput) countInput.value = String(totalCount);

        var statusSelect = document.getElementById('status');
        if (statusSelect && totalCount > 0) {
            var approvedForStatus = currentApprovedAnimals.length;
            if (approvedForStatus > 0 && totalCount < approvedForStatus) {
                statusSelect.value = 'in_progress';
            } else if (approvedForStatus > 0 && totalCount >= approvedForStatus) {
                statusSelect.value = 'completed';
            } else if (isCreateForm) {
                statusSelect.value = 'completed';
            }
        }
    }

    function toggleAnimalCard(card, checked) {
        var fields = card.querySelector('.slaughter-animal-fields');
        var hiddenId = card.querySelector('.slaughter-animal-id');
        var qtyInput = card.querySelector('.slaughter-meat-qty');
        var notesInput = card.querySelector('.slaughter-notes');

        if (fields) fields.classList.toggle('hidden', !checked);
        [hiddenId, qtyInput, notesInput].forEach(function (field) {
            if (!field) return;
            field.disabled = !checked;
            if (!checked) {
                if (field.classList.contains('slaughter-meat-qty') || field.classList.contains('slaughter-notes')) {
                    field.value = '';
                }
            }
        });

        if (checked && qtyInput && !qtyInput.value) {
            var animalId = Number(card.dataset.animalId || hiddenId?.value || 0);
            var animal = currentApprovedAnimals.find(function (row) {
                return Number(row.id) === animalId;
            });
            var existing = existingExecutionItems[animalId] || {};
            qtyInput.value = existing.meat_quantity_kg || (animal ? defaultMeatQuantity(animal) : '');
            if (notesInput) {
                notesInput.value = existing.notes || '';
            }
        }
    }

    function bindAnimalCards() {
        var container = document.getElementById('per-animal-slaughter-container');
        if (container && container.dataset.bound !== '1') {
            container.dataset.bound = '1';

            container.addEventListener('change', function (event) {
                var target = event.target;
                if (target.classList.contains('slaughter-animal-checkbox')) {
                    var card = target.closest('.slaughter-animal-card');
                    if (!card) return;
                    toggleAnimalCard(card, target.checked);
                    reindexSlaughterFields();
                    updateYieldTotal();
                    return;
                }
                if (target.classList.contains('slaughter-meat-qty')) {
                    updateYieldTotal();
                }
            });

            container.addEventListener('input', function (event) {
                if (event.target.classList.contains('slaughter-meat-qty')) {
                    updateYieldTotal();
                }
            });
        }

        reindexSlaughterFields();
        updateYieldTotal();
        autoSelectSinglePendingAnimal();
    }

    function autoSelectSinglePendingAnimal() {
        var pendingCards = document.querySelectorAll('.slaughter-animal-card');
        if (pendingCards.length !== 1) return;

        var card = pendingCards[0];
        var checkbox = card.querySelector('.slaughter-animal-checkbox');
        if (!checkbox || checkbox.checked) {
            if (checkbox && checkbox.checked) {
                toggleAnimalCard(card, true);
                reindexSlaughterFields();
                updateYieldTotal();
            }
            return;
        }

        checkbox.checked = true;
        toggleAnimalCard(card, true);
        reindexSlaughterFields();
        updateYieldTotal();
    }

    function reindexSlaughterFields() {
        var index = 0;

        document.querySelectorAll('.slaughter-recorded-inputs').forEach(function (row) {
            var idInput = row.querySelector('[data-field="animal_intake_item_id"]');
            var qtyInput = row.querySelector('[data-field="meat_quantity_kg"]');
            var notesInput = row.querySelector('[data-field="notes"]');

            if (idInput) idInput.name = 'item_slaughters[' + index + '][animal_intake_item_id]';
            if (qtyInput) qtyInput.name = 'item_slaughters[' + index + '][meat_quantity_kg]';
            if (notesInput) notesInput.name = 'item_slaughters[' + index + '][notes]';
            index++;
        });

        document.querySelectorAll('.slaughter-animal-card').forEach(function (card) {
            var checkbox = card.querySelector('.slaughter-animal-checkbox');
            if (!checkbox || !checkbox.checked) return;

            var hiddenId = card.querySelector('.slaughter-animal-id');
            var qtyInput = card.querySelector('.slaughter-meat-qty');
            var notesInput = card.querySelector('.slaughter-notes');

            if (hiddenId) hiddenId.name = 'item_slaughters[' + index + '][animal_intake_item_id]';
            if (qtyInput) qtyInput.name = 'item_slaughters[' + index + '][meat_quantity_kg]';
            if (notesInput) notesInput.name = 'item_slaughters[' + index + '][notes]';
            index++;
        });
    }

    function prepareSlaughterFormForSubmit(form) {
        reindexSlaughterFields();
        updateYieldTotal();

        document.querySelectorAll('.slaughter-animal-card').forEach(function (card) {
            var checkbox = card.querySelector('.slaughter-animal-checkbox');
            if (!checkbox || checkbox.checked) return;

            card.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = true;
            });
        });

        var manualSection = document.getElementById('manual-count-section');
        if (manualSection && manualSection.classList.contains('hidden')) {
            manualSection.querySelectorAll('[required]').forEach(function (field) {
                field.removeAttribute('required');
            });
        }
    }

    function formatMeatKg(value) {
        if (value === null || value === undefined || value === '') return '—';
        var num = Number(value);
        return isNaN(num) ? '—' : num.toFixed(2);
    }

    function asAnimalList(value) {
        if (Array.isArray(value)) {
            return value;
        }
        if (value && typeof value === 'object') {
            return Object.keys(value).map(function (key) { return value[key]; });
        }

        return [];
    }

    function rebuildSlaughterTable(animals, slaughteredIds, slaughteredDetails) {
        var container = document.getElementById('per-animal-slaughter-container');
        if (!container) return;

        currentApprovedAnimals = asAnimalList(animals);
        currentSlaughteredIds = asAnimalList(slaughteredIds).map(function (id) { return Number(id); });
        currentSlaughteredDetails = {};
        asAnimalList(slaughteredDetails).forEach(function (row) {
            currentSlaughteredDetails[Number(row.animal_intake_item_id)] = row;
        });

        if (currentApprovedAnimals.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">' + @json(__('No ante-mortem approved animals found for this plan.')) + '</p>';
            updateYieldTotal();
            toggleManualCountSection(true);
            return;
        }

        toggleManualCountSection(false);

        var slaughteredAnimals = currentApprovedAnimals.filter(function (animal) {
            return isAlreadySlaughtered(animal.id);
        });
        var pendingAnimals = currentApprovedAnimals.filter(function (animal) {
            return !isAlreadySlaughtered(animal.id);
        });
        var approvedCount = currentApprovedAnimals.length;
        var recordedCount = slaughteredAnimals.length;
        var pendingCount = pendingAnimals.length;

        var html = '<div class="mb-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">'
            + '<span id="slaughter-session-recorded-count" class="font-semibold">' + recordedCount + '</span> / '
            + '<span id="slaughter-session-approved-count">' + approvedCount + '</span> '
            + @json(__('slaughtered on this session'))
            + ' <span class="mx-1">·</span> '
            + '<span id="slaughter-session-pending-count" class="font-semibold">' + pendingCount + '</span> '
            + @json(__('remaining to record'))
            + '</div>';

        if (recordedCount > 0) {
            html += '<div class="mb-6"><h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-800">'
                + @json(__('Already slaughtered')) + ' (' + recordedCount + ')</h4>'
                + '<div class="overflow-hidden rounded-lg border border-green-200">'
                + '<table class="min-w-full divide-y divide-green-100 text-sm">'
                + '<thead class="bg-green-50"><tr>'
                + '<th class="px-3 py-2 text-left font-medium text-green-900">' + @json(__('Ear tag')) + '</th>'
                + '<th class="px-3 py-2 text-left font-medium text-green-900">' + @json(__('Animal')) + '</th>'
                + '<th class="px-3 py-2 text-left font-medium text-green-900">' + @json(__('Meat (kg)')) + '</th>'
                + '<th class="px-3 py-2 text-left font-medium text-green-900">' + @json(__('Slaughter time')) + '</th>'
                + '</tr></thead><tbody class="divide-y divide-green-50 bg-white">';

            slaughteredAnimals.forEach(function (animal) {
                var detail = currentSlaughteredDetails[Number(animal.id)] || {};
                var meatQty = recordedMeatQuantity(animal.id, detail);
                var notes = recordedNotes(animal.id);

                html += '<tr class="slaughter-recorded-row" data-animal-id="' + animal.id + '">'
                    + '<td class="px-3 py-2 font-mono text-xs"><span class="inline-flex items-center gap-2">'
                    + '<span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-800">' + @json(__('Done')) + '</span>'
                    + escapeHtml(detail.ear_tag || animal.ear_tag) + '</span></td>'
                    + '<td class="px-3 py-2 text-slate-700">' + escapeHtml(detail.species || animal.species) + ' · ' + escapeHtml(detail.sex || animal.sex) + '</td>'
                    + '<td class="px-3 py-2 text-slate-700">' + formatMeatKg(meatQty) + '</td>'
                    + '<td class="px-3 py-2 text-slate-600">' + escapeHtml(detail.slaughter_time || '—') + '</td>'
                    + '</tr>'
                    + '<tr class="hidden slaughter-recorded-inputs" aria-hidden="true"><td colspan="4">'
                    + '<input type="hidden" data-field="animal_intake_item_id" value="' + animal.id + '">'
                    + '<input type="hidden" data-field="meat_quantity_kg" value="' + escapeHtml(meatQty) + '">'
                    + '<input type="hidden" data-field="notes" value="' + escapeHtml(notes) + '">'
                    + '</td></tr>';
            });

            html += '</tbody></table></div></div>';
        }

        if (pendingCount > 0) {
            var autoSelectSingle = pendingCount === 1;
            html += '<div><h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-600">'
                + @json(__('Add slaughter now')) + ' (' + pendingCount + ')</h4>'
                + '<div class="space-y-4">';

            pendingAnimals.forEach(function (animal) {
                var meatQty = defaultMeatQuantity(animal);
                var liveWeight = animal.live_weight_kg
                    ? Number(animal.live_weight_kg).toFixed(2) + ' kg ' + @json(__('live'))
                    : '';
                var checkedAttr = autoSelectSingle ? ' checked' : '';
                var disabledAttr = autoSelectSingle ? '' : ' disabled';
                var fieldsHidden = autoSelectSingle ? '' : ' hidden';
                var qtyValue = autoSelectSingle ? meatQty : '';

                html += '<div class="overflow-hidden rounded-lg border border-slate-200 slaughter-animal-card slaughter-animal-card--pending" data-animal-id="' + animal.id + '">'
                    + '<label class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer">'
                    + '<span class="inline-flex items-center gap-2 shrink-0">'
                    + '<input type="checkbox" class="slaughter-animal-checkbox rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary"' + checkedAttr + '>'
                    + '<span class="text-xs font-medium uppercase tracking-wide text-slate-500">' + @json(__('Slaughter now')) + '</span></span>'
                    + '<div class="min-w-0 flex-1"><p class="font-mono text-sm font-medium text-slate-900">' + escapeHtml(animal.ear_tag) + '</p>'
                    + '<p class="mt-0.5 text-xs text-slate-500">' + escapeHtml(animal.species) + ' · ' + escapeHtml(animal.sex)
                    + (liveWeight ? ' · ' + liveWeight : '') + '</p></div></label>'
                    + '<div class="slaughter-animal-fields grid grid-cols-1 gap-3 p-4 sm:grid-cols-2' + fieldsHidden + '">'
                    + '<input type="hidden" class="slaughter-animal-id" value="' + animal.id + '"' + disabledAttr + '>'
                    + '<div><label class="block text-xs font-medium text-slate-600">' + @json(__('Meat quantity (kg)')) + '</label>'
                    + '<input type="number" class="slaughter-meat-qty mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"'
                    + ' value="' + escapeHtml(qtyValue) + '" min="0.1" max="9999" step="0.01" placeholder="kg"' + disabledAttr + '></div>'
                    + '<div><label class="block text-xs font-medium text-slate-600">' + @json(__('Notes (optional)')) + '</label>'
                    + '<input type="text" class="slaughter-notes mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" value=""' + disabledAttr + '></div>'
                    + '</div></div>';
            });

            html += '</div><p class="mt-3 text-xs text-slate-500">'
                + @json(__('Check the animal(s) you are slaughtering now, enter dressed weight, and save. Animals marked Done above are already recorded.'))
                + '</p></div>';
        } else if (recordedCount > 0) {
            html += '<div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">'
                + @json(__('All approved animals on this session have been slaughtered.'))
                + '</div>';
        }

        container.innerHTML = html;
        bindAnimalCards();
    }

    function toggleManualCountSection(showManual) {
        var section = document.getElementById('manual-count-section');
        var perAnimalSection = document.getElementById('per-animal-slaughter-section');
        if (section) section.classList.toggle('hidden', !showManual);
        if (perAnimalSection) perAnimalSection.classList.toggle('hidden', showManual);
    }

    function updateFromPlan(selectEl) {
        var planId = selectEl.value;
        var perAnimalSection = document.getElementById('per-animal-slaughter-section');
        var container = document.getElementById('per-animal-slaughter-container');

        if (!planId) {
            if (container) {
                container.innerHTML = '<p class="text-sm text-gray-500">' + @json(__('Select a slaughter session with ante-mortem approved animals and no execution recorded yet.')) + '</p>';
            }
            if (perAnimalSection) {
                perAnimalSection.classList.add('hidden');
            }
            toggleManualCountSection(true);
            checkGate();
            return;
        }

        var planData = planDataForId(planId);
        if (isCreateForm && planData.continue_edit_url) {
            window.location.href = planData.continue_edit_url;
            return;
        }

        rebuildSlaughterTable(
            planData.approved_items || [],
            planData.slaughtered_ids || [],
            planData.slaughtered_details || []
        );

        if (perAnimalSection && currentApprovedAnimals.length > 0) {
            perAnimalSection.classList.remove('hidden');
        }

        checkGate();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('button[type="submit"]');
        if (!button) return;

        var form = button.closest('form');
        if (!form || !form.matches('[data-slaughter-form]')) return;

        prepareSlaughterFormForSubmit(form);
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        var planSelect = document.querySelector('[name="slaughter_plan_id"]:not([type="hidden"])');
        var timeInput = document.getElementById('slaughter_time');

        bindAnimalCards();

        if (planSelect) {
            planSelect.addEventListener('change', function () { updateFromPlan(this); });
            if (planSelect.value) {
                updateFromPlan(planSelect);
            }
        } else {
            var hiddenPlan = document.querySelector('input[name="slaughter_plan_id"][type="hidden"]');
            if (hiddenPlan && hiddenPlan.value) {
                var planData = planDataForId(hiddenPlan.value);
                currentApprovedAnimals = asAnimalList(planData.approved_items || []);
                currentSlaughteredIds = asAnimalList(planData.slaughtered_ids || []).map(function (id) {
                    return Number(id);
                });
            }
        }

        if (timeInput) {
            timeInput.addEventListener('change', checkGate);
            timeInput.addEventListener('input', checkGate);
        }

        checkGate();
    });
}());
</script>
@endpush
