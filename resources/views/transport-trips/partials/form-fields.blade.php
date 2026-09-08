@props([
    'trip' => null,
    'certificates' => [],
    'facilities' => [],
    'selectedCertificateId' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
    'destinationCountries' => [],
    'submitLabel',
])

@php
    $isEdit = $trip !== null;
@endphp

<div class="bucha-wizard-form">
    @include('transport-trips.partials.certificate-source-fields', [
        'trip' => $trip,
        'certificates' => $certificates,
        'selectedCertificateId' => $selectedCertificateId,
        'transportDefaults' => $transportDefaults,
        'lockedTransportFields' => $lockedTransportFields,
    ])

    <x-wizard-section :title="__('Origin')">
        <x-wizard-field for="origin_facility_id" :label="__('Origin facility')" required>
            <select id="origin_facility_id" name="origin_facility_id" class="bucha-wizard-select" required>
                @unless ($isEdit)
                    <option value="">{{ __('Select facility') }}</option>
                @endunless
                @foreach ($facilities as $f)
                    <option value="{{ $f['id'] }}" @selected(old('origin_facility_id', $trip?->origin_facility_id) == $f['id'])>{{ $f['label'] }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('origin_facility_id')" />
    </x-wizard-section>

    @include('transport-trips.partials.destination-fields', [
        'trip' => $trip,
        'transportDefaults' => $transportDefaults,
        'lockedTransportFields' => $lockedTransportFields,
        'destinationCountries' => $destinationCountries ?? [],
    ])

    @include('transport-trips.partials.transport-logistics-fields', [
        'trip' => $trip,
        'transportDefaults' => $transportDefaults,
        'lockedTransportFields' => $lockedTransportFields,
    ])

    @include('transport-trips.partials.trip-date-fields', [
        'trip' => $trip,
        'transportDefaults' => $transportDefaults,
        'lockedTransportFields' => $lockedTransportFields,
    ])

    <x-wizard-section :title="__('Status')">
        <x-wizard-field for="status" :label="__('Trip status')">
            <select id="status" name="status" class="bucha-wizard-select">
                @foreach (\App\Models\TransportTrip::STATUSES as $s)
                    <option value="{{ $s }}" @selected(old('status', $trip?->status ?? 'pending') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </x-wizard-section>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('transport-trips.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
            {{ __('Cancel') }}
        </a>
    </div>
</div>
