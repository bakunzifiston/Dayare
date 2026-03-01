<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm delivery') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('delivery-confirmations.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="transport_trip_id" :value="__('Transport trip')" />
                        <select id="transport_trip_id" name="transport_trip_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select trip') }}</option>
                            @foreach ($trips as $t)
                                <option value="{{ $t['id'] }}" @selected(old('transport_trip_id') == $t['id'])>{{ $t['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('transport_trip_id')" />
                    </div>

                    <div>
                        <x-input-label for="receiving_facility_id" :value="__('Receiving facility')" />
                        <select id="receiving_facility_id" name="receiving_facility_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('receiving_facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('receiving_facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="received_quantity" :value="__('Received quantity')" />
                        <x-text-input id="received_quantity" name="received_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('received_quantity', 0)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('received_quantity')" />
                    </div>

                    <div>
                        <x-input-label for="received_date" :value="__('Received date')" />
                        <x-text-input id="received_date" name="received_date" type="date" class="mt-1 block w-full" :value="old('received_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('received_date')" />
                    </div>

                    <div>
                        <x-input-label for="receiver_name" :value="__('Receiver name')" />
                        <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full" :value="old('receiver_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
                    </div>

                    <div>
                        <x-input-label for="confirmation_status" :value="__('Confirmation status')" />
                        <select id="confirmation_status" name="confirmation_status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\DeliveryConfirmation::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('confirmation_status', 'pending') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('confirmation_status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save confirmation') }}</x-primary-button>
                        <a href="{{ route('delivery-confirmations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
