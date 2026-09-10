@php
    $line = is_array($line ?? null) ? $line : [];
    $idx = $index;
@endphp
<div class="rounded-lg border border-slate-200 p-4 space-y-3" data-line>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Line') }}</p>
        <button type="button" data-remove-line class="text-xs font-semibold text-red-600 hover:underline">{{ __('Remove') }}</button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <x-input-label :for="'lines_'.$idx.'_meat_type'" :value="__('Meat type')" />
            <select id="lines_{{ $idx }}_meat_type" name="lines[{{ $idx }}][meat_type]" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm" data-field="meat_type">
                @foreach ($meatTypes as $type)
                    <option value="{{ $type }}" @selected(($line['meat_type'] ?? null) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('lines.'.$idx.'.meat_type')" class="mt-2" />
        </div>
        <div>
            <x-input-label :for="'lines_'.$idx.'_outcome'" :value="__('Outcome')" />
            <select id="lines_{{ $idx }}_outcome" name="lines[{{ $idx }}][outcome]" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm" data-field="outcome">
                @foreach ($outcomes as $outcome)
                    <option value="{{ $outcome }}" @selected(($line['outcome'] ?? 'accepted') === $outcome)>{{ str_replace('_', ' ', ucfirst($outcome)) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('lines.'.$idx.'.outcome')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div>
            <x-input-label :for="'lines_'.$idx.'_expected_weight_kg'" :value="__('Expected (kg)')" />
            <x-text-input id="lines_{{ $idx }}_expected_weight_kg" name="lines[{{ $idx }}][expected_weight_kg]" type="number" step="0.001" min="0" class="mt-1 block w-full" :value="$line['expected_weight_kg'] ?? ''" data-field="expected_weight_kg" />
        </div>
        <div>
            <x-input-label :for="'lines_'.$idx.'_received_weight_kg'" :value="__('Received (kg)')" />
            <x-text-input id="lines_{{ $idx }}_received_weight_kg" name="lines[{{ $idx }}][received_weight_kg]" type="number" step="0.001" min="0.1" class="mt-1 block w-full" :value="$line['received_weight_kg'] ?? ''" required data-field="received_weight_kg" />
            <x-input-error :messages="$errors->get('lines.'.$idx.'.received_weight_kg')" class="mt-2" />
        </div>
        <div>
            <x-input-label :for="'lines_'.$idx.'_unit_cost'" :value="__('Unit cost / kg')" />
            <x-text-input id="lines_{{ $idx }}_unit_cost" name="lines[{{ $idx }}][unit_cost]" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="$line['unit_cost'] ?? ''" required data-field="unit_cost" />
            <x-input-error :messages="$errors->get('lines.'.$idx.'.unit_cost')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div>
            <x-input-label :for="'lines_'.$idx.'_accepted_weight_kg'" :value="__('Accepted (kg)')" />
            <x-text-input id="lines_{{ $idx }}_accepted_weight_kg" name="lines[{{ $idx }}][accepted_weight_kg]" type="number" step="0.001" min="0" class="mt-1 block w-full" :value="$line['accepted_weight_kg'] ?? ''" data-field="accepted_weight_kg" />
            <x-input-error :messages="$errors->get('lines.'.$idx.'.accepted_weight_kg')" class="mt-2" />
        </div>
        <div>
            <x-input-label :for="'lines_'.$idx.'_rejected_weight_kg'" :value="__('Rejected (kg)')" />
            <x-text-input id="lines_{{ $idx }}_rejected_weight_kg" name="lines[{{ $idx }}][rejected_weight_kg]" type="number" step="0.001" min="0" class="mt-1 block w-full" :value="$line['rejected_weight_kg'] ?? ''" data-field="rejected_weight_kg" />
            <x-input-error :messages="$errors->get('lines.'.$idx.'.rejected_weight_kg')" class="mt-2" />
        </div>
        <div>
            <x-input-label :for="'lines_'.$idx.'_temperature_c'" :value="__('Temp °C (optional)')" />
            <x-text-input id="lines_{{ $idx }}_temperature_c" name="lines[{{ $idx }}][temperature_c]" type="number" step="0.01" class="mt-1 block w-full" :value="$line['temperature_c'] ?? ''" data-field="temperature_c" />
        </div>
    </div>

    <div>
        <x-input-label :for="'lines_'.$idx.'_condition_notes'" :value="__('Condition notes (optional)')" />
        <textarea id="lines_{{ $idx }}_condition_notes" name="lines[{{ $idx }}][condition_notes]" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" data-field="condition_notes">{{ $line['condition_notes'] ?? '' }}</textarea>
    </div>
</div>
