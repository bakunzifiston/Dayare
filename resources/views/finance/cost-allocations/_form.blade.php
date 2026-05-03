<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-input-label for="allocation_date" :value="__('Allocation date')" />
        <x-text-input id="allocation_date" name="allocation_date" type="date" class="mt-1 block w-full" :value="old('allocation_date', optional($allocation?->allocation_date ?? now())->format('Y-m-d'))" required />
    </div>
    <div>
        <x-input-label for="batch_id" :value="__('Batch')" />
        <select id="batch_id" name="batch_id" class="mt-1 block w-full rounded-lg border-slate-300" required>
            <option value="">{{ __('Select') }}</option>
            @foreach($batches as $batch)
                <option value="{{ $batch->id }}" @selected((string) old('batch_id', $allocation->batch_id ?? '') === (string) $batch->id)>
                    {{ $batch->batch_code ?? ('#'.$batch->id) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="category" :value="__('Category')" />
        <select id="category" name="category" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (['labor', 'logistics', 'overhead', 'utilities', 'other'] as $category)
                <option value="{{ $category }}" @selected(old('category', $allocation->category ?? 'other') === $category)>{{ ucfirst($category) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="amount" :value="__('Amount (RWF)')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $allocation->amount ?? 0)" required />
    </div>
    <div>
        <x-input-label for="source_type" :value="__('Source type (optional)')" />
        <x-text-input id="source_type" name="source_type" type="text" class="mt-1 block w-full" :value="old('source_type', $allocation->source_type ?? '')" />
    </div>
    <div>
        <x-input-label for="source_id" :value="__('Source id (optional)')" />
        <x-text-input id="source_id" name="source_id" type="number" min="1" class="mt-1 block w-full" :value="old('source_id', $allocation->source_id ?? '')" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $allocation->notes ?? '') }}</textarea>
</div>
