@php
    $lineData = $line ?? null;
    $linkContractDefault = ($invoice?->contract_id) ? 'yes' : 'no';
    $linkContractValue = old('link_contract', $linkContractDefault);
    $batchCertificateMap = $batchCertificateMap ?? [];
    $batchQuantityMap = $batchQuantityMap ?? [];
    $units = $units ?? collect();
    $clientAnimalIntakes = $clientAnimalIntakes ?? collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-input-label for="invoice_number" :value="__('Invoice number')" />
        <x-text-input id="invoice_number" name="invoice_number" type="text" class="mt-1 block w-full" :value="old('invoice_number', $invoice?->invoice_number ?? ('AR-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)))" required />
    </div>
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (['draft', 'issued', 'overdue', 'paid', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(old('status', $invoice?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="currency" :value="__('Currency')" />
        <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $invoice?->currency ?? 'RWF')" required />
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="animal_intake_id" :value="__('Client intake')" />
        <p class="text-xs text-slate-500 mt-0.5">{{ __('Animal intakes with source “client” for this business. Each line shows client name, species, and number of animals.') }}</p>
        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 block w-full rounded-lg border-slate-300" required>
            <option value="">{{ __('Select intake') }}</option>
            @foreach ($clientAnimalIntakes as $intake)
                <option value="{{ $intake->id }}" @selected((string) old('animal_intake_id', $invoice?->animal_intake_id ?? '') === (string) $intake->id)>
                    {{ $intake->labelForFinanceInvoice() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('animal_intake_id')" />
    </div>
    <div class="space-y-2">
        <x-input-label for="link_contract" :value="__('Have a contract?')" />
        <select id="link_contract" name="link_contract" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="no" @selected($linkContractValue === 'no')>{{ __('No') }}</option>
            <option value="yes" @selected($linkContractValue === 'yes')>{{ __('Yes') }}</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('link_contract')" />
        <div id="invoice-contract-select-wrap" class="@if($linkContractValue === 'yes') @else hidden @endif">
            <x-input-label for="contract_id" :value="__('Contract')" class="mt-2" />
            <select id="contract_id" name="contract_id" class="mt-1 block w-full rounded-lg border-slate-300" @if($linkContractValue !== 'yes') disabled @endif>
                <option value="">{{ __('Select contract') }}</option>
                @foreach($contracts as $contract)
                    <option value="{{ $contract->id }}" @selected((string) old('contract_id', $invoice?->contract_id ?? '') === (string) $contract->id)>{{ $contract->contract_number ?? ('#'.$contract->id) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('contract_id')" />
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
    <div>
        <x-input-label for="issued_at" :value="__('Issued at')" />
        <x-text-input id="issued_at" name="issued_at" type="datetime-local" class="mt-1 block w-full" :value="old('issued_at', ($invoice?->issued_at ?? now())->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="due_date" :value="__('Due date')" />
        <x-text-input id="due_date" name="due_date" type="datetime-local" class="mt-1 block w-full" :value="old('due_date', optional($invoice?->due_date)->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="paid_at" :value="__('Paid at')" />
        <x-text-input id="paid_at" name="paid_at" type="datetime-local" class="mt-1 block w-full" :value="old('paid_at', optional($invoice?->paid_at)->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="amount_paid" :value="__('Amount paid')" />
        <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount_paid', $invoice?->amount_paid ?? 0)" />
    </div>
</div>

<div class="mt-6 rounded-lg border border-slate-200 p-4">
    <h3 class="font-semibold text-slate-900">{{ __('Primary line item') }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
        <div class="md:col-span-2">
            <x-input-label for="line_description" :value="__('Description')" />
            <x-text-input id="line_description" name="line_description" type="text" class="mt-1 block w-full" :value="old('line_description', $lineData?->description ?? '')" required />
        </div>
        <div>
            <x-input-label for="batch_id" :value="__('Batch')" />
            <select id="batch_id" name="batch_id" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">{{ __('Select') }}</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((string) old('batch_id', $lineData?->batch_id ?? '') === (string) $batch->id)>
                        {{ $batch->batch_code ?? ('#'.$batch->id) }}@if(isset($batch->quantity) && (float) $batch->quantity > 0) — {{ $batch->quantity }}@if(filled($batch->quantity_unit)) {{ $batch->quantity_unit }}@endif @endif
                    </option>
                @endforeach
            </select>
            <p id="batch-certificate-message" class="mt-1 text-sm hidden text-amber-800" role="status"></p>
        </div>
        <div>
            <x-input-label for="certificate_select" :value="__('Certificate')" />
            <p class="text-xs text-slate-500 mt-0.5">{{ __('With a batch selected, the certificate comes from that batch. Clear the batch to pick a certificate manually.') }}</p>
            <input type="hidden" id="certificate_id_hidden" value="" disabled />
            <select id="certificate_select" name="certificate_id" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">{{ __('Select') }}</option>
                @foreach($certificates as $certificate)
                    <option value="{{ $certificate->id }}" @selected((string) old('certificate_id', $lineData?->certificate_id ?? '') === (string) $certificate->id)>{{ $certificate->certificate_number ?? ('#'.$certificate->id) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('certificate_id')" />
        </div>
        <div>
            <x-input-label for="quantity" :value="__('Quantity')" />
            <p class="text-xs text-slate-500 mt-0.5">{{ __('When a batch is selected, quantity and unit match that batch.') }}</p>
            <input type="hidden" id="quantity_hidden" value="" disabled />
            <x-text-input id="quantity" name="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" :value="old('quantity', $lineData?->quantity ?? 1)" required />
            <p id="batch-quantity-hint" class="mt-1 text-sm text-slate-600 hidden" role="status"></p>
        </div>
        <div>
            <x-input-label for="quantity_unit" :value="__('Unit')" />
            <p class="text-xs text-slate-500 mt-0.5">{{ __('When a batch is selected, the unit matches that batch. Otherwise choose a unit for the line quantity.') }}</p>
            <input type="hidden" id="quantity_unit_hidden" value="" disabled />
            <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">{{ __('Select') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit['code'] }}" @selected((string) old('quantity_unit', $lineData?->quantity_unit ?? '') === (string) $unit['code'])>{{ $unit['name'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('quantity_unit')" />
        </div>
        <div>
            <x-input-label for="unit_price" :value="__('Unit price')" />
            <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_price', $lineData?->unit_price ?? 0)" required />
        </div>
        <div>
            <x-input-label for="tax_amount" :value="__('Tax amount')" />
            <x-text-input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('tax_amount', $invoice?->tax_amount ?? 0)" />
        </div>
        <div>
            <x-input-label for="discount_amount" :value="__('Discount amount')" />
            <x-text-input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('discount_amount', $invoice?->discount_amount ?? 0)" />
        </div>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $invoice?->notes ?? '') }}</textarea>
</div>

<script>
    (function () {
        var linkEl = document.getElementById('link_contract');
        var wrap = document.getElementById('invoice-contract-select-wrap');
        var contractEl = document.getElementById('contract_id');
        if (!linkEl || !wrap || !contractEl) return;
        function syncContractVisibility() {
            var show = linkEl.value === 'yes';
            wrap.classList.toggle('hidden', !show);
            contractEl.disabled = !show;
            if (!show) contractEl.value = '';
        }
        linkEl.addEventListener('change', syncContractVisibility);
        syncContractVisibility();
    })();
    (function () {
        var certMap = @json($batchCertificateMap);
        var quantityMap = @json($batchQuantityMap);
        var batchEl = document.getElementById('batch_id');
        var certSelect = document.getElementById('certificate_select');
        var certHidden = document.getElementById('certificate_id_hidden');
        var msgEl = document.getElementById('batch-certificate-message');
        var quantityInput = document.getElementById('quantity');
        var quantityHidden = document.getElementById('quantity_hidden');
        var quantityHint = document.getElementById('batch-quantity-hint');
        var unitSelect = document.getElementById('quantity_unit');
        var unitHidden = document.getElementById('quantity_unit_hidden');
        var missingCertText = @json(__('This batch has no certificate. Create or link a certificate for the batch first, or clear the batch to choose a certificate manually.'));
        var qtyFromBatchLabel = @json(__('Quantity from batch'));
        if (!batchEl || !certSelect || !certHidden || !quantityInput || !quantityHidden || !unitSelect || !unitHidden) return;

        function clearCertMessage() {
            if (!msgEl) return;
            msgEl.textContent = '';
            msgEl.classList.add('hidden');
        }

        function showMissingCert() {
            if (!msgEl) return;
            msgEl.textContent = missingCertText;
            msgEl.classList.remove('hidden');
        }

        function useCertHiddenSubmit(value) {
            certSelect.removeAttribute('name');
            certSelect.disabled = true;
            certHidden.setAttribute('name', 'certificate_id');
            certHidden.value = value !== null && value !== undefined ? String(value) : '';
            certHidden.disabled = false;
        }

        function useCertSelectSubmit() {
            certHidden.removeAttribute('name');
            certHidden.disabled = true;
            certHidden.value = '';
            certSelect.setAttribute('name', 'certificate_id');
            certSelect.disabled = false;
        }

        function useQuantityHiddenSubmit(val) {
            quantityInput.removeAttribute('name');
            quantityInput.disabled = true;
            quantityHidden.setAttribute('name', 'quantity');
            quantityHidden.value = String(val);
            quantityHidden.disabled = false;
        }

        function useQuantityInputSubmit() {
            quantityHidden.removeAttribute('name');
            quantityHidden.disabled = true;
            quantityHidden.value = '';
            quantityInput.setAttribute('name', 'quantity');
            quantityInput.disabled = false;
        }

        function useQuantityUnitHiddenSubmit(val) {
            unitSelect.removeAttribute('name');
            unitSelect.disabled = true;
            unitHidden.setAttribute('name', 'quantity_unit');
            unitHidden.value = val === null || val === undefined ? '' : String(val);
            unitHidden.disabled = false;
        }

        function useQuantityUnitInputSubmit() {
            unitHidden.removeAttribute('name');
            unitHidden.disabled = true;
            unitHidden.value = '';
            unitSelect.setAttribute('name', 'quantity_unit');
            unitSelect.disabled = false;
        }

        function clearQuantityHint() {
            if (!quantityHint) return;
            quantityHint.textContent = '';
            quantityHint.classList.add('hidden');
        }

        function syncFromBatch() {
            var batchId = batchEl.value || '';
            clearCertMessage();
            clearQuantityHint();

            if (!batchId) {
                useCertSelectSubmit();
                certSelect.value = '';
                useQuantityInputSubmit();
                quantityInput.value = '1';
                useQuantityUnitInputSubmit();
                unitSelect.value = '';
                return;
            }

            var qrow = quantityMap[batchId];
            if (qrow) {
                quantityInput.value = String(qrow.quantity);
                useQuantityHiddenSubmit(qrow.quantity);
                var ucode = qrow.quantity_unit || '';
                unitSelect.value = ucode;
                useQuantityUnitHiddenSubmit(ucode);
                if (quantityHint && (qrow.quantity_unit_label || qrow.quantity_unit)) {
                    var unitPart = qrow.quantity_unit_label || qrow.quantity_unit || '';
                    quantityHint.textContent = qtyFromBatchLabel + ': ' + qrow.quantity + (unitPart ? ' ' + unitPart : '');
                    quantityHint.classList.remove('hidden');
                }
            } else {
                useQuantityInputSubmit();
                useQuantityUnitInputSubmit();
            }

            var row = certMap[batchId];
            if (row && row.certificate_id) {
                certSelect.value = String(row.certificate_id);
                useCertHiddenSubmit(row.certificate_id);
                return;
            }

            certSelect.value = '';
            useCertHiddenSubmit('');
            showMissingCert();
        }

        batchEl.addEventListener('change', syncFromBatch);
        syncFromBatch();
    })();
</script>
