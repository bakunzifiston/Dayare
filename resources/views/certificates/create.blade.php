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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    {{ __('Batches appear here after meat is released from cold room storage and no certificate exists yet.') }}
                </p>

                @if ($selectedBatch ?? null)
                    @if ($selectedBatch->canIssueCertificate())
                        <div class="mb-4 rounded bg-green-50 border border-green-200 p-3">
                            <p class="text-xs font-medium text-green-700 mb-2">
                                {{ __('Ready for certification') }}
                            </p>
                            <div class="flex flex-wrap gap-6 text-sm text-green-800">
                                <span>{{ __('Batch') }}: <strong class="font-mono">{{ $selectedBatch->batch_code }}</strong></span>
                                <span>{{ __('Species') }}: <strong>{{ $selectedBatch->species }}</strong></span>
                                @if ($selectedBatch->hasPerAnimalData())
                                    <span>{{ __('Animals') }}: <strong>{{ $selectedBatch->animal_count }}</strong></span>
                                @endif
                                @if ($selectedBatch->postMortemInspection)
                                    <span>{{ __('PM approved') }}: <strong>{{ $selectedBatch->postMortemInspection->approved_quantity }}</strong></span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="mb-4 rounded bg-amber-50 border border-amber-200 p-3">
                            <p class="text-sm font-medium text-amber-800">
                                {{ __('Batch :code cannot be certified yet', ['code' => $selectedBatch->batch_code]) }}
                            </p>
                            <p class="mt-1 text-sm text-amber-700">{{ $selectedBatch->certificateIssueBlockReason() }}</p>
                            @if (! $selectedBatch->hasReleasedColdRoomStorage())
                                <a href="{{ route('cold-rooms.hub') }}" class="mt-2 inline-block text-sm font-medium text-amber-900 underline">
                                    {{ __('Go to cold room →') }}
                                </a>
                            @endif
                        </div>
                    @endif
                @endif

                @if ($batches->isEmpty())
                    <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                        <p class="font-medium text-slate-900">{{ __('No batches are ready for certification') }}</p>
                        <p class="mt-1">{{ __('Complete post-mortem inspection, store meat in the cold room, and release it before issuing a certificate.') }}</p>
                        @if (($pendingColdRoomRelease ?? collect())->isNotEmpty())
                            <div class="mt-4 border-t border-slate-200 pt-3">
                                <p class="font-medium text-slate-800">{{ __('Meat still in cold room (not released yet)') }}</p>
                                <p class="mt-1 text-slate-600">{{ __('Open each storage record, set status to Released, and save. The batch will appear here after release.') }}</p>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($pendingColdRoomRelease as $pending)
                                        <li class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="font-mono text-xs">{{ $pending['batch_code'] }}</span>
                                            @if ($pending['ear_tag'])
                                                <span class="text-slate-600">· {{ $pending['ear_tag'] }}</span>
                                            @endif
                                            <span class="text-slate-600">· {{ number_format((float) $pending['quantity'], 2) }} {{ $pending['unit'] }}</span>
                                            <a href="{{ $pending['edit_url'] }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ __('Release in cold room →') }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (($blockedBatches ?? collect())->isNotEmpty())
                            <div class="mt-4 border-t border-slate-200 pt-3">
                                <p class="font-medium text-slate-800">{{ __('Released storage found, but these batches are still blocked') }}</p>
                                <ul class="mt-2 space-y-1">
                                    @foreach ($blockedBatches as $blocked)
                                        <li>
                                            <span class="font-mono text-xs">{{ $blocked['batch_code'] }}</span>
                                            <span class="text-slate-600"> — {{ $blocked['reason'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-3">
                            <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ __('Cold room') }}</a>
                            <a href="{{ route('post-mortem-inspections.hub') }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ __('Post-mortem inspections') }}</a>
                        </div>
                    </div>
                @endif

                <form method="post" action="{{ route('certificates.store') }}" class="space-y-6" id="certificate-form">
                    @csrf

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($batches->isEmpty())>
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $b)
                                <option
                                    value="{{ $b['id'] }}"
                                    data-facility-id="{{ $b['facility_id'] }}"
                                    data-inspector-id="{{ $b['inspector_id'] }}"
                                    @selected((string) old('batch_id', $selectedBatch?->canIssueCertificate() ? $selectedBatch->id : '') === (string) $b['id'])
                                >{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($batches->isEmpty())>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option
                                        value="{{ $insp['id'] }}"
                                        data-facility-id="{{ $fid }}"
                                        @selected((string) old('inspector_id', $defaultInspectorId ?? '') === (string) $insp['id'])
                                    >{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($batches->isEmpty())>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($facilities as $f)
                                <option
                                    value="{{ $f['id'] }}"
                                    data-facility-id="{{ $f['id'] }}"
                                    @selected((string) old('facility_id', $defaultFacilityId ?? '') === (string) $f['id'])
                                >{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="slaughterhouse_display_name" :value="__('Slaughterhouse name (on certificate)')" />
                        <x-text-input
                            id="slaughterhouse_display_name"
                            name="slaughterhouse_display_name"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            :value="old('slaughterhouse_display_name', $defaultSlaughterhouseName ?? \App\Services\Processor\CertificatePdfService::NYAGATARE_FACILITY_NAME)"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Enter the official name exactly as it should appear on the printed certificate.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughterhouse_display_name')" />
                    </div>

                    @include('certificates.partials.pdf-details-form', [
                        'pdfDefaults' => $pdfDefaults ?? [],
                        'savedPdfDetails' => $savedPdfDetails ?? [],
                    ])

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
                        <x-primary-button :disabled="$batches->isEmpty()">{{ __('Issue certificate') }}</x-primary-button>
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
                var labelSelectInspector = @json(__('Select inspector'));
                var labelSelectBatchFirst = @json(__('Select batch first'));
                var labelSlaughterFacility = @json(__('Slaughter facility'));

                function setOptionVisibility(select, facilityId) {
                    if (!select) {
                        return;
                    }

                    Array.from(select.options).forEach(function (opt) {
                        if (opt.value === '') {
                            opt.disabled = false;
                            opt.hidden = false;
                            opt.textContent = facilityId ? labelSelectInspector : labelSelectBatchFirst;
                            return;
                        }

                        var matches = !facilityId || opt.dataset.facilityId === String(facilityId);
                        opt.disabled = !matches;
                        opt.hidden = !matches;
                    });
                }

                function syncFromBatch() {
                    var selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                    var facilityId = selected && selected.dataset.facilityId ? selected.dataset.facilityId : '';
                    var inspectorId = selected && selected.dataset.inspectorId ? selected.dataset.inspectorId : '';

                    setOptionVisibility(inspectorSelect, facilityId);

                    if (facilitySelect) {
                        Array.from(facilitySelect.options).forEach(function (opt) {
                            if (opt.value === '') {
                                opt.disabled = !facilityId;
                                opt.hidden = false;
                                opt.textContent = facilityId ? labelSlaughterFacility : labelSelectBatchFirst;
                                return;
                            }

                            var matches = !facilityId || opt.dataset.facilityId === String(facilityId);
                            opt.disabled = !matches;
                            opt.hidden = !matches;
                        });

                        if (facilityId) {
                            facilitySelect.value = facilityId;
                        } else {
                            facilitySelect.value = '';
                        }
                    }

                    if (inspectorSelect && inspectorId) {
                        var inspectorOption = Array.from(inspectorSelect.options).find(function (opt) {
                            return opt.value === String(inspectorId) && !opt.disabled;
                        });
                        if (inspectorOption) {
                            inspectorSelect.value = inspectorOption.value;
                        }
                    }
                }

                if (batchSelect) {
                    batchSelect.addEventListener('change', syncFromBatch);
                }

                syncFromBatch();
            });
        </script>
    @endpush
</x-app-layout>
