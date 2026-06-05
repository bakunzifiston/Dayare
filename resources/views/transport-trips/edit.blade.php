<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('transport-trips.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transport') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit transport trip') }} — {{ $trip->vehicle_plate_number }}
                </h2>
            </div>
            <a href="{{ route('transport-trips.show', $trip) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">{{ __('Back to trip') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('transport-trips.update', $trip) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('transport-trips.partials.storage-source-fields', [
                        'trip' => $trip,
                        'releasedStorages' => $releasedStorages ?? [],
                        'certificates' => $certificates,
                        'batches' => $batches,
                        'hasReleasedStorages' => $hasReleasedStorages ?? false,
                    ])

                    <div>
                        <x-input-label for="origin_facility_id" :value="__('Origin facility')" />
                        <select id="origin_facility_id" name="origin_facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('origin_facility_id', $trip->origin_facility_id) == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('origin_facility_id')" />
                    </div>

                    @include('transport-trips.partials.destination-fields', ['trip' => $trip, 'facilities' => $facilities])

                    <div>
                        <x-input-label for="vehicle_plate_number" :value="__('Vehicle plate number')" />
                        <x-text-input id="vehicle_plate_number" name="vehicle_plate_number" type="text" class="mt-1 block w-full" :value="old('vehicle_plate_number', $trip->vehicle_plate_number)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="driver_name" :value="__('Driver name')" />
                            <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="old('driver_name', $trip->driver_name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
                        </div>
                        <div>
                            <x-input-label for="driver_phone" :value="__('Driver phone')" />
                            <x-text-input id="driver_phone" name="driver_phone" type="text" class="mt-1 block w-full" :value="old('driver_phone', $trip->driver_phone)" />
                            <x-input-error class="mt-2" :messages="$errors->get('driver_phone')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="departure_date" :value="__('Departure date')" />
                            <x-text-input id="departure_date" name="departure_date" type="date" class="mt-1 block w-full" :value="old('departure_date', $trip->departure_date->format('Y-m-d'))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('departure_date')" />
                        </div>
                        <div>
                            <x-input-label for="arrival_date" :value="__('Arrival date')" />
                            <x-text-input id="arrival_date" name="arrival_date" type="date" class="mt-1 block w-full" :value="old('arrival_date', $trip->arrival_date?->format('Y-m-d'))" />
                            <x-input-error class="mt-2" :messages="$errors->get('arrival_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\TransportTrip::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $trip->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update trip') }}</x-primary-button>
                        <a href="{{ route('transport-trips.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
