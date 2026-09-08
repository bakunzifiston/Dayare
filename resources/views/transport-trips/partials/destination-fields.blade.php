@props([
    'trip' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
    'destinationCountries' => [],
])

@php
    $locked = collect($lockedTransportFields);
    $destinationName = old(
        'destination_name',
        $trip?->destination_name ?? ($transportDefaults['destination_name'] ?? $transportDefaults['departure_destination'] ?? '')
    );
    $destinationCountry = strtoupper((string) old(
        'destination_country',
        $trip?->destination_country ?? ($transportDefaults['destination_country'] ?? '')
    ));
    $destinationAddress = old(
        'destination_address',
        $trip?->destination_address ?? ($transportDefaults['destination_address'] ?? '')
    );

    $countries = collect($destinationCountries ?: config('processor.destination_countries', []))
        ->mapWithKeys(fn ($label, $code) => [strtoupper((string) $code) => $label])
        ->all();

    if ($destinationCountry !== '' && ! array_key_exists($destinationCountry, $countries)) {
        $countries[$destinationCountry] = $destinationCountry;
    }

    $countryLabel = $countries[$destinationCountry] ?? $destinationCountry;
@endphp

<x-wizard-section :title="__('Destination')">
    <x-certificate-sourced-field
        name="destination_name"
        :label="__('Destination')"
        :value="$destinationName"
        :locked="$locked->contains('destination_name')"
        required
    />
    <x-input-error class="mt-2" :messages="$errors->get('destination_name')" />

    <div class="bucha-wizard-grid">
        <div>
            <x-wizard-field for="destination_country" :label="__('Country')">
                @if ($locked->contains('destination_country'))
                    <p class="bucha-wizard-input flex items-center bg-slate-50 text-slate-800 border-slate-200">
                        {{ filled($destinationCountry) ? $countryLabel : '—' }}
                    </p>
                    <input
                        type="hidden"
                        id="destination_country"
                        name="destination_country"
                        value="{{ $destinationCountry }}"
                        data-certificate-sourced="1"
                    />
                @else
                    <select id="destination_country" name="destination_country" class="bucha-wizard-select">
                        <option value="">{{ __('Select country') }}</option>
                        @foreach ($countries as $code => $label)
                            <option value="{{ $code }}" @selected($destinationCountry === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </x-wizard-field>
            <x-input-error class="mt-2" :messages="$errors->get('destination_country')" />
        </div>
        <div>
            <x-certificate-sourced-field
                name="destination_address"
                :label="__('Address (optional)')"
                :value="$destinationAddress"
                :locked="$locked->contains('destination_address')"
            />
            <x-input-error class="mt-2" :messages="$errors->get('destination_address')" />
        </div>
    </div>
</x-wizard-section>
