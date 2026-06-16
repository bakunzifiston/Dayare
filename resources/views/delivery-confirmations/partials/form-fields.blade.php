@props([
    'confirmation' => null,
    'trips' => [],
    'clients' => [],
    'receivedUnits' => [],
    'contractsUrl' => '',
    'preselectedTripId' => null,
])

@php
    $isEdit = $confirmation !== null;
    $selectedTripId = old('transport_trip_id', $confirmation?->transport_trip_id ?? $preselectedTripId);
    $selectedTrip = collect($trips)->firstWhere('id', (int) $selectedTripId);
    $lockedReceiverFields = collect($selectedTrip['locked_receiver_fields'] ?? []);
    $receiverDefaults = $selectedTrip['receiver_defaults'] ?? [];
    $receiverName = old('receiver_name', $confirmation?->receiver_name ?? ($receiverDefaults['receiver_name'] ?? ''));
    $receiverCountry = old('receiver_country', $confirmation?->receiver_country ?? ($receiverDefaults['receiver_country'] ?? ''));
    $receiverAddress = old('receiver_address', $confirmation?->receiver_address ?? ($receiverDefaults['receiver_address'] ?? ''));
@endphp

<div>
    <x-input-label for="transport_trip_id" :value="__('Transport trip')" />
    <select id="transport_trip_id" name="transport_trip_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
        @foreach ($trips as $t)
            <option value="{{ $t['id'] }}"
                data-context="{{ e(json_encode($t['context'] ?? [])) }}"
                data-receiver-defaults="{{ e(json_encode($t['receiver_defaults'] ?? [])) }}"
                data-locked-receiver-fields="{{ e(json_encode($t['locked_receiver_fields'] ?? [])) }}"
                @selected((int) $selectedTripId === (int) $t['id'])>{{ $t['label'] }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-sm text-gray-500">{{ __('Receiver details are taken from the trip destination and must match.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('transport_trip_id')" />
</div>

<div id="trip-context-panel" class="hidden rounded-lg border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-700">
    <p class="font-medium text-slate-800">{{ __('Linked transport') }}</p>
    <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Certificate') }}</dt>
            <dd id="trip-context-certificate" class="mt-0.5">—</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Route') }}</dt>
            <dd id="trip-context-route" class="mt-0.5">—</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Driver') }}</dt>
            <dd id="trip-context-driver" class="mt-0.5">—</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Destination') }}</dt>
            <dd id="trip-context-destination" class="mt-0.5">—</dd>
        </div>
    </dl>
</div>

<div id="client-block" class="space-y-4">
    <div>
        <x-input-label for="client_id" :value="__('Link to client (optional)')" />
        <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
            <option value="">{{ __('No client') }}</option>
            @foreach ($clients as $c)
                <option value="{{ $c['id'] }}" data-name="{{ e($c['name']) }}" data-country="{{ e($c['country']) }}" data-address="{{ e($c['address']) }}" @selected(old('client_id', $confirmation?->client_id) == $c['id'])>{{ $c['label'] }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">{{ __('Optional — link a client and contract without changing the trip destination.') }}</p>
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

@include('delivery-confirmations.partials.receiver-fields', [
    'receiverName' => $receiverName,
    'receiverCountry' => $receiverCountry,
    'receiverAddress' => $receiverAddress,
    'lockedReceiverFields' => $lockedReceiverFields,
])

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
    var clientSelect = document.getElementById('client_id');
    var contractSelect = document.getElementById('contract_id');
    var contextPanel = document.getElementById('trip-context-panel');

    function parseJsonAttr(el, attr) {
        if (!el || !el.dataset[attr]) {
            return null;
        }
        try {
            return JSON.parse(el.dataset[attr]);
        } catch (e) {
            return null;
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

    function renderReceiverFields(defaults, lockedFields) {
        var locked = lockedFields || [];
        var fields = ['receiver_name', 'receiver_country', 'receiver_address'];
        fields.forEach(function(field) {
            var container = document.getElementById(field + '_field');
            if (!container) {
                return;
            }
            var value = (defaults && defaults[field]) ? defaults[field] : '';
            var isLocked = locked.indexOf(field) !== -1 && value;
            container.innerHTML = '';

            var label = document.createElement('label');
            label.className = 'block font-medium text-sm text-gray-700';
            label.setAttribute('for', field);
            label.textContent = field === 'receiver_name'
                ? @json(__('Receiver name'))
                : (field === 'receiver_country' ? @json(__('Receiver country (optional)')) : @json(__('Receiver address (optional)')));
            container.appendChild(label);

            if (isLocked) {
                var display = document.createElement('p');
                display.className = 'mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2';
                display.textContent = value;
                container.appendChild(display);

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = field;
                hidden.id = field;
                hidden.value = value;
                container.appendChild(hidden);

                var hint = document.createElement('p');
                hint.className = 'mt-1 text-xs text-emerald-700';
                hint.textContent = @json(__('From transport trip — edit the trip if this must change.'));
                container.appendChild(hint);
            } else {
                var input = document.createElement('input');
                input.type = 'text';
                input.name = field;
                input.id = field;
                input.className = 'mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm';
                input.value = value;
                if (field === 'receiver_name') {
                    input.required = true;
                }
                container.appendChild(input);
            }
        });
    }

    function updateTripContext() {
        var opt = tripSelect.options[tripSelect.selectedIndex];
        if (!opt || !opt.value) {
            contextPanel.classList.add('hidden');
            return;
        }

        var context = parseJsonAttr(opt, 'context') || {};
        var defaults = parseJsonAttr(opt, 'receiverDefaults') || {};
        var locked = parseJsonAttr(opt, 'lockedReceiverFields') || [];

        contextPanel.classList.remove('hidden');
        document.getElementById('trip-context-certificate').textContent = context.certificate_number
            ? context.certificate_number + (context.batch_code ? ' (' + context.batch_code + ')' : '')
            : '—';
        document.getElementById('trip-context-route').textContent = [context.origin, context.destination].filter(Boolean).join(' → ') || '—';
        document.getElementById('trip-context-driver').textContent = [context.driver_name, context.vehicle_plate_number].filter(Boolean).join(' · ') || '—';
        document.getElementById('trip-context-destination').textContent = context.destination || '—';

        renderReceiverFields(defaults, locked);
    }

    tripSelect.addEventListener('change', updateTripContext);
    clientSelect.addEventListener('change', function() {
        loadContracts(clientSelect.value);
    });

    if (clientSelect.value) {
        loadContracts(clientSelect.value);
    }
    updateTripContext();
})();
</script>
