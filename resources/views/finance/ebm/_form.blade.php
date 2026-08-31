@php
    $record = $record ?? null;
    $invoices = $invoices ?? collect();
    $facilities = $facilities ?? collect();
    $ctrl = 'mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm';
    $statuses = \App\Models\FinanceEbmRecord::STATUSES;
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
        <p class="font-medium">{{ __('Please fix the highlighted fields.') }}</p>
    </div>
@endif

<div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <div>
        <x-input-label for="finance_invoice_id" :value="__('Linked AR invoice / sale')" />
        <select id="finance_invoice_id" name="finance_invoice_id" class="{{ $ctrl }}">
            <option value="">{{ __('Not linked yet') }}</option>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected((string) old('finance_invoice_id', $record?->finance_invoice_id ?? ($selectedInvoiceId ?? '')) === (string) $invoice->id)>
                    {{ $invoice->invoice_number }} · {{ number_format((float) $invoice->total_amount, 0) }} RWF
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('finance_invoice_id')" />
    </div>
    <div>
        <x-input-label for="facility_id" :value="__('Site / location')" />
        <select id="facility_id" name="facility_id" class="{{ $ctrl }}">
            <option value="">{{ __('Select site') }}</option>
            @foreach ($facilities as $facility)
                <option value="{{ $facility->id }}" @selected((string) old('facility_id', $record?->facility_id ?? '') === (string) $facility->id)>{{ $facility->facility_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('facility_id')" />
    </div>
</div>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <x-input-label for="ebm_invoice_number" :value="__('EBM invoice / reference')" />
        <x-text-input id="ebm_invoice_number" name="ebm_invoice_number" type="text" class="{{ $ctrl }}" :value="old('ebm_invoice_number', $record?->ebm_invoice_number)" maxlength="80" required />
        <x-input-error class="mt-1" :messages="$errors->get('ebm_invoice_number')" />
    </div>
    <div>
        <x-input-label for="ebm_receipt_number" :value="__('EBM receipt number')" />
        <x-text-input id="ebm_receipt_number" name="ebm_receipt_number" type="text" class="{{ $ctrl }}" :value="old('ebm_receipt_number', $record?->ebm_receipt_number)" maxlength="80" />
        <x-input-error class="mt-1" :messages="$errors->get('ebm_receipt_number')" />
    </div>
    <div>
        <x-input-label for="issued_at" :value="__('EBM issue date')" />
        <x-text-input id="issued_at" name="issued_at" type="datetime-local" class="{{ $ctrl }}" :value="old('issued_at', optional($record?->issued_at)->format('Y-m-d\\TH:i'))" />
        <x-input-error class="mt-1" :messages="$errors->get('issued_at')" />
    </div>
    <div>
        <x-input-label for="amount" :value="__('EBM amount')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" max="999999999999.99" class="{{ $ctrl }}" :value="old('amount', $record?->amount)" />
        <x-input-error class="mt-1" :messages="$errors->get('amount')" />
    </div>
</div>

<div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <div>
        <x-input-label for="status" :value="__('EBM status')" />
        <select id="status" name="status" class="{{ $ctrl }}" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $record?->status ?? 'issued') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('status')" />
    </div>
    <div>
        <x-input-label for="notes" :value="__('Notes')" />
        <x-text-input id="notes" name="notes" type="text" class="{{ $ctrl }}" :value="old('notes', $record?->notes)" maxlength="2000" />
        <x-input-error class="mt-1" :messages="$errors->get('notes')" />
    </div>
</div>
