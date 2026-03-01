<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record transport trip') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('transport-trips.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="certificate_id" :value="__('Certificate')" />
                        <select id="certificate_id" name="certificate_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select certificate') }}</option>
                            @foreach ($certificates as $c)
                                <option value="{{ $c['id'] }}" @selected(old('certificate_id') == $c['id'])>{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('certificate_id')" />
                    </div>

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch (optional)')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" @selected(old('batch_id') == $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="origin_facility_id" :value="__('Origin facility')" />
                        <select id="origin_facility_id" name="origin_facility_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('origin_facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('origin_facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="destination_facility_id" :value="__('Destination facility')" />
                        <select id="destination_facility_id" name="destination_facility_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('destination_facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('destination_facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="vehicle_plate_number" :value="__('Vehicle plate number')" />
                        <x-text-input id="vehicle_plate_number" name="vehicle_plate_number" type="text" class="mt-1 block w-full" :value="old('vehicle_plate_number')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="driver_name" :value="__('Driver name')" />
                            <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="old('driver_name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
                        </div>
                        <div>
                            <x-input-label for="driver_phone" :value="__('Driver phone')" />
                            <x-text-input id="driver_phone" name="driver_phone" type="text" class="mt-1 block w-full" :value="old('driver_phone')" />
                            <x-input-error class="mt-2" :messages="$errors->get('driver_phone')" />
                        </div>
                    </div>

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
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\TransportTrip::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'pending') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save trip') }}</x-primary-button>
                        <a href="{{ route('transport-trips.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
