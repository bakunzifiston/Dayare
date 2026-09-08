<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('transport-trips.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transport') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Edit transport trip') }} — {{ $trip->vehicle_plate_number }}
                </h2>
            </div>
            <a href="{{ route('transport-trips.show', $trip) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-bucha font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">{{ __('Back to trip') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                <form method="post" action="{{ route('transport-trips.update', $trip) }}">
                    @csrf
                    @method('put')
                    @include('transport-trips.partials.form-fields', [
                        'trip' => $trip,
                        'certificates' => $certificates,
                        'facilities' => $facilities,
                        'selectedCertificateId' => $selectedCertificateId ?? null,
                        'transportDefaults' => $transportDefaults ?? [],
                        'lockedTransportFields' => $lockedTransportFields ?? [],
                        'destinationCountries' => $destinationCountries ?? [],
                        'submitLabel' => __('Update trip'),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
