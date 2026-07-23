@props([
    'trip' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
])

@php
    $locked = collect($lockedTransportFields);
    $departureDate = old(
        'departure_date',
        $trip?->departure_date?->format('Y-m-d') ?? ($transportDefaults['departure_date'] ?? '')
    );
    $arrivalDate = old('arrival_date', $trip?->arrival_date?->format('Y-m-d'));
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="departure_date" :value="__('Departure date')" />
        @if ($locked->contains('departure_date'))
            <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $departureDate }}</p>
            <input type="hidden" name="departure_date" value="{{ $departureDate }}">
            <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate departure time / date') }}</p>
        @else
            <x-text-input id="departure_date" name="departure_date" type="date" class="mt-1 block w-full" :value="$departureDate" required />
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('departure_date')" />
    </div>
    <div>
        <x-input-label for="arrival_date" :value="__('Arrival date')" />
        <x-text-input id="arrival_date" name="arrival_date" type="date" class="mt-1 block w-full" :value="$arrivalDate" />
        <x-input-error class="mt-2" :messages="$errors->get('arrival_date')" />
    </div>
</div>
