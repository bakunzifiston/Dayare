@props([
    'confirmation' => null,
    'trips' => [],
    'facilities' => [],
    'clients' => [],
    'receivedUnits' => [],
    'contractsUrl' => '',
    'preselectedTripId' => null,
])

@php
    $isEdit = $confirmation !== null;
@endphp

<div>
    <x-input-label for="transport_trip_id" :value="__('Transport trip')" />
    <select id="transport_trip_id" name="transport_trip_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
        @foreach ($trips as $t)
            <option value="{{ $t['id'] }}"
                data-external-destination="{{ ! empty($t['external_destination']) ? '1' : '0' }}"
                data-destination-name="{{ e($t['destination_name'] ?? '') }}"
                data-destination-country="{{ e($t['destination_country'] ?? '') }}"
                data-destination-address="{{ e($t['destination_address'] ?? '') }}"
                @selected(old('transport_trip_id', $confirmation?->transport_trip_id ?? $preselectedTripId) == $t['id'])>{{ $t['label'] }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('transport_trip_id')" />
</div>

<div>
    <x-input-label for="receiving_facility_id" :value="__('Receiving facility')" />
    <select id="receiving_facility_id" name="receiving_facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
        <option value="">{{ __('External / non-registered (e.g. client in another country)') }}</option>
        @foreach ($facilities as $f)
            <option value="{{ $f['id'] }}" @selected(old('receiving_facility_id', $confirmation?->receiving_facility_id) == $f['id'])>{{ $f['label'] }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-sm text-gray-500">{{ __('Choose the first option (External) to show client, contract, and international export fields. Required when the receiver is in another country.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('receiving_facility_id')" />
</div>

<div id="client-block" class="hidden space-y-4">
    <div>
        <x-input-label for="client_id" :value="__('Link to client (optional)')" />
        <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
            <option value="">{{ __('No client') }}</option>
            @foreach ($clients as $c)
                <option value="{{ $c['id'] }}" data-name="{{ e($c['name']) }}" data-country="{{ e($c['country']) }}" data-address="{{ e($c['address']) }}" @selected(old('client_id', $confirmation?->client_id) == $c['id'])>{{ $c['label'] }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
    </div>
    <div>
        <x-input-label for="contract_id" :value="__('Customer contract (optional)')" />
        <select id="contract_id" name="contract_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" disabled>
            <option value="">{{ __('Select a client first') }}</option>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('contract_id')" />
    </div>
</div>

<div>
    <x-input-label for="receiver_name" :value="__('Receiver name')" />
    <x-text-input id="receiver_name" name="receiver_name" type="text" class="mt-1 block w-full" :value="old('receiver_name', $confirmation?->receiver_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('receiver_name')" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="receiver_country" :value="__('Receiver country (optional)')" />
        <x-text-input id="receiver_country" name="receiver_country" type="text" class="mt-1 block w-full" :value="old('receiver_country', $confirmation?->receiver_country)" />
        <x-input-error class="mt-2" :messages="$errors->get('receiver_country')" />
    </div>
    <div>
        <x-input-label for="receiver_address" :value="__('Receiver address (optional)')" />
        <x-text-input id="receiver_address" name="receiver_address" type="text" class="mt-1 block w-full" :value="old('receiver_address', $confirmation?->receiver_address)" />
        <x-input-error class="mt-2" :messages="$errors->get('receiver_address')" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="received_quantity" :value="__('Received quantity')" />
        <x-text-input id="received_quantity" name="received_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('received_quantity', $confirmation?->received_quantity ?? 0)" required />
        <x-input-error class="mt-2" :messages="$errors->get('received_quantity')" />
    </div>
    <div>
        <x-input-label for="received_unit" :value="__('Unit')" />
        <select id="received_unit" name="received_unit" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
            @foreach ($receivedUnits as $unit)
                <option value="{{ $unit->value }}" @selected(old('received_unit', $confirmation?->received_unit ?? 'units') === $unit->value)>{{ $unit->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('received_unit')" />
    </div>
</div>

<div>
    <x-input-label for="received_date" :value="__('Received date')" />
    <x-text-input id="received_date" name="received_date" type="date" class="mt-1 block w-full" :value="old('received_date', $confirmation?->received_date?->format('Y-m-d') ?? date('Y-m-d'))" required />
    <x-input-error class="mt-2" :messages="$errors->get('received_date')" />
</div>

<div>
    <x-input-label for="confirmation_status" :value="__('Confirmation status')" />
    <select id="confirmation_status" name="confirmation_status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
        @foreach (\App\Models\DeliveryConfirmation::STATUSES as $s)
            <option value="{{ $s }}" @selected(old('confirmation_status', $confirmation?->confirmation_status ?? 'pending') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('confirmation_status')" />
</div>

<script>
(function() {
    var contractsUrl = @json($contractsUrl);
    var initialContractId = @json(old('contract_id', $confirmation?->contract_id));
    var tripSelect = document.getElementById('transport_trip_id');
    var receivingSelect = document.getElementById('receiving_facility_id');
    var clientBlock = document.getElementById('client-block');
    var clientSelect = document.getElementById('client_id');
    var contractSelect = document.getElementById('contract_id');

    function toggleClientBlock() {
        var isExternal = !receivingSelect.value;
        clientBlock.classList.toggle('hidden', !isExternal);
        if (!isExternal) {
            clientSelect.value = '';
            resetContracts(@json(__('Select a client first')), true);
        }
    }

    function resetContracts(placeholder, disabled) {
        contractSelect.innerHTML = '<option value="">' + placeholder + '</option>';
        contractSelect.disabled = disabled;
    }

    async function loadContracts(clientId) {
        if (!clientId) {
            resetContracts(@json(__('Select a client first')), true);
            return;
        }
        resetContracts(@json(__('Loading…')), true);
        try {
            var res = await fetch(contractsUrl + '?client_id=' + encodeURIComponent(clientId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var items = await res.json();
            resetContracts(@json(__('None')), false);
            items.forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = (item.reference || '#' + item.id) + (item.title ? ' — ' + item.title : '');
                if (String(initialContractId) === String(item.id)) {
                    opt.selected = true;
                    initialContractId = null;
                }
                contractSelect.appendChild(opt);
            });
        } catch (e) {
            resetContracts(@json(__('Could not load contracts')), true);
        }
    }

    function prefillFromClient() {
        var opt = clientSelect.options[clientSelect.selectedIndex];
        if (!opt || !opt.value) return;
        var receiverName = document.getElementById('receiver_name');
        var receiverCountry = document.getElementById('receiver_country');
        var receiverAddress = document.getElementById('receiver_address');
        if (receiverName) receiverName.value = opt.dataset.name || '';
        if (receiverCountry) receiverCountry.value = opt.dataset.country || '';
        if (receiverAddress) receiverAddress.value = opt.dataset.address || '';
    }

    function prefillFromTrip() {
        var opt = tripSelect.options[tripSelect.selectedIndex];
        if (!opt || !opt.value) return;
        if (opt.dataset.externalDestination !== '1') return;
        receivingSelect.value = '';
        toggleClientBlock();
        var receiverName = document.getElementById('receiver_name');
        var receiverCountry = document.getElementById('receiver_country');
        var receiverAddress = document.getElementById('receiver_address');
        if (receiverName && opt.dataset.destinationName) receiverName.value = opt.dataset.destinationName;
        if (receiverCountry && opt.dataset.destinationCountry) receiverCountry.value = opt.dataset.destinationCountry;
        if (receiverAddress && opt.dataset.destinationAddress) receiverAddress.value = opt.dataset.destinationAddress;
    }

    receivingSelect.addEventListener('change', toggleClientBlock);
    tripSelect.addEventListener('change', prefillFromTrip);
    clientSelect.addEventListener('change', function() {
        prefillFromClient();
        loadContracts(clientSelect.value);
    });
    toggleClientBlock();
    if (clientSelect.value) {
        loadContracts(clientSelect.value);
    }
    prefillFromTrip();
})();
</script>
