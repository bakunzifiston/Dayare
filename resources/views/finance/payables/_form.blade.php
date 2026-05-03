@php
    $lineData = $line ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-input-label for="payable_number" :value="__('Payable number')" />
        <x-text-input id="payable_number" name="payable_number" type="text" class="mt-1 block w-full" :value="old('payable_number', $payable->payable_number ?? ('AP-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)))" required />
    </div>
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (['open', 'overdue', 'paid', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(old('status', $payable->status ?? 'open') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="currency" :value="__('Currency')" />
        <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $payable->currency ?? 'RWF')" required />
    </div>
</div>

@php
    $selectedCounterpartyType = old('counterparty_type');
    if (! $selectedCounterpartyType) {
        if (! empty($payable->supplier_id)) {
            $selectedCounterpartyType = 'supplier';
        } elseif (! empty($payable->client_id)) {
            $selectedCounterpartyType = 'client';
        } else {
            $selectedCounterpartyType = 'supplier';
        }
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="counterparty_type" :value="__('Counterparty type')" />
        <select id="counterparty_type" name="counterparty_type" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="supplier" @selected($selectedCounterpartyType === 'supplier')>{{ __('Supplier') }}</option>
            <option value="client" @selected($selectedCounterpartyType === 'client')>{{ __('Client') }}</option>
        </select>
    </div>
    <div>
        <x-input-label for="supplier_id" :value="__('Supplier')" />
        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $payable->supplier_id ?? '') === (string) $supplier->id)>
                    {{ trim(($supplier->first_name ?? '').' '.($supplier->last_name ?? '')) ?: ('#'.$supplier->id) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="client_id" :value="__('Client (for intake from client)')" />
        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected((string) old('client_id', $payable->client_id ?? '') === (string) $client->id)>
                    {{ $client->name ?? ('#'.$client->id) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="contract_id" :value="__('Contract')" />
        <select id="contract_id" name="contract_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($contracts as $contract)
                <option value="{{ $contract->id }}" @selected((string) old('contract_id', $payable->contract_id ?? '') === (string) $contract->id)>{{ $contract->contract_number ?? ('#'.$contract->id) }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-3">
        <x-input-label for="animal_intake_id" :value="__('Animal intake')" />
        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($animalIntakes as $intake)
                <option
                    value="{{ $intake->id }}"
                    data-source-type="{{ $intake->source_type }}"
                    data-supplier-id="{{ $intake->supplier_id }}"
                    data-client-id="{{ $intake->client_id }}"
                    @selected((string) old('animal_intake_id', $payable->animal_intake_id ?? '') === (string) $intake->id)
                >
                    {{ ('#'.$intake->id).' • '.($intake->source_type ?? '—').' • '.($intake->species ?? '').' • '.number_format((float) ($intake->number_of_animals ?? 0), 0) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
    <div>
        <x-input-label for="issued_at" :value="__('Issued at')" />
        <x-text-input id="issued_at" name="issued_at" type="datetime-local" class="mt-1 block w-full" :value="old('issued_at', optional($payable->issued_at ?? now())->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="due_date" :value="__('Due date')" />
        <x-text-input id="due_date" name="due_date" type="datetime-local" class="mt-1 block w-full" :value="old('due_date', optional($payable?->due_date)->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="paid_at" :value="__('Paid at')" />
        <x-text-input id="paid_at" name="paid_at" type="datetime-local" class="mt-1 block w-full" :value="old('paid_at', optional($payable?->paid_at)->format('Y-m-d\\TH:i'))" />
    </div>
    <div>
        <x-input-label for="amount_paid" :value="__('Amount paid')" />
        <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount_paid', $payable->amount_paid ?? 0)" />
    </div>
</div>

<div class="mt-6 rounded-lg border border-slate-200 p-4">
    <h3 class="font-semibold text-slate-900">{{ __('Primary line item') }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
        <div class="md:col-span-2">
            <x-input-label for="line_description" :value="__('Description')" />
            <x-text-input id="line_description" name="line_description" type="text" class="mt-1 block w-full" :value="old('line_description', $lineData->description ?? '')" required />
        </div>
        <div>
            <x-input-label for="batch_id" :value="__('Batch')" />
            <select id="batch_id" name="batch_id" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">{{ __('Select') }}</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((string) old('batch_id', $lineData->batch_id ?? '') === (string) $batch->id)>{{ $batch->batch_code ?? ('#'.$batch->id) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="quantity" :value="__('Quantity')" />
            <x-text-input id="quantity" name="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" :value="old('quantity', $lineData->quantity ?? 1)" required />
        </div>
        <div>
            <x-input-label for="unit_price" :value="__('Unit price')" />
            <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_price', $lineData->unit_price ?? 0)" required />
        </div>
        <div>
            <x-input-label for="tax_amount" :value="__('Tax amount')" />
            <x-text-input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('tax_amount', $payable->tax_amount ?? 0)" />
        </div>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $payable->notes ?? '') }}</textarea>
</div>

<script>
    (function () {
        const typeSelect = document.getElementById('counterparty_type');
        const supplierSelect = document.getElementById('supplier_id');
        const clientSelect = document.getElementById('client_id');
        const intakeSelect = document.getElementById('animal_intake_id');
        if (!typeSelect || !supplierSelect || !clientSelect || !intakeSelect) return;

        const refresh = () => {
            const type = typeSelect.value;
            const supplierId = supplierSelect.value;
            const clientId = clientSelect.value;

            supplierSelect.disabled = type !== 'supplier';
            clientSelect.disabled = type !== 'client';

            if (type === 'supplier') {
                clientSelect.value = '';
            } else {
                supplierSelect.value = '';
            }

            Array.from(intakeSelect.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }
                const intakeType = option.dataset.sourceType || '';
                const intakeSupplierId = option.dataset.supplierId || '';
                const intakeClientId = option.dataset.clientId || '';

                let visible = false;
                if (type === 'supplier') {
                    visible = intakeType === 'supplier' && supplierId !== '' && intakeSupplierId === supplierId;
                } else {
                    visible = intakeType === 'client' && clientId !== '' && intakeClientId === clientId;
                }

                option.hidden = !visible;
            });

            const selected = intakeSelect.options[intakeSelect.selectedIndex];
            if (selected && selected.hidden) {
                intakeSelect.value = '';
            }
        };

        typeSelect.addEventListener('change', refresh);
        supplierSelect.addEventListener('change', refresh);
        clientSelect.addEventListener('change', refresh);
        refresh();
    })();
</script>
