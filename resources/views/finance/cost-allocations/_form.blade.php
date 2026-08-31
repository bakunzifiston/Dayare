@php
    $allocation = $allocation ?? null;
    $expenses = $expenses ?? collect();
    $ctrl = 'mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm';
    $expenseClass = \App\Models\FinanceExpense::class;
    $linkedExpenseId = old(
        'expense_id',
        ($allocation?->source_type === $expenseClass ? $allocation->source_id : '') ?? ''
    );
    $keepSourceType = '';
    $keepSourceId = '';
    if ($allocation && filled($allocation->source_type) && $allocation->source_type !== $expenseClass) {
        $keepSourceType = (string) $allocation->source_type;
        $keepSourceId = $allocation->source_id;
    }
@endphp

@if ($keepSourceType !== '')
    <input type="hidden" name="source_type" value="{{ old('source_type', $keepSourceType) }}">
    <input type="hidden" name="source_id" value="{{ old('source_id', $keepSourceId) }}">
@endif

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <x-input-label for="allocation_date" :value="__('Date')" />
        <x-text-input id="allocation_date" name="allocation_date" type="date" class="{{ $ctrl }}" :value="old('allocation_date', optional($allocation?->allocation_date ?? now())->format('Y-m-d'))" required />
        <x-input-error class="mt-1" :messages="$errors->get('allocation_date')" />
    </div>
    <div>
        <x-input-label for="batch_id" :value="__('Batch')" />
        <select id="batch_id" name="batch_id" class="{{ $ctrl }}" required>
            <option value="">{{ __('Select batch') }}</option>
            @foreach ($batches as $batch)
                <option value="{{ $batch->id }}" @selected((string) old('batch_id', $allocation->batch_id ?? '') === (string) $batch->id)>
                    {{ $batch->batch_code ?? ('#'.$batch->id) }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('batch_id')" />
    </div>
    <div>
        <x-input-label for="category" :value="__('Category')" />
        <select id="category" name="category" class="{{ $ctrl }}" required>
            @foreach (['labor', 'logistics', 'overhead', 'utilities', 'other'] as $category)
                <option value="{{ $category }}" @selected(old('category', $allocation->category ?? 'other') === $category)>{{ ucfirst($category) }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('category')" />
    </div>
    <div>
        <x-input-label for="amount" :value="__('Amount (RWF)')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="{{ $ctrl }}" :value="old('amount', $allocation->amount ?? '')" required />
        <x-input-error class="mt-1" :messages="$errors->get('amount')" />
    </div>
</div>

<div>
    <x-input-label for="expense_id" :value="__('Source expense')" />
    <p class="mt-0.5 text-xs text-slate-500">{{ __('Optional. Link an operating expense already on the register. Leave empty for a standalone allocation.') }}</p>
    <select id="expense_id" name="expense_id" class="{{ $ctrl }}">
        <option value="">{{ __('None') }}</option>
        @foreach ($expenses as $expense)
            <option
                value="{{ $expense->id }}"
                data-amount="{{ $expense->amount }}"
                @selected((string) $linkedExpenseId === (string) $expense->id)
            >
                {{ $expense->expense_number }} · {{ $expense->description }} · {{ number_format((float) $expense->amount, 0) }} RWF
            </option>
        @endforeach
    </select>
    <x-input-error class="mt-1" :messages="$errors->get('expense_id')" />
</div>

<div>
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" rows="2">{{ old('notes', $allocation->notes ?? '') }}</textarea>
    <x-input-error class="mt-1" :messages="$errors->get('notes')" />
</div>

<script>
    (function () {
        var expenseEl = document.getElementById('expense_id');
        var amountEl = document.getElementById('amount');
        if (!expenseEl || !amountEl) return;
        expenseEl.addEventListener('change', function () {
            var opt = expenseEl.options[expenseEl.selectedIndex];
            if (!opt || !opt.value || !opt.dataset.amount) return;
            var current = parseFloat(amountEl.value || '0');
            if (!current) amountEl.value = opt.dataset.amount;
        });
    })();
</script>
