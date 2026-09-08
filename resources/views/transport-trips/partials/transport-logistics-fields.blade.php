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

<x-wizard-section :title="__('Vehicle and driver')">
    <x-certificate-sourced-field
        name="vehicle_plate_number"
        :label="__('Vehicle plate number')"
        :value="$vehicle"
        :locked="$locked->contains('vehicle_plate_number')"
        required
        mono
    />
    <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate_number')" />

    <div class="bucha-wizard-grid">
        <div>
            <x-certificate-sourced-field
                name="driver_name"
                :label="__('Driver name')"
                :value="$driver"
                :locked="$locked->contains('driver_name')"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
        </div>
        <div>
            <x-certificate-sourced-field
                name="driver_phone"
                :label="__('Driver phone')"
                :value="$phone"
                :locked="$locked->contains('driver_phone')"
                type="tel"
            />
            <x-input-error class="mt-2" :messages="$errors->get('driver_phone')" />
        </div>
    </div>
</x-wizard-section>
