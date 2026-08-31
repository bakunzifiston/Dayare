@php
    use App\Models\FinanceEbmRecord;
    $record = $record ?? null;
    $invoices = $invoices ?? collect();
    $facilities = $facilities ?? collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-input-label for="finance_invoice_id" :value="__('Linked AR invoice / sale')" />
        <select id="finance_invoice_id" name="finance_invoice_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Not linked yet') }}</option>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected((string) old('finance_invoice_id', $record?->finance_invoice_id ?? ($selectedInvoiceId ?? '')) === (string) $invoice->id)>
                    {{ $invoice->invoice_number }} · {{ number_format((float) $invoice->total_amount, 0) }} RWF
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="facility_id" :value="__('Site / location')" />
        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select site') }}</option>
            @foreach ($facilities as $facility)
                <option value="{{ $facility->id }}" @selected((string) old('facility_id', $record?->facility_id ?? '') === (string) $facility->id)>{{ $facility->facility_name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="ebm_invoice_number" :value="__('EBM invoice / reference')" />
        <x-text-input id="ebm_invoice_number" name="ebm_invoice_number" type="text" class="mt-1 block w-full" :value="old('ebm_invoice_number', $record?->ebm_invoice_number)" required />
    </div>
    <div>
        <x-input-label for="ebm_receipt_number" :value="__('EBM receipt number')" />
        <x-text-input id="ebm_receipt_number" name="ebm_receipt_number" type="text" class="mt-1 block w-full" :value="old('ebm_receipt_number', $record?->ebm_receipt_number)" />
    </div>
    <div>
        <x-input-label for="issued_at" :value="__('EBM issue date')" />
        <x-text-input id="issued_at" name="issued_at" type="datetime-local" class="mt-1 block w-full" :value="old('issued_at', optional($record?->issued_at)->format('Y-m-d\\TH:i'))" />
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="amount" :value="__('EBM amount')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $record?->amount)" />
    </div>
    <div>
        <x-input-label for="status" :value="__('EBM status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (FinanceEbmRecord::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $record?->status ?? 'issued') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $record?->notes) }}</textarea>
</div>
