@php
    $confirmation = $confirmation ?? null;
    $trips = $trips ?? [];
    $receivedUnits = $receivedUnits ?? [];
    $preselectedTripId = $preselectedTripId ?? null;
    $submitLabel = $submitLabel ?? __('Save confirmation');
    $destinationCountries = collect($destinationCountries ?? config('processor.destination_countries', []))
        ->mapWithKeys(fn ($label, $code) => [strtoupper((string) $code) => $label])
        ->all();

    $selectedTripId = old('transport_trip_id', $confirmation?->transport_trip_id ?? $preselectedTripId);
    $selectedTrip = collect($trips)->firstWhere('id', (int) $selectedTripId);
    $lockedReceiverFields = collect($selectedTrip['locked_receiver_fields'] ?? []);
    $receiverDefaults = $selectedTrip['receiver_defaults'] ?? [];
    $receiverName = old('receiver_name', $confirmation?->receiver_name ?? ($receiverDefaults['receiver_name'] ?? ''));
    $receiverCountry = strtoupper((string) old(
        'receiver_country',
        $confirmation?->receiver_country ?? ($receiverDefaults['receiver_country'] ?? '')
    ));
    $receiverAddress = old('receiver_address', $confirmation?->receiver_address ?? ($receiverDefaults['receiver_address'] ?? ''));
    $tripsList = collect($trips);
@endphp

<div class="bucha-wizard-form">
    <x-wizard-section :title="__('Transport')">
        <x-wizard-field for="transport_trip_id" :label="__('Transport trip')" required>
            <select id="transport_trip_id" name="transport_trip_id" class="bucha-wizard-select" required @disabled($tripsList->isEmpty())>
                <option value="">{{ __('Select transport trip') }}</option>
                @foreach ($tripsList as $t)
                    <option value="{{ $t['id'] }}"
                        data-context='@json($t['context'] ?? [])'
                        data-receiver-defaults='@json($t['receiver_defaults'] ?? [])'
                        data-locked-receiver-fields='@json($t['locked_receiver_fields'] ?? [])'
                        @selected((int) $selectedTripId === (int) $t['id'])>{{ $t['label'] }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('transport_trip_id')" />

        @if ($tripsList->isEmpty())
            <div class="rounded-bucha border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                {{ __('No transport trips are waiting for confirmation. Record a trip first, or open Confirm from a trip that has no delivery yet.') }}
            </div>
        @endif

        <div id="trip-context-panel" class="hidden rounded-bucha border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-700">
            <p class="text-sm font-semibold text-slate-800">{{ __('Linked transport') }}</p>
            <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Certificate') }}</dt>
                    <dd id="trip-context-certificate" class="mt-0.5 text-slate-900">—</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Route') }}</dt>
                    <dd id="trip-context-route" class="mt-0.5 text-slate-900">—</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Driver') }}</dt>
                    <dd id="trip-context-driver" class="mt-0.5 text-slate-900">—</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Destination') }}</dt>
                    <dd id="trip-context-destination" class="mt-0.5 text-slate-900">—</dd>
                </div>
            </dl>
        </div>
    </x-wizard-section>

    @include('delivery-confirmations.partials.receiver-fields', [
        'receiverName' => $receiverName,
        'receiverCountry' => $receiverCountry,
        'receiverAddress' => $receiverAddress,
        'lockedReceiverFields' => $lockedReceiverFields,
        'destinationCountries' => $destinationCountries,
    ])

    <x-wizard-section :title="__('Receipt')">
        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="received_quantity" :label="__('Received quantity')" required>
                    <input id="received_quantity" name="received_quantity" type="number" min="0" class="bucha-wizard-input" value="{{ old('received_quantity', $confirmation?->received_quantity ?? 0) }}" required />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('received_quantity')" />
            </div>
            <div>
                <x-wizard-field for="received_unit" :label="__('Unit')">
                    <select id="received_unit" name="received_unit" class="bucha-wizard-select">
                        @foreach ($receivedUnits as $unit)
                            <option value="{{ $unit->value }}" @selected(old('received_unit', $confirmation?->received_unit ?? 'units') === $unit->value)>{{ $unit->label() }}</option>
                        @endforeach
                    </select>
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('received_unit')" />
            </div>
        </div>

        <x-wizard-field for="received_date" :label="__('Received date')" required>
            <input id="received_date" name="received_date" type="date" class="bucha-wizard-input" value="{{ old('received_date', $confirmation?->received_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('received_date')" />

        <x-wizard-field for="confirmation_status" :label="__('Confirmation status')">
            <select id="confirmation_status" name="confirmation_status" class="bucha-wizard-select">
                @foreach (\App\Models\DeliveryConfirmation::STATUSES as $s)
                    <option value="{{ $s }}" @selected(old('confirmation_status', $confirmation?->confirmation_status ?? 'pending') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('confirmation_status')" />
    </x-wizard-section>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <x-primary-button :disabled="$tripsList->isEmpty()">{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('delivery-confirmations.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
            {{ __('Cancel') }}
        </a>
    </div>
</div>

@once
<script>
(function() {
    var countryLabels = @json($destinationCountries);
    var tripSelect = document.getElementById('transport_trip_id');
    var contextPanel = document.getElementById('trip-context-panel');
    var labels = {
        receiver_name: @json(__('Receiver name')),
        receiver_country: @json(__('Receiver country (optional)')),
        receiver_address: @json(__('Receiver address (optional)')),
    };

    if (!tripSelect || !contextPanel) {
        return;
    }

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

    function renderCountryField(container, value, isLocked) {
        value = value ? String(value).toUpperCase() : '';
        var fieldWrap = document.createElement('div');
        fieldWrap.className = 'bucha-wizard-field';

        var label = document.createElement('label');
        label.className = 'bucha-wizard-label';
        label.setAttribute('for', 'receiver_country');
        label.textContent = labels.receiver_country;
        fieldWrap.appendChild(label);

        var control = document.createElement('div');
        control.className = 'bucha-wizard-control';

        if (isLocked) {
            var display = document.createElement('p');
            display.className = 'bucha-wizard-input flex items-center bg-slate-50 text-slate-800 border-slate-200';
            display.textContent = value ? (countryLabels[value] || value) : '—';
            control.appendChild(display);

            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'receiver_country';
            hidden.id = 'receiver_country';
            hidden.value = value;
            control.appendChild(hidden);
        } else {
            var select = document.createElement('select');
            select.name = 'receiver_country';
            select.id = 'receiver_country';
            select.className = 'bucha-wizard-select';

            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = @json(__('Select country'));
            select.appendChild(empty);

            Object.keys(countryLabels).forEach(function(code) {
                var opt = document.createElement('option');
                opt.value = code;
                opt.textContent = countryLabels[code];
                if (code === value) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });

            if (value && !countryLabels[value]) {
                var extra = document.createElement('option');
                extra.value = value;
                extra.textContent = value;
                extra.selected = true;
                select.appendChild(extra);
            }

            control.appendChild(select);
        }

        fieldWrap.appendChild(control);
        container.appendChild(fieldWrap);
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

            if (field === 'receiver_country') {
                renderCountryField(container, value, isLocked);
                return;
            }

            var fieldWrap = document.createElement('div');
            fieldWrap.className = 'bucha-wizard-field';

            var label = document.createElement('label');
            label.className = 'bucha-wizard-label';
            label.setAttribute('for', field);
            label.textContent = labels[field];
            if (field === 'receiver_name' && !isLocked) {
                var star = document.createElement('span');
                star.className = 'text-bucha-primary';
                star.setAttribute('aria-hidden', 'true');
                star.textContent = ' *';
                label.appendChild(star);
            }
            fieldWrap.appendChild(label);

            var control = document.createElement('div');
            control.className = 'bucha-wizard-control';

            if (isLocked) {
                var display = document.createElement('p');
                display.className = 'bucha-wizard-input flex items-center bg-slate-50 text-slate-800 border-slate-200';
                display.textContent = value || '—';
                control.appendChild(display);

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = field;
                hidden.id = field;
                hidden.value = value;
                control.appendChild(hidden);
            } else {
                var input = document.createElement('input');
                input.type = 'text';
                input.name = field;
                input.id = field;
                input.className = 'bucha-wizard-input';
                input.value = value;
                if (field === 'receiver_name') {
                    input.required = true;
                }
                control.appendChild(input);
            }

            fieldWrap.appendChild(control);
            container.appendChild(fieldWrap);
        });
    }

    function updateTripContext() {
        var opt = tripSelect.options[tripSelect.selectedIndex];
        if (!opt || !opt.value) {
            contextPanel.classList.add('hidden');
            return;
        }

        var context = parseJsonAttr(opt, 'context');
        var defaults = parseJsonAttr(opt, 'receiverDefaults');
        var locked = parseJsonAttr(opt, 'lockedReceiverFields');

        if (!context || defaults === null || locked === null) {
            contextPanel.classList.add('hidden');
            return;
        }

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

    // Show linked-trip summary on load, but only re-render receiver inputs when the trip changes
    // so validation errors / old input from a failed save are not wiped.
    (function showInitialTripContext() {
        var opt = tripSelect.options[tripSelect.selectedIndex];
        if (!opt || !opt.value) {
            contextPanel.classList.add('hidden');
            return;
        }

        var context = parseJsonAttr(opt, 'context');
        if (!context) {
            contextPanel.classList.add('hidden');
            return;
        }

        contextPanel.classList.remove('hidden');
        document.getElementById('trip-context-certificate').textContent = context.certificate_number
            ? context.certificate_number + (context.batch_code ? ' (' + context.batch_code + ')' : '')
            : '—';
        document.getElementById('trip-context-route').textContent = [context.origin, context.destination].filter(Boolean).join(' → ') || '—';
        document.getElementById('trip-context-driver').textContent = [context.driver_name, context.vehicle_plate_number].filter(Boolean).join(' · ') || '—';
        document.getElementById('trip-context-destination').textContent = context.destination || '—';
    })();
})();
</script>
@endonce
