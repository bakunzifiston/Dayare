@php
    use App\Models\FinanceExpense;
    use App\Models\FinancePayment;
    $e = $expense ?? null;
    $facilities = $facilities ?? collect();
    $suppliers = $suppliers ?? collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-input-label for="expense_number" :value="__('Reference number')" />
        <x-text-input id="expense_number" name="expense_number" type="text" class="mt-1 block w-full" :value="old('expense_number', $e?->expense_number)" placeholder="{{ __('Generated on save if empty') }}" />
    </div>
    <div>
        <x-input-label for="expense_date" :value="__('Expense date')" />
        <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full" :value="old('expense_date', optional($e?->expense_date ?? now())->format('Y-m-d'))" required />
    </div>
    <div>
        <x-input-label for="category" :value="__('Category')" />
        <select id="category" name="category" class="mt-1 block w-full rounded-lg border-slate-300" required>
            @foreach (FinanceExpense::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $e?->category ?? 'operational') === $category)>{{ FinanceExpense::categoryLabel($category) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="amount" :value="__('Amount (RWF)')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', $e?->amount)" required />
    </div>
    <div>
        <x-input-label for="supplier_id" :value="__('Vendor / supplier')" />
        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('None') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $e?->supplier_id ?? '') === (string) $supplier->id)>
                    {{ trim(($supplier->first_name ?? '').' '.($supplier->last_name ?? '')) ?: ('#'.$supplier->id) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="facility_id" :value="__('Site / location')" />
        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-slate-300">
            <option value="">{{ __('Select site') }}</option>
            @foreach ($facilities as $facility)
                <option value="{{ $facility->id }}" @selected((string) old('facility_id', $e?->facility_id ?? '') === (string) $facility->id)>{{ $facility->facility_name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="description" :value="__('Description')" />
    <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $e?->description)" required />
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="reference_number" :value="__('Supporting reference')" />
        <x-text-input id="reference_number" name="reference_number" type="text" class="mt-1 block w-full" :value="old('reference_number', $e?->reference_number)" />
    </div>
    <div>
        <x-input-label for="attachment" :value="__('Supporting document')" />
        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm" />
        @if ($e?->hasAttachment())
            <a href="{{ route('finance.expenses.attachment', $e) }}" class="mt-1 inline-block text-xs text-bucha-primary">{{ __('Download current attachment') }}</a>
        @endif
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $e?->notes) }}</textarea>
</div>

@if (! $e)
    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-3">
        <p class="text-sm font-semibold text-slate-800">{{ __('Payment') }}</p>
        <p class="text-xs text-slate-500">{{ __('Cash and other paid-now expenses stay on this register. They do not create an accounts payable bill.') }}</p>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-input-label for="already_paid" :value="__('Payment status')" />
                <select id="already_paid" name="already_paid" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="0" @selected(old('already_paid', '0') === '0')>{{ __('Unpaid') }}</option>
                    <option value="1" @selected(old('already_paid') === '1')>{{ __('Already paid') }}</option>
                </select>
            </div>
            <div>
                <x-input-label for="payment_method" :value="__('Payment method')" />
                <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">{{ __('Select') }}</option>
                    @foreach (FinancePayment::METHODS as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ FinancePayment::methodLabel($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="payment_reference" :value="__('Payment reference')" />
                <x-text-input id="payment_reference" name="payment_reference" type="text" class="mt-1 block w-full" :value="old('payment_reference')" placeholder="{{ __('MoMo / bank / receipt no.') }}" />
            </div>
            <div>
                <x-input-label for="payment_paid_at" :value="__('Payment date')" />
                <x-text-input id="payment_paid_at" name="payment_paid_at" type="datetime-local" class="mt-1 block w-full" :value="old('payment_paid_at')" />
            </div>
        </div>
    </div>
@endif
