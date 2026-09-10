@php
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
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.receiving.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Receiving') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Receive delivery') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-butcher.hygiene-advisory-banner :banner="$hygieneBanner" />
            <form
                id="receiving-form"
                method="post"
                action="{{ route('butcher.receiving.store') }}"
                class="rounded-bucha border border-slate-200/80 bg-white p-4 sm:p-6 shadow-bucha space-y-5"
                data-submit-once
                onsubmit="return confirm(@js(__('Post this delivery? Accepted quantities will create inventory batches and cannot be undone from this screen.')))"
            >
                @csrf

                <div>
                    <x-input-label for="purchase_order_id" :value="__('Purchase order (optional)')" />
                    <select id="purchase_order_id" name="purchase_order_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="supplier_id" :value="__('Supplier')" />
                        <select id="supplier_id" name="supplier_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedPo?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="outlet_id" :value="__('Receiving outlet')" />
                        <select id="outlet_id" name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Delivery lines') }}</h3>
                        <button type="button" id="add-line" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('+ Add line') }}</button>
                    </div>

                    <div id="lines-container" class="space-y-4">
                        @foreach ($oldLines as $i => $line)
                            @include('butcher.receiving.partials.line-fields', ['index' => $i, 'line' => $line, 'meatTypes' => $meatTypes, 'outcomes' => $outcomes])
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <p>{{ __('Accepted') }}: <span id="summary-accepted" class="font-semibold">0.000</span> kg</p>
                    <p class="mt-1">{{ __('Rejected') }}: <span id="summary-rejected" class="font-semibold">0.000</span> kg</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="certificate_ref" :value="__('Certificate reference (optional)')" />
                        <x-text-input id="certificate_ref" name="certificate_ref" type="text" maxlength="100" class="mt-1 block w-full" :value="old('certificate_ref')" placeholder="e.g. CERT-2026-00123" />
                        <x-input-error :messages="$errors->get('certificate_ref')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="certificate_issuer" :value="__('Certificate issuer (optional)')" />
                        <x-text-input id="certificate_issuer" name="certificate_issuer" type="text" class="mt-1 block w-full" :value="old('certificate_issuer')" placeholder="e.g. RFA, Abattoir X" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>

                <p class="text-xs text-slate-500">{{ __('Accepted and partially accepted quantities create inventory batches. Rejected quantities are logged only — no stock or cost liability.') }}</p>

                <div class="flex justify-end">
                    <button type="submit" id="receiving-submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Post delivery') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="line-template">
        @include('butcher.receiving.partials.line-fields', ['index' => '__INDEX__', 'line' => [], 'meatTypes' => $meatTypes, 'outcomes' => $outcomes])
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
                    btn.textContent = @json(__('Posting…'));
                }
            });
        })();
    </script>
</x-app-layout>
