@props(['trip' => null])

@php
    $destinationName = old(
        'destination_name',
        $trip?->destination_name ?? $trip?->destinationFacility?->facility_name
    );
@endphp

<div class="space-y-4">
    <div>
        <x-input-label for="destination_name" :value="__('Destination')" />
        <x-text-input id="destination_name" name="destination_name" type="text" class="mt-1 block w-full" :value="$destinationName" required />
        <p class="mt-1 text-xs text-gray-500">{{ __('e.g. client warehouse, shop, border post, airport cargo') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('destination_name')" />
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="destination_country" :value="__('Country')" />
            <x-text-input id="destination_country" name="destination_country" type="text" class="mt-1 block w-full" :value="old('destination_country', $trip?->destination_country)" placeholder="e.g. RW, KE, UG" />
            <x-input-error class="mt-2" :messages="$errors->get('destination_country')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="destination_address" :value="__('Address (optional)')" />
            <x-text-input id="destination_address" name="destination_address" type="text" class="mt-1 block w-full" :value="old('destination_address', $trip?->destination_address)" />
            <x-input-error class="mt-2" :messages="$errors->get('destination_address')" />
        </div>
    </div>
</div>
