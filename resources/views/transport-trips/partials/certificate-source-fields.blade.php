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

<div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-4">
    <p class="text-sm font-medium text-slate-800">{{ __('Certified product') }}</p>
    <p class="text-xs text-slate-600">{{ __('Select the meat inspection certificate for this shipment. Batch, facility, and transporter details come from the certificate when recorded there.') }}</p>

    <div>
        <x-input-label for="certificate_id" :value="__('Certificate')" />
        <select id="certificate_id" name="certificate_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
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
    </div>

    <input type="hidden" id="trip_batch_id" name="batch_id" value="{{ old('batch_id', $trip?->batch_id ?? $selected['batch_id'] ?? '') }}" />

    <div id="certificate-derived-fields" @class(['space-y-3', 'hidden' => ! $selected])>
        <div>
            <x-input-label :value="__('Batch')" />
            <p id="linked_batch_display" class="mt-1 text-sm text-gray-900 rounded-md border border-gray-200 bg-white px-3 py-2">{{ $selected['batch_label'] ?? '—' }}</p>
        </div>
        <div>
            <x-input-label :value="__('Slaughter facility')" />
            <p id="linked_facility_display" class="mt-1 text-sm text-gray-900 rounded-md border border-gray-200 bg-white px-3 py-2">{{ $selected['facility_label'] ?? '—' }}</p>
        </div>
    </div>
</div>

<script>
(function() {
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

        if (typeof window.applyTransportDefaultsFromCertificate === 'function') {
            window.applyTransportDefaultsFromCertificate(
                parseJson(opt.dataset.transportDefaults),
                parseJson(opt.dataset.lockedFields)
            );
        }
    }

    certSelect.addEventListener('change', syncCertificate);
    syncCertificate();

    if (typeof window.applyTransportDefaultsFromCertificate === 'function') {
        window.applyTransportDefaultsFromCertificate(
            @json($certificateDefaults),
            @json($certificateLocked)
        );
    }
})();
</script>
