@props(['trip' => null, 'facilities' => []])

@php
    $isExternal = old('destination_type', ($trip && $trip->isExternalDestination()) ? 'external' : 'facility') === 'external';
@endphp

<div class="space-y-4">
    <div>
        <x-input-label :value="__('Destination type')" />
        <div class="mt-2 space-y-2">
            <label class="flex items-center gap-2">
                <input type="radio" name="destination_type" value="facility" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked(! $isExternal)>
                <span class="text-sm">{{ __('Registered facility') }}</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="destination_type" value="external" class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary" @checked($isExternal)>
                <span class="text-sm">{{ __('Other place (export, client site, border, etc.)') }}</span>
            </label>
        </div>
    </div>

    <div id="destination-facility-block" @class(['hidden' => $isExternal])>
        <x-input-label for="destination_facility_id" :value="__('Destination facility')" />
        <select id="destination_facility_id" name="destination_facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
            <option value="">{{ __('Select facility') }}</option>
            @foreach ($facilities as $f)
                <option value="{{ $f['id'] }}" @selected(old('destination_facility_id', $trip?->destination_facility_id) == $f['id'])>{{ $f['label'] }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('destination_facility_id')" />
    </div>

    <div id="destination-external-block" @class(['hidden' => ! $isExternal, 'space-y-4' => true])>
        <div>
            <x-input-label for="destination_name" :value="__('Destination name / site')" />
            <x-text-input id="destination_name" name="destination_name" type="text" class="mt-1 block w-full" :value="old('destination_name', $trip?->destination_name)" />
            <p class="mt-1 text-xs text-gray-500">{{ __('e.g. client warehouse, border post, airport cargo') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('destination_name')" />
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="destination_country" :value="__('Country')" />
                <x-text-input id="destination_country" name="destination_country" type="text" class="mt-1 block w-full" :value="old('destination_country', $trip?->destination_country)" placeholder="e.g. KE, UG" />
                <x-input-error class="mt-2" :messages="$errors->get('destination_country')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="destination_address" :value="__('Address (optional)')" />
                <x-text-input id="destination_address" name="destination_address" type="text" class="mt-1 block w-full" :value="old('destination_address', $trip?->destination_address)" />
                <x-input-error class="mt-2" :messages="$errors->get('destination_address')" />
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var facilityBlock = document.getElementById('destination-facility-block');
    var externalBlock = document.getElementById('destination-external-block');
    var facilitySelect = document.getElementById('destination_facility_id');
    var radios = document.querySelectorAll('input[name="destination_type"]');

    function syncDestinationType() {
        var external = document.querySelector('input[name="destination_type"]:checked')?.value === 'external';
        facilityBlock.classList.toggle('hidden', external);
        externalBlock.classList.toggle('hidden', !external);
        facilitySelect.required = !external;
        facilitySelect.disabled = external;
        if (external) {
            facilitySelect.value = '';
        }
        document.getElementById('destination_name').required = external;
    }

    radios.forEach(function(r) { r.addEventListener('change', syncDestinationType); });
    syncDestinationType();
})();
</script>
