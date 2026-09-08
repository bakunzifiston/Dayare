@props([
    'trip' => null,
    'certificates' => [],
    'selectedCertificateId' => null,
    'transportDefaults' => [],
    'lockedTransportFields' => [],
])

@php
    $certificateId = old('certificate_id', $selectedCertificateId ?? $trip?->certificate_id);
    $selected = collect($certificates)->firstWhere('id', (int) $certificateId);
    $certificateDefaults = $transportDefaults ?: ($selected['transport_defaults'] ?? []);
    $certificateLocked = $lockedTransportFields ?: ($selected['locked_fields'] ?? []);
@endphp

<x-wizard-section :title="__('Certified product')">
    <x-wizard-field for="certificate_id" :label="__('Certificate')" required>
        <select id="certificate_id" name="certificate_id" class="bucha-wizard-select" required>
            <option value="">{{ __('Select certificate') }}</option>
            @foreach ($certificates as $c)
                <option value="{{ $c['id'] }}"
                    data-batch-id="{{ $c['batch_id'] ?? '' }}"
                    data-batch-label="{{ e($c['batch_label'] ?? '—') }}"
                    data-facility-id="{{ $c['facility_id'] ?? '' }}"
                    data-facility-label="{{ e($c['facility_label'] ?? '—') }}"
                    data-transport-defaults="{{ e(json_encode($c['transport_defaults'] ?? [])) }}"
                    data-locked-fields="{{ e(json_encode($c['locked_fields'] ?? [])) }}"
                    @selected((string) $certificateId === (string) $c['id'])>{{ $c['label'] }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('certificate_id')" />
    </x-wizard-field>

    <input type="hidden" id="trip_batch_id" name="batch_id" value="{{ old('batch_id', $trip?->batch_id ?? $selected['batch_id'] ?? '') }}" />

    <div id="certificate-derived-fields" @class(['bucha-wizard-grid', 'hidden' => ! $selected])>
        <x-wizard-field :label="__('Batch')">
            <p id="linked_batch_display" class="bucha-wizard-input flex items-center bg-slate-50 text-slate-800">{{ $selected['batch_label'] ?? '—' }}</p>
        </x-wizard-field>
        <x-wizard-field :label="__('Slaughter facility')">
            <p id="linked_facility_display" class="bucha-wizard-input flex items-center bg-slate-50 text-slate-800">{{ $selected['facility_label'] ?? '—' }}</p>
        </x-wizard-field>
    </div>
</x-wizard-section>

<script>
(function() {
    var countryLabels = @json(
        collect(config('processor.destination_countries', []))
            ->mapWithKeys(fn ($label, $code) => [strtoupper((string) $code) => $label])
            ->all()
    );

    window.applyTransportDefaultsFromCertificate = function(defaults, lockedFields) {
        var locked = lockedFields || [];
        var fields = [
            'vehicle_plate_number',
            'driver_name',
            'driver_phone',
            'destination_name',
            'destination_country',
            'destination_address',
            'departure_date',
        ];

        fields.forEach(function(key) {
            var input = document.getElementById(key);
            var value = defaults[key] || (key === 'destination_name' ? defaults.departure_destination : null);
            if (!input || !value) {
                return;
            }

            if (key === 'destination_country') {
                value = String(value).toUpperCase();
            }

            if (locked.indexOf(key) !== -1 || !input.value) {
                if (key === 'destination_country' && input.tagName === 'SELECT') {
                    var hasOption = Array.prototype.some.call(input.options, function(opt) {
                        return opt.value === value;
                    });
                    if (!hasOption) {
                        var opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = countryLabels[value] || value;
                        input.appendChild(opt);
                    }
                }

                input.value = value;
                var display = input.previousElementSibling;
                if (input.dataset.certificateSourced === '1' && display && display.tagName === 'P') {
                    display.textContent = key === 'destination_country'
                        ? (countryLabels[value] || value || '—')
                        : (value || '—');
                }
            }
        });
    };

    var certSelect = document.getElementById('certificate_id');
    var batchInput = document.getElementById('trip_batch_id');
    var derivedBlock = document.getElementById('certificate-derived-fields');
    var batchDisplay = document.getElementById('linked_batch_display');
    var facilityDisplay = document.getElementById('linked_facility_display');
    var originSelect = document.getElementById('origin_facility_id');

    function parseJson(value) {
        try {
            return JSON.parse(value || '{}');
        } catch (e) {
            return {};
        }
    }

    function syncCertificate() {
        var opt = certSelect.options[certSelect.selectedIndex];
        var hasCert = certSelect.value !== '';

        derivedBlock.classList.toggle('hidden', !hasCert);

        if (!hasCert) {
            batchInput.value = '';
            return;
        }

        batchInput.value = opt.dataset.batchId || '';
        batchDisplay.textContent = opt.dataset.batchLabel || '—';
        facilityDisplay.textContent = opt.dataset.facilityLabel || '—';

        if (originSelect && opt.dataset.facilityId) {
            originSelect.value = opt.dataset.facilityId;
        }

        window.applyTransportDefaultsFromCertificate(
            parseJson(opt.dataset.transportDefaults),
            parseJson(opt.dataset.lockedFields)
        );
    }

    certSelect.addEventListener('change', syncCertificate);
    document.addEventListener('DOMContentLoaded', function() {
        syncCertificate();
        window.applyTransportDefaultsFromCertificate(
            @json($certificateDefaults),
            @json($certificateLocked)
        );
    });
})();
</script>
