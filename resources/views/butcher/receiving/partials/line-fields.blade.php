@php
    $line = is_array($line ?? null) ? $line : [];
    $idx = $index;
    $fieldClass = $fieldClass ?? 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp
<div class="rounded-lg border border-slate-200 bg-slate-50/40 p-4 space-y-3" data-line>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Line') }}</p>
        <button type="button" data-remove-line class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700">
            <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
            {{ __('Remove') }}
        </button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="lines_{{ $idx }}_meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat type') }}</label>
            <select id="lines_{{ $idx }}_meat_type" name="lines[{{ $idx }}][meat_type]" required class="{{ $fieldClass }}" data-field="meat_type">
                @foreach ($meatTypes as $type)
                    <option value="{{ $type }}" @selected(($line['meat_type'] ?? null) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('lines.'.$idx.'.meat_type')" class="mt-2" />
        </div>
        <div>
            <label for="lines_{{ $idx }}_outcome" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outcome') }}</label>
            <select id="lines_{{ $idx }}_outcome" name="lines[{{ $idx }}][outcome]" required class="{{ $fieldClass }}" data-field="outcome">
                @foreach ($outcomes as $outcome)
                    <option value="{{ $outcome }}" @selected(($line['outcome'] ?? 'accepted') === $outcome)>{{ str_replace('_', ' ', ucfirst($outcome)) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('lines.'.$idx.'.outcome')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div>
            <label for="lines_{{ $idx }}_expected_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expected (kg)') }}</label>
            <input id="lines_{{ $idx }}_expected_weight_kg" name="lines[{{ $idx }}][expected_weight_kg]" type="number" step="0.001" min="0" value="{{ $line['expected_weight_kg'] ?? '' }}" class="{{ $fieldClass }}" data-field="expected_weight_kg">
        </div>
        <div>
            <label for="lines_{{ $idx }}_received_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Received (kg)') }}</label>
            <input id="lines_{{ $idx }}_received_weight_kg" name="lines[{{ $idx }}][received_weight_kg]" type="number" step="0.001" min="0.1" required value="{{ $line['received_weight_kg'] ?? '' }}" class="{{ $fieldClass }}" data-field="received_weight_kg">
            <x-input-error :messages="$errors->get('lines.'.$idx.'.received_weight_kg')" class="mt-2" />
        </div>
        <div>
            <label for="lines_{{ $idx }}_unit_cost" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Unit cost / kg') }}</label>
            <input id="lines_{{ $idx }}_unit_cost" name="lines[{{ $idx }}][unit_cost]" type="number" step="0.01" min="0" required value="{{ $line['unit_cost'] ?? '' }}" class="{{ $fieldClass }}" data-field="unit_cost">
            <x-input-error :messages="$errors->get('lines.'.$idx.'.unit_cost')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div>
            <label for="lines_{{ $idx }}_accepted_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Accepted (kg)') }}</label>
            <input id="lines_{{ $idx }}_accepted_weight_kg" name="lines[{{ $idx }}][accepted_weight_kg]" type="number" step="0.001" min="0" value="{{ $line['accepted_weight_kg'] ?? '' }}" class="{{ $fieldClass }}" data-field="accepted_weight_kg">
            <x-input-error :messages="$errors->get('lines.'.$idx.'.accepted_weight_kg')" class="mt-2" />
        </div>
        <div>
            <label for="lines_{{ $idx }}_rejected_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Rejected (kg)') }}</label>
            <input id="lines_{{ $idx }}_rejected_weight_kg" name="lines[{{ $idx }}][rejected_weight_kg]" type="number" step="0.001" min="0" value="{{ $line['rejected_weight_kg'] ?? '' }}" class="{{ $fieldClass }}" data-field="rejected_weight_kg">
            <x-input-error :messages="$errors->get('lines.'.$idx.'.rejected_weight_kg')" class="mt-2" />
        </div>
        <div>
            <label for="lines_{{ $idx }}_temperature_c" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Temp °C') }}</label>
            <input id="lines_{{ $idx }}_temperature_c" name="lines[{{ $idx }}][temperature_c]" type="number" step="0.01" value="{{ $line['temperature_c'] ?? '' }}" class="{{ $fieldClass }}" data-field="temperature_c">
        </div>
    </div>

    <div>
        <label for="lines_{{ $idx }}_condition_notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Condition notes') }}</label>
        <textarea id="lines_{{ $idx }}_condition_notes" name="lines[{{ $idx }}][condition_notes]" rows="2" class="{{ $fieldClass }}" data-field="condition_notes">{{ $line['condition_notes'] ?? '' }}</textarea>
    </div>
</div>
