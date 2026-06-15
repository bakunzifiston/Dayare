<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Issue certificate') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('Only batches with post-mortem approved quantity greater than zero and no existing certificate are listed.') }}</p>

                @if ($selectedBatch ?? null)
                    <div class="mb-4 rounded bg-green-50 border border-green-200 p-3">
                        <p class="text-xs font-medium text-green-700 mb-2">
                            {{ __('Post-mortem approved — ready for certification') }}
                        </p>
                        <div class="flex flex-wrap gap-6 text-sm text-green-800">
                            <span>{{ __('Batch') }}: <strong class="font-mono">{{ $selectedBatch->batch_code }}</strong></span>
                            <span>{{ __('Species') }}: <strong>{{ $selectedBatch->species }}</strong></span>
                            @if ($selectedBatch->hasPerAnimalData())
                                <span>{{ __('Animals') }}: <strong>{{ $selectedBatch->animal_count }}</strong></span>
                            @endif
                            @if ($selectedBatch->postMortemInspection)
                                <span>{{ __('PM approved') }}: <strong>{{ $selectedBatch->postMortemInspection->approved_quantity }}</strong></span>
                                @if ($selectedBatch->postMortemInspection->condemned_quantity > 0)
                                    <span class="text-red-600">
                                        {{ __('Condemned') }}: <strong>{{ $selectedBatch->postMortemInspection->condemned_quantity }}</strong>
                                    </span>
                                @endif
                            @endif
                        </div>
                        @if ($selectedBatch->hasPerAnimalData() && $selectedBatch->isPostMortemComplete())
                            <p class="text-xs text-green-600 mt-2">
                                {{ __('✓ All :count animals have individual PM outcomes recorded', ['count' => $selectedBatch->animal_count]) }}
                            </p>
                        @elseif ($selectedBatch->hasPerAnimalData())
                            <p class="text-xs text-amber-600 mt-2">
                                {{ __('⚠ :done of :total animals have PM outcomes — not all animals recorded', [
                                    'done' => $selectedBatch->post_mortem_done_count,
                                    'total' => $selectedBatch->animal_count,
                                ]) }}
                            </p>
                        @endif
                    </div>
                @endif

                <form method="post" action="{{ route('certificates.store') }}" class="space-y-6" id="certificate-form">
                    @csrf

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" @selected((string) old('batch_id', $selectedBatch?->id ?? '') === (string) $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}">{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f['id'] }}" data-facility-id="{{ $f['id'] }}" @selected(old('facility_id') == $f['id'])>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="certificate_number" :value="__('Certificate number')" />
                        <x-text-input id="certificate_number" name="certificate_number" type="text" class="mt-1 block w-full" :value="old('certificate_number')" />
                        <x-input-error class="mt-2" :messages="$errors->get('certificate_number')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="issued_at" :value="__('Issue date')" />
                            <x-text-input id="issued_at" name="issued_at" type="date" class="mt-1 block w-full" :value="old('issued_at', date('Y-m-d'))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('issued_at')" />
                        </div>
                        <div>
                            <x-input-label for="expiry_date" :value="__('Expiry date (if applicable)')" />
                            <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('expiry_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\Certificate::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Issue certificate') }}</x-primary-button>
                        <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var batchSelect = document.getElementById('batch_id');
                var inspectorSelect = document.getElementById('inspector_id');
                var facilitySelect = document.getElementById('facility_id');
                var oldBatchId = '{{ old('batch_id', $selectedBatch?->id ?? '') }}';
                var oldInspectorId = '{{ old('inspector_id') }}';
                var oldFacilityId = '{{ old('facility_id') }}';

                function filterByFacility() {
                    var selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                    var facilityId = selected && selected.dataset.facilityId;

                    if (inspectorSelect) {
                        Array.from(inspectorSelect.options).forEach(function (opt) {
                            if (opt.value === '') {
                                opt.textContent = facilityId ? '{{ __('Select inspector') }}' : '{{ __('Select batch first') }}';
                                opt.hidden = false;
                                return;
                            }
                            opt.hidden = opt.dataset.facilityId !== facilityId;
                        });
                        inspectorSelect.value = facilityId && oldBatchId === batchSelect.value ? oldInspectorId : '';
                    }

                    if (facilitySelect) {
                        Array.from(facilitySelect.options).forEach(function (opt) {
                            if (opt.value === '') {
                                opt.textContent = facilityId ? '{{ __('Select facility') }}' : '{{ __('Select batch first') }}';
                                opt.hidden = !facilityId || opt.dataset.facilityId !== facilityId;
                                return;
                            }
                            opt.hidden = opt.dataset.facilityId !== facilityId;
                        });
                        facilitySelect.value = facilityId && oldBatchId === batchSelect.value ? oldFacilityId : (facilityId || '');
                        if (facilityId && !facilitySelect.value) {
                            var firstFac = Array.from(facilitySelect.options).find(function (o) {
                                return o.value && o.dataset.facilityId === facilityId;
                            });
                            if (firstFac) facilitySelect.value = firstFac.value;
                        }
                    }
                }

                if (batchSelect) batchSelect.addEventListener('change', filterByFacility);
                filterByFacility();
            });
        </script>
    @endpush
</x-app-layout>
