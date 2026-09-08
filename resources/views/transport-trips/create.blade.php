<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('transport-trips.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transport') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Record transport trip') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bucha-wizard-panel">
                <form method="post" action="{{ route('transport-trips.store') }}">
                    @csrf
                    @include('transport-trips.partials.form-fields', [
                        'certificates' => $certificates,
                        'facilities' => $facilities,
                        'selectedCertificateId' => $selectedCertificateId ?? null,
                        'transportDefaults' => $transportDefaults ?? [],
                        'lockedTransportFields' => $lockedTransportFields ?? [],
                        'destinationCountries' => $destinationCountries ?? [],
                        'submitLabel' => __('Save trip'),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
