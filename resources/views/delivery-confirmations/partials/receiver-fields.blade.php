@php
    $receiverName = $receiverName ?? '';
    $receiverCountry = strtoupper((string) ($receiverCountry ?? ''));
    $receiverAddress = $receiverAddress ?? '';
    $locked = collect($lockedReceiverFields ?? []);
    $countries = collect($destinationCountries ?? config('processor.destination_countries', []))
        ->mapWithKeys(fn ($label, $code) => [strtoupper((string) $code) => $label])
        ->all();

    if ($receiverCountry !== '' && ! array_key_exists($receiverCountry, $countries)) {
        $countries[$receiverCountry] = $receiverCountry;
    }

    $countryLabel = $countries[$receiverCountry] ?? $receiverCountry;
@endphp

<x-wizard-section :title="__('Receiver')">
    <div>
        <div id="receiver_name_field">
            <x-certificate-sourced-field
                name="receiver_name"
                :label="__('Receiver name')"
                :value="$receiverName"
                :locked="$locked->contains('receiver_name')"
                required
            />
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
    </div>

    <div class="bucha-wizard-grid">
        <div>
            <div id="receiver_country_field">
                <x-wizard-field for="receiver_country" :label="__('Receiver country (optional)')">
                    @if ($locked->contains('receiver_country'))
                        <p class="bucha-wizard-input flex items-center bg-slate-50 text-slate-800 border-slate-200">
                            {{ filled($receiverCountry) ? $countryLabel : '—' }}
                        </p>
                        <input type="hidden" name="receiver_country" id="receiver_country" value="{{ $receiverCountry }}">
                    @else
                        <select id="receiver_country" name="receiver_country" class="bucha-wizard-select">
                            <option value="">{{ __('Select country') }}</option>
                            @foreach ($countries as $code => $label)
                                <option value="{{ $code }}" @selected($receiverCountry === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </x-wizard-field>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('receiver_country')" />
        </div>
        <div>
            <div id="receiver_address_field">
                <x-certificate-sourced-field
                    name="receiver_address"
                    :label="__('Receiver address (optional)')"
                    :value="$receiverAddress"
                    :locked="$locked->contains('receiver_address')"
                />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('receiver_address')" />
        </div>
    </div>
</x-wizard-section>
