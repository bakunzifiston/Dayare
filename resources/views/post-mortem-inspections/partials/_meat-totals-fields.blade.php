@php
    $examinedValue = (float) ($examinedValue ?? 0);
    $carcassApprovedValue = (float) ($carcassApprovedValue ?? 0);
    $otherMeatApprovedValue = (float) ($otherMeatApprovedValue ?? 0);
    $condemnedValue = (float) ($condemnedValue ?? 0);
    $approvedTotal = $carcassApprovedValue + $otherMeatApprovedValue;
@endphp

<div id="aggregate-counts-section" @class(['grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4', 'hidden' => $hidden ?? false])>
    <div>
        <x-input-label for="total_examined" :value="__('Total examined meat (kg)')" />
        <x-text-input id="total_examined" name="total_examined" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('total_examined', $examinedValue)" required />
        <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
    </div>
    <div>
        <x-input-label for="approved_carcass_kg" :value="__('Carcass meat approved (kg)')" />
        <x-text-input id="approved_carcass_kg" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('approved_carcass_kg', $carcassApprovedValue)" />
        <x-input-error class="mt-2" :messages="$errors->get('approved_carcass_kg')" />
    </div>
    <div>
        <x-input-label for="approved_other_meat_kg" :value="__('Other meat approved (kg)')" />
        <x-text-input id="approved_other_meat_kg" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('approved_other_meat_kg', $otherMeatApprovedValue)" />
        <x-input-error class="mt-2" :messages="$errors->get('approved_other_meat_kg')" />
    </div>
    <div>
        <x-input-label for="condemned_quantity" :value="__('Condemned meat (kg)')" />
        <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('condemned_quantity', $condemnedValue)" required />
        <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
    </div>
</div>
<input type="hidden" id="approved_quantity" name="approved_quantity" value="{{ old('approved_quantity', $approvedTotal) }}">
<p class="text-xs text-gray-500 -mt-2">{{ __('Carcass + other approved + condemned cannot exceed total examined meat.') }}</p>
