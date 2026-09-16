@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
    $oldLines = old('lines');
    if (! is_array($oldLines) || $oldLines === []) {
        $seedMeat = $selectedPo?->meat_type ?? ($meatTypes[0] ?? 'beef');
        $seedWeight = $selectedPo?->requested_weight_kg;
        $oldLines = [[
            'meat_type' => $seedMeat,
            'expected_weight_kg' => $seedWeight,
            'received_weight_kg' => $seedWeight,
            'temperature_c' => null,
            'condition_notes' => null,
            'outcome' => 'accepted',
            'accepted_weight_kg' => $seedWeight,
            'rejected_weight_kg' => 0,
            'unit_cost' => null,
        ]];
    }
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.receiving.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Receiving') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-package text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Receive delivery') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Record inbound stock from a supplier.') }}</p>
                    </div>
                </div>
            </div>

            <x-butcher.hygiene-advisory-banner :banner="$hygieneBanner" />

            <form
                id="receiving-form"
                method="post"
                action="{{ route('butcher.receiving.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
                data-submit-once
                onsubmit="return confirm(@js(__('Post this delivery? Accepted quantities will create inventory batches and cannot be undone from this screen.')))"
            >
                @csrf

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Delivery') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="purchase_order_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Purchase order') }}</label>
                                <select id="purchase_order_id" name="purchase_order_id" class="{{ $fieldClass }}">
                                    <option value="">{{ __('None — ad-hoc delivery') }}</option>
                                    @foreach ($openOrders as $po)
                                        <option
                                            value="{{ $po->id }}"
                                            data-supplier="{{ $po->supplier_id }}"
                                            data-meat="{{ $po->meat_type }}"
                                            data-weight="{{ $po->requested_weight_kg }}"
                                            @selected((string) old('purchase_order_id', $selectedPo?->id) === (string) $po->id)
                                        >
                                            {{ $po->po_number }} — {{ $po->supplier?->name }} — {{ ucfirst($po->meat_type) }} ({{ number_format((float) $po->requested_weight_kg, 2) }} kg)
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('purchase_order_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="supplier_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Supplier') }}</label>
                                <select id="supplier_id" name="supplier_id" required class="{{ $fieldClass }}">
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedPo?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Receiving outlet') }}</label>
                                <select id="outlet_id" name="outlet_id" required class="{{ $fieldClass }}">
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Delivery lines') }}</h3>
                            <button type="button" id="add-line" class="inline-flex items-center gap-1 text-sm font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                {{ __('Add line') }}
                            </button>
                        </div>

                        <div id="lines-container" class="space-y-4">
                            @foreach ($oldLines as $i => $line)
                                @include('butcher.receiving.partials.line-fields', ['index' => $i, 'line' => $line, 'meatTypes' => $meatTypes, 'outcomes' => $outcomes, 'fieldClass' => $fieldClass])
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('lines')" class="mt-2" />

                        <div class="grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm">
                            <p class="text-slate-600">{{ __('Accepted') }}: <span id="summary-accepted" class="font-semibold tabular-nums text-slate-900">0.000</span> kg</p>
                            <p class="text-slate-600">{{ __('Rejected') }}: <span id="summary-rejected" class="font-semibold tabular-nums text-slate-900">0.000</span> kg</p>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Certificate & notes') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="certificate_ref" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Certificate reference') }}</label>
                                <input id="certificate_ref" name="certificate_ref" type="text" maxlength="100" value="{{ old('certificate_ref') }}" class="{{ $fieldClass }}" placeholder="CERT-2026-00123">
                                <x-input-error :messages="$errors->get('certificate_ref')" class="mt-2" />
                            </div>
                            <div>
                                <label for="certificate_issuer" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Certificate issuer') }}</label>
                                <input id="certificate_issuer" name="certificate_issuer" type="text" value="{{ old('certificate_issuer') }}" class="{{ $fieldClass }}" placeholder="RFA">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.receiving.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" id="receiving-submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-check text-base leading-none" aria-hidden="true"></i>
                        {{ __('Post delivery') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="line-template">
        @include('butcher.receiving.partials.line-fields', ['index' => '__INDEX__', 'line' => [], 'meatTypes' => $meatTypes, 'outcomes' => $outcomes, 'fieldClass' => $fieldClass])
    </template>

    <script>
        (function () {
            const container = document.getElementById('lines-container');
            const template = document.getElementById('line-template');
            const addBtn = document.getElementById('add-line');
            const form = document.getElementById('receiving-form');
            const poSelect = document.getElementById('purchase_order_id');
            const supplierSelect = document.getElementById('supplier_id');
            let lineIndex = container.querySelectorAll('[data-line]').length;

            function num(el, fallback = 0) {
                const v = parseFloat(el?.value);
                return Number.isFinite(v) ? v : fallback;
            }

            function syncOutcomeWeights(row) {
                const outcome = row.querySelector('[data-field="outcome"]')?.value;
                const received = num(row.querySelector('[data-field="received_weight_kg"]'));
                const acceptedInput = row.querySelector('[data-field="accepted_weight_kg"]');
                const rejectedInput = row.querySelector('[data-field="rejected_weight_kg"]');
                if (!acceptedInput || !rejectedInput) return;

                if (outcome === 'accepted') {
                    acceptedInput.value = received || '';
                    rejectedInput.value = '0';
                    acceptedInput.readOnly = true;
                    rejectedInput.readOnly = true;
                } else if (outcome === 'rejected') {
                    acceptedInput.value = '0';
                    rejectedInput.value = received || '';
                    acceptedInput.readOnly = true;
                    rejectedInput.readOnly = true;
                } else {
                    acceptedInput.readOnly = false;
                    rejectedInput.readOnly = false;
                    if (!acceptedInput.value && received) {
                        acceptedInput.value = (received / 2).toFixed(3);
                        rejectedInput.value = (received - num(acceptedInput)).toFixed(3);
                    }
                }
            }

            function updateSummary() {
                let accepted = 0;
                let rejected = 0;
                container.querySelectorAll('[data-line]').forEach((row) => {
                    accepted += num(row.querySelector('[data-field="accepted_weight_kg"]'));
                    rejected += num(row.querySelector('[data-field="rejected_weight_kg"]'));
                });
                document.getElementById('summary-accepted').textContent = accepted.toFixed(3);
                document.getElementById('summary-rejected').textContent = rejected.toFixed(3);
            }

            function bindRow(row) {
                row.querySelectorAll('[data-field]').forEach((el) => {
                    el.addEventListener('input', () => {
                        if (el.dataset.field === 'outcome' || el.dataset.field === 'received_weight_kg') {
                            syncOutcomeWeights(row);
                        }
                        updateSummary();
                    });
                    el.addEventListener('change', () => {
                        if (el.dataset.field === 'outcome' || el.dataset.field === 'received_weight_kg') {
                            syncOutcomeWeights(row);
                        }
                        updateSummary();
                    });
                });
                row.querySelector('[data-remove-line]')?.addEventListener('click', () => {
                    if (container.querySelectorAll('[data-line]').length <= 1) return;
                    row.remove();
                    updateSummary();
                });
                syncOutcomeWeights(row);
            }

            container.querySelectorAll('[data-line]').forEach(bindRow);
            updateSummary();

            addBtn?.addEventListener('click', () => {
                const html = template.innerHTML.replaceAll('__INDEX__', String(lineIndex++));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const row = wrap.firstElementChild;
                container.appendChild(row);
                bindRow(row);
                updateSummary();
            });

            poSelect?.addEventListener('change', () => {
                const opt = poSelect.selectedOptions[0];
                if (!opt || !opt.value) return;
                if (opt.dataset.supplier) supplierSelect.value = opt.dataset.supplier;
                const first = container.querySelector('[data-line]');
                if (!first) return;
                const meat = first.querySelector('[data-field="meat_type"]');
                const expected = first.querySelector('[data-field="expected_weight_kg"]');
                const received = first.querySelector('[data-field="received_weight_kg"]');
                if (meat && opt.dataset.meat) meat.value = opt.dataset.meat;
                if (expected && opt.dataset.weight) expected.value = opt.dataset.weight;
                if (received && opt.dataset.weight && !received.value) received.value = opt.dataset.weight;
                syncOutcomeWeights(first);
                updateSummary();
            });

            form?.addEventListener('submit', () => {
                const btn = document.getElementById('receiving-submit');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = @json(__('Posting…'));
                }
            });
        })();
    </script>
</x-app-layout>
