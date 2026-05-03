@php
    $lineData = $line ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-input-label for="invoice_number" :value="__('Invoice number')" />
        <x-text-input id="invoice_number" name="invoice_number" type="text" class="mt-1 block w-full" :value="old('invoice_number', $invoice->invoice_number ?? ('AR-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)))" required />
    </div>
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (['draft', 'issued', 'overdue', 'paid', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(old('status', $invoice->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="currency" :value="__('Currency')" />
        <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $invoice->currency ?? 'RWF')" required />
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="client_id" :value="__('Client')" />
        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected((string) old('client_id', $invoice->client_id ?? '') === (string) $client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="contract_id" :value="__('Contract')" />
        <select id="contract_id" name="contract_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($contracts as $contract)
                <option value="{{ $contract->id }}" @selected((string) old('contract_id', $invoice->contract_id ?? '') === (string) $contract->id)>{{ $contract->contract_number ?? ('#'.$contract->id) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="delivery_confirmation_id" :value="__('Delivery confirmation')" />
        <select id="delivery_confirmation_id" name="delivery_confirmation_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select') }}</option>
            @foreach($deliveries as $delivery)
                <option value="{{ $delivery->id }}" @selected((string) old('delivery_confirmation_id', $invoice->delivery_confirmation_id ?? '') === (string) $delivery->id)>
                    {{ '#'.$delivery->id.' • '.($delivery->receiver_name ?? 'Receiver').' • '.number_format((float) ($delivery->received_quantity ?? 0), 2) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
    <div>
        <x-input-label for="issued_at" :value="__('Issued at')" />
        <x-text-input id="issued_at" name="issued_at" type="datetime-local" class="mt-1 block w-full" :value="old('issued_at', optional($invoice->issued_at ?? now())->format('Y-m-d\\TH:i'))" />
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
        <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount_paid', $invoice->amount_paid ?? 0)" />
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
            <x-input-label for="certificate_id" :value="__('Certificate')" />
            <select id="certificate_id" name="certificate_id" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">{{ __('Select') }}</option>
                @foreach($certificates as $certificate)
                    <option value="{{ $certificate->id }}" @selected((string) old('certificate_id', $lineData->certificate_id ?? '') === (string) $certificate->id)>{{ $certificate->certificate_number ?? ('#'.$certificate->id) }}</option>
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
            <x-text-input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('tax_amount', $invoice->tax_amount ?? 0)" />
        </div>
        <div>
            <x-input-label for="discount_amount" :value="__('Discount amount')" />
            <x-text-input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('discount_amount', $invoice->discount_amount ?? 0)" />
        </div>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $invoice->notes ?? '') }}</textarea>
</div>
