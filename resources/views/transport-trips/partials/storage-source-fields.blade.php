@props([
    'trip' => null,
    'releasedStorages' => [],
    'certificates' => [],
    'batches' => [],
    'hasReleasedStorages' => false,
])

@php
    $storageValue = old('warehouse_storage_id', $trip?->warehouse_storage_id);
    $manualMode = (string) $storageValue === 'manual';
    $linkedMode = $storageValue !== null && $storageValue !== '' && ! $manualMode;
@endphp

<input type="hidden" id="require_released_storage" name="require_released_storage" value="{{ ($hasReleasedStorages && ! $manualMode) ? '1' : '0' }}" />

<div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-4">
    <p class="text-sm font-medium text-slate-800">{{ __('Product source') }}</p>

    <div>
        <x-input-label for="warehouse_storage_id" :value="__('Cold room storage (released)')" />
        <select id="warehouse_storage_id" name="warehouse_storage_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
            @if ($hasReleasedStorages)
                <option value="" @selected(! $linkedMode && ! $manualMode)>{{ __('Select released cold room stock first') }}</option>
                @foreach ($releasedStorages as $ws)
                    <option value="{{ $ws['id'] }}"
                        data-certificate-id="{{ $ws['certificate_id'] }}"
                        data-certificate-label="{{ e($ws['certificate_label']) }}"
                        data-batch-id="{{ $ws['batch_id'] ?? '' }}"
                        data-batch-label="{{ e($ws['batch_label']) }}"
                        data-warehouse-facility-id="{{ $ws['warehouse_facility_id'] ?? '' }}"
                        @selected((string) $storageValue === (string) $ws['id'])>{{ $ws['label'] }}</option>
                @endforeach
                <option value="manual" @selected($manualMode)>{{ __('Not from cold room — choose certificate manually') }}</option>
            @else
                <option value="manual" selected>{{ __('No released cold room stock — choose certificate below') }}</option>
            @endif
        </select>
        <p class="mt-1 text-xs text-gray-500">{{ __('Certificate and batch are filled from the selected release.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('warehouse_storage_id')" />
    </div>

    <input type="hidden" id="trip_certificate_id" name="certificate_id" value="{{ old('certificate_id', $trip?->certificate_id) }}" />
    <input type="hidden" id="trip_batch_id" name="batch_id" value="{{ old('batch_id', $trip?->batch_id) }}" />

    <div id="linked-certificate-batch" @class(['space-y-3', 'hidden' => ! $linkedMode])>
        <div>
            <x-input-label :value="__('Certificate')" />
            <p id="linked_certificate_display" class="mt-1 text-sm text-gray-900 rounded-md border border-gray-200 bg-white px-3 py-2">—</p>
        </div>
        <div>
            <x-input-label :value="__('Batch')" />
            <p id="linked_batch_display" class="mt-1 text-sm text-gray-900 rounded-md border border-gray-200 bg-white px-3 py-2">—</p>
        </div>
    </div>

    <div id="manual-certificate-batch" @class(['space-y-4', 'hidden' => $linkedMode])>
        <div>
            <x-input-label for="certificate_id_manual" :value="__('Certificate')" />
            <select id="certificate_id_manual" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                <option value="">{{ __('Select certificate') }}</option>
                @foreach ($certificates as $c)
                    <option value="{{ $c['id'] }}" @selected(old('certificate_id', $trip?->certificate_id) == $c['id'])>{{ $c['label'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('certificate_id')" />
        </div>
        <div>
            <x-input-label for="batch_id_manual" :value="__('Batch (optional)')" />
            <select id="batch_id_manual" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                <option value="">{{ __('None') }}</option>
                @foreach ($batches as $b)
                    <option value="{{ $b['id'] }}" @selected(old('batch_id', $trip?->batch_id) == $b['id'])>{{ $b['label'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
        </div>
    </div>
</div>

<script>
(function() {
    var storageSelect = document.getElementById('warehouse_storage_id');
    var requireReleased = document.getElementById('require_released_storage');
    var linkedBlock = document.getElementById('linked-certificate-batch');
    var manualBlock = document.getElementById('manual-certificate-batch');
    var certInput = document.getElementById('trip_certificate_id');
    var batchInput = document.getElementById('trip_batch_id');
    var linkedCertDisplay = document.getElementById('linked_certificate_display');
    var linkedBatchDisplay = document.getElementById('linked_batch_display');
    var manualCertSelect = document.getElementById('certificate_id_manual');
    var manualBatchSelect = document.getElementById('batch_id_manual');
    var originSelect = document.getElementById('origin_facility_id');
    var hasReleased = @json($hasReleasedStorages);

    function syncMode() {
        var value = storageSelect.value;
        var manual = value === 'manual' || !hasReleased;
        var linked = value && value !== 'manual';

        linkedBlock.classList.toggle('hidden', !linked);
        manualBlock.classList.toggle('hidden', linked);
        requireReleased.value = (hasReleased && !manual) ? '1' : '0';
        storageSelect.required = hasReleased && !manual;

        if (linked) {
            var opt = storageSelect.options[storageSelect.selectedIndex];
            certInput.value = opt.dataset.certificateId || '';
            batchInput.value = opt.dataset.batchId || '';
            linkedCertDisplay.textContent = opt.dataset.certificateLabel || '—';
            linkedBatchDisplay.textContent = opt.dataset.batchLabel || '—';
            if (originSelect && opt.dataset.warehouseFacilityId) {
                originSelect.value = opt.dataset.warehouseFacilityId;
            }
        } else if (manual) {
            certInput.value = manualCertSelect.value || '';
            batchInput.value = manualBatchSelect.value || '';
        }
    }

    storageSelect.addEventListener('change', syncMode);
    manualCertSelect.addEventListener('change', syncMode);
    manualBatchSelect.addEventListener('change', syncMode);
    syncMode();
})();
</script>
