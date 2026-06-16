<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('transport-trips.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transport') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Record transport trip') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('processor.partials.transport-workflow-callout')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('transport-trips.store') }}" class="space-y-6">
                    @csrf

                    @include('transport-trips.partials.certificate-source-fields', [
                        'certificates' => $certificates,
                        'selectedCertificateId' => $selectedCertificateId ?? null,
                        'transportDefaults' => $transportDefaults ?? [],
                        'lockedTransportFields' => $lockedTransportFields ?? [],
                    ])

                    <div>
                        <x-input-label for="origin_facility_id" :value="__('Origin facility')" />
                        <select id="origin_facility_id" name="origin_facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('origin_facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('origin_facility_id')" />
                    </div>

                    @include('transport-trips.partials.destination-fields', ['trip' => $trip ?? null])

                    @include('transport-trips.partials.transport-logistics-fields', [
                        'transportDefaults' => $transportDefaults ?? [],
                        'lockedTransportFields' => $lockedTransportFields ?? [],
                    ])

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="departure_date" :value="__('Departure date')" />
                            <x-text-input id="departure_date" name="departure_date" type="date" class="mt-1 block w-full" :value="old('departure_date')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('departure_date')" />
                        </div>
                        <div>
                            <x-input-label for="arrival_date" :value="__('Arrival date')" />
                            <x-text-input id="arrival_date" name="arrival_date" type="date" class="mt-1 block w-full" :value="old('arrival_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('arrival_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\TransportTrip::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'pending') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save trip') }}</x-primary-button>
                        <a href="{{ route('transport-trips.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
