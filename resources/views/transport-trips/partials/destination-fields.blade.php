@props([
    'trip' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
])

@php
    $locked = collect($lockedTransportFields);
    $destinationName = old(
        'destination_name',
        $trip?->destination_name ?? ($transportDefaults['destination_name'] ?? $transportDefaults['departure_destination'] ?? '')
    );
    $destinationCountry = old(
        'destination_country',
        $trip?->destination_country ?? ($transportDefaults['destination_country'] ?? '')
    );
    $destinationAddress = old(
        'destination_address',
        $trip?->destination_address ?? ($transportDefaults['destination_address'] ?? '')
    );
@endphp

<div class="space-y-4">
    <div>
        <x-input-label for="destination_name" :value="__('Destination')" />
        @if ($locked->contains('destination_name'))
            <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $destinationName }}</p>
            <input type="hidden" name="destination_name" value="{{ $destinationName }}">
            <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate') }}</p>
        @else
            <x-text-input id="destination_name" name="destination_name" type="text" class="mt-1 block w-full" :value="$destinationName" required />
            <p class="mt-1 text-xs text-gray-500">{{ __('e.g. client warehouse, shop, border post, airport cargo') }}</p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('destination_name')" />
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="destination_country" :value="__('Country')" />
            @if ($locked->contains('destination_country'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $destinationCountry }}</p>
                <input type="hidden" name="destination_country" value="{{ $destinationCountry }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate') }}</p>
            @else
                <x-text-input id="destination_country" name="destination_country" type="text" class="mt-1 block w-full" :value="$destinationCountry" placeholder="e.g. RW, KE, UG" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('destination_country')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="destination_address" :value="__('Address (optional)')" />
            @if ($locked->contains('destination_address'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $destinationAddress }}</p>
                <input type="hidden" name="destination_address" value="{{ $destinationAddress }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate') }}</p>
            @else
                <x-text-input id="destination_address" name="destination_address" type="text" class="mt-1 block w-full" :value="$destinationAddress" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('destination_address')" />
        </div>
    </div>
</div>
