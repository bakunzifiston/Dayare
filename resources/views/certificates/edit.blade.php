<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit certificate') }} — {{ $certificate->certificate_number ?: '#' . $certificate->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('certificates.update', $certificate) }}" class="space-y-6" id="certificate-edit-form">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" @selected(old('batch_id', $certificate->batch_id) == $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select inspector') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id', $certificate->inspector_id) == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" data-facility-id="{{ $f['id'] }}" @selected(old('facility_id', $certificate->facility_id) == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="certificate_number" :value="__('Certificate number')" />
                        <x-text-input id="certificate_number" name="certificate_number" type="text" class="mt-1 block w-full" :value="old('certificate_number', $certificate->certificate_number)" />
                        <x-input-error class="mt-2" :messages="$errors->get('certificate_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="issued_at" :value="__('Issue date')" />
                            <x-text-input id="issued_at" name="issued_at" type="date" class="mt-1 block w-full" :value="old('issued_at', $certificate->issued_at?->format('Y-m-d'))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('issued_at')" />
                        </div>
                        <div>
                            <x-input-label for="expiry_date" :value="__('Expiry date (if applicable)')" />
                            <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date', $certificate->expiry_date?->format('Y-m-d'))" />
                            <x-input-error class="mt-2" :messages="$errors->get('expiry_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\Certificate::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $certificate->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update certificate') }}</x-primary-button>
                        <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const batchSelect = document.getElementById('batch_id');
            const inspectorSelect = document.getElementById('inspector_id');
            const facilitySelect = document.getElementById('facility_id');
            function filterByFacility() {
                const selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                if (inspectorSelect) {
                    Array.from(inspectorSelect.options).forEach(opt => {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = opt.dataset.facilityId !== facilityId;
                    });
                    const cur = inspectorSelect.options[inspectorSelect.selectedIndex];
                    if (cur && cur.hidden) {
                        const v = Array.from(inspectorSelect.options).find(o => o.value && !o.hidden);
                        inspectorSelect.value = v ? v.value : '';
                    }
                }
                if (facilitySelect) {
                    Array.from(facilitySelect.options).forEach(opt => {
                        opt.hidden = opt.value !== '' && opt.dataset.facilityId !== facilityId;
                    });
                    const curF = facilitySelect.options[facilitySelect.selectedIndex];
                    if (curF && curF.hidden) {
                        const v = Array.from(facilitySelect.options).find(o => o.value && !o.hidden);
                        facilitySelect.value = v ? v.value : '';
                    }
                }
            }
            if (batchSelect) batchSelect.addEventListener('change', filterByFacility);
            document.addEventListener('DOMContentLoaded', filterByFacility);
        })();
    </script>
</x-app-layout>
