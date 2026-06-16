@props([
    'trip' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
])

@php
    $locked = collect($lockedTransportFields);
    $vehicle = old('vehicle_plate_number', $trip?->vehicle_plate_number ?? ($transportDefaults['vehicle_plate_number'] ?? ''));
    $driver = old('driver_name', $trip?->driver_name ?? ($transportDefaults['driver_name'] ?? ''));
    $phone = old('driver_phone', $trip?->driver_phone ?? ($transportDefaults['driver_phone'] ?? ''));
@endphp

<div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-4">
    <div>
        <p class="text-sm font-medium text-slate-800">{{ __('Vehicle and driver') }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ __('Filled from the certificate when available. Locked fields must match the certificate transporter section.') }}</p>
    </div>

    <div>
        <x-input-label for="vehicle_plate_number" :value="__('Vehicle plate number')" />
        @if ($locked->contains('vehicle_plate_number'))
            <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 font-mono">{{ $vehicle }}</p>
            <input type="hidden" name="vehicle_plate_number" value="{{ $vehicle }}">
            <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate — edit on the certificate if this must change.') }}</p>
        @else
            <x-text-input id="vehicle_plate_number" name="vehicle_plate_number" type="text" class="mt-1 block w-full" :value="$vehicle" required />
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate_number')" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="driver_name" :value="__('Driver name')" />
            @if ($locked->contains('driver_name'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $driver }}</p>
                <input type="hidden" name="driver_name" value="{{ $driver }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate') }}</p>
            @else
                <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="$driver" required />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
        </div>
        <div>
            <x-input-label for="driver_phone" :value="__('Driver phone')" />
            @if ($locked->contains('driver_phone'))
                <p class="mt-1 text-sm text-gray-900 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">{{ $phone ?: '—' }}</p>
                <input type="hidden" name="driver_phone" value="{{ $phone }}">
                <p class="mt-1 text-xs text-emerald-700">{{ __('From certificate') }}</p>
            @else
                <x-text-input id="driver_phone" name="driver_phone" type="text" class="mt-1 block w-full" :value="$phone" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('driver_phone')" />
        </div>
    </div>
</div>

<script>
(function() {
    window.applyTransportDefaultsFromCertificate = function(defaults, lockedFields) {
        var locked = lockedFields || [];
        var fields = {
            vehicle_plate_number: document.getElementById('vehicle_plate_number'),
            driver_name: document.getElementById('driver_name'),
            driver_phone: document.getElementById('driver_phone'),
        };

        Object.keys(fields).forEach(function(key) {
            var input = fields[key];
            if (!input || locked.indexOf(key) !== -1) {
                return;
            }
            if (!input.value && defaults[key]) {
                input.value = defaults[key];
            }
        });

        var destinationName = document.getElementById('destination_name');
        if (destinationName && !destinationName.value && defaults.departure_destination) {
            destinationName.value = defaults.departure_destination;
        }
    };
})();
</script>
