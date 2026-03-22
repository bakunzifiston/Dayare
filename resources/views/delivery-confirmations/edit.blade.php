<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit delivery confirmation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('delivery-confirmations.update', $confirmation) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="transport_trip_id" :value="__('Transport trip')" />
                        <select id="transport_trip_id" name="transport_trip_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($trips as $t)
                                <option value="{{ $t['id'] }}" @selected(old('transport_trip_id', $confirmation->transport_trip_id) == $t['id'])>{{ $t['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('transport_trip_id')" />
                    </div>

                    <div>
                        <x-input-label for="receiving_facility_id" :value="__('Receiving facility')" />
                        <select id="receiving_facility_id" name="receiving_facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            <option value="">{{ __('External / non-registered') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" @selected(old('receiving_facility_id', $confirmation->receiving_facility_id) == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('receiving_facility_id')" />
                    </div>

                    <div id="client-block" class="hidden">
                        <x-input-label for="client_id" :value="__('Link to client (optional)')" />
                        <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            <option value="">{{ __('No client') }}</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c['id'] }}" data-name="{{ e($c['name']) }}" data-country="{{ e($c['country']) }}" data-address="{{ e($c['address']) }}" @selected(old('client_id', $confirmation->client_id) == $c['id'])>{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                    </div>

                    <div>
                        <x-input-label for="receiver_name" :value="__('Receiver name')" />
                        <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full" :value="old('receiver_name', $confirmation->receiver_name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="receiver_country" :value="__('Receiver country (optional)')" />
                            <x-text-input id="receiver_country" name="receiver_country" type="text" class="mt-1 block w-full" :value="old('receiver_country', $confirmation->receiver_country)" />
                            <x-input-error class="mt-2" :messages="$errors->get('receiver_country')" />
                        </div>
                        <div>
                            <x-input-label for="receiver_address" :value="__('Receiver address (optional)')" />
                            <x-text-input id="receiver_address" name="receiver_address" type="text" class="mt-1 block w-full" :value="old('receiver_address', $confirmation->receiver_address)" />
                            <x-input-error class="mt-2" :messages="$errors->get('receiver_address')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="received_quantity" :value="__('Received quantity')" />
                        <x-text-input id="received_quantity" name="received_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('received_quantity', $confirmation->received_quantity)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('received_quantity')" />
                    </div>

                    <div>
                        <x-input-label for="received_date" :value="__('Received date')" />
                        <x-text-input id="received_date" name="received_date" type="date" class="mt-1 block w-full" :value="old('received_date', $confirmation->received_date->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('received_date')" />
                    </div>

                    <div>
                        <x-input-label for="confirmation_status" :value="__('Confirmation status')" />
                        <select id="confirmation_status" name="confirmation_status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\DeliveryConfirmation::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('confirmation_status', $confirmation->confirmation_status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('confirmation_status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update confirmation') }}</x-primary-button>
                        <a href="{{ route('delivery-confirmations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function() {
            var receivingSelect = document.getElementById('receiving_facility_id');
            var clientBlock = document.getElementById('client-block');
            var clientSelect = document.getElementById('client_id');
            var receiverName = document.getElementById('receiver_name');
            var receiverCountry = document.getElementById('receiver_country');
            var receiverAddress = document.getElementById('receiver_address');
            function toggleClientBlock() { clientBlock.classList.toggle('hidden', !!receivingSelect.value); }
            function prefillFromClient() {
                var opt = clientSelect.options[clientSelect.selectedIndex];
                if (!opt || !opt.value) return;
                if (receiverName) receiverName.value = opt.dataset.name || '';
                if (receiverCountry) receiverCountry.value = opt.dataset.country || '';
                if (receiverAddress) receiverAddress.value = opt.dataset.address || '';
            }
            receivingSelect.addEventListener('change', toggleClientBlock);
            clientSelect.addEventListener('change', prefillFromClient);
            toggleClientBlock();
        })();
    </script>
</x-app-layout>
