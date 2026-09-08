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

<x-wizard-section :title="__('Schedule')">
    <div class="bucha-wizard-grid">
        <div>
            <x-certificate-sourced-field
                name="departure_date"
                :label="__('Departure date')"
                :value="$departureDate"
                :locked="$locked->contains('departure_date')"
                type="date"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('departure_date')" />
        </div>
        <div>
            <x-wizard-field for="arrival_date" :label="__('Arrival date')">
                <input id="arrival_date" name="arrival_date" type="date" class="bucha-wizard-input" value="{{ $arrivalDate }}" />
            </x-wizard-field>
            <x-input-error class="mt-2" :messages="$errors->get('arrival_date')" />
        </div>
    </div>
</x-wizard-section>
