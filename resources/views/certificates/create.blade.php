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
                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-medium">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('certificates.store') }}" class="space-y-6" id="certificate-form">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_execution_id" :value="__('Slaughter execution')" />
                        <select id="slaughter_execution_id" name="slaughter_execution_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($executions->isEmpty())>
                            <option value="">{{ __('Select slaughter execution') }}</option>
                            @foreach ($executions as $execution)
                                <option
                                    value="{{ $execution['id'] }}"
                                    data-facility-id="{{ $execution['facility_id'] }}"
                                    data-inspector-id="{{ $execution['inspector_id'] }}"
                                    @selected((string) old('slaughter_execution_id', $selectedExecutionId ?? '') === (string) $execution['id'])
                                >{{ $execution['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />
                        @if ($executions->isEmpty())
                            <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                {{ __('No slaughter executions are ready for certification yet. Complete post-mortem inspection, store the meat in cold room, then release it from cold room before issuing a certificate.') }}
                            </p>
                            @if (! empty($certificateBlockReasons) && $certificateBlockReasons->isNotEmpty())
                                <ul class="mt-2 list-disc list-inside text-sm text-amber-900 space-y-1">
                                    @foreach ($certificateBlockReasons as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    </div>

                    <div id="animal-selection-panel" class="hidden rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Animal for this certificate') }}</h3>
                            <p class="mt-1 text-xs text-slate-600">{{ __('Select exactly one animal. Each certificate is issued per animal, after post-mortem approval and cold-room release.') }}</p>
                        </div>
                        <div id="animal-selection-list" class="space-y-2"></div>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_item_ids')" />
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_item_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($executions->isEmpty())>
                            <option value="">{{ __('Select slaughter execution first') }}</option>
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
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required @disabled($executions->isEmpty())>
                            <option value="">{{ __('Select slaughter execution first') }}</option>
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
                        <x-primary-button :disabled="$executions->isEmpty()">{{ __('Issue certificate') }}</x-primary-button>
                        <a href="{{ route('certificates.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
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
                var executionSelect = document.getElementById('slaughter_execution_id');
                var inspectorSelect = document.getElementById('inspector_id');
                var facilitySelect = document.getElementById('facility_id');
                var executionPrefills = @json($executionPrefills ?? []);
                var animalsByExecution = @json($certifiableAnimalsByExecution ?? []);
                var selectedAnimalIds = @json($selectedAnimalIds ?? []);
                var pdfDetailKeys = @json(\App\Support\CertificatePdfDetails::KEYS);
                var labelSelectInspector = @json(__('Select inspector'));
                var labelSelectExecutionFirst = @json(__('Select slaughter execution first'));
                var labelSlaughterFacility = @json(__('Slaughter facility'));
                var animalPanel = document.getElementById('animal-selection-panel');
                var animalList = document.getElementById('animal-selection-list');

                function selectedAnimalRadios() {
                    return animalList ? Array.from(animalList.querySelectorAll('input[name="animal_intake_item_id"]')) : [];
                }

                function selectedAnimalIdsFromDom() {
                    return selectedAnimalRadios()
                        .filter(function (input) { return input.checked; })
                        .map(function (input) { return parseInt(input.value, 10); });
                }

                function renderAnimalSelection(executionId) {
                    if (!animalList || !animalPanel) {
                        return;
                    }

                    var animals = animalsByExecution[executionId] || [];
                    animalList.innerHTML = '';

                    if (!executionId || animals.length === 0) {
                        animalPanel.classList.add('hidden');
                        return;
                    }

                    animalPanel.classList.remove('hidden');

                    animals.forEach(function (animal, index) {
                        var checked = selectedAnimalIds.length
                            ? selectedAnimalIds.indexOf(animal.animal_intake_item_id) !== -1
                            : animals.length === 1 && index === 0;
                        var row = document.createElement('label');
                        row.className = 'flex flex-wrap items-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm cursor-pointer';
                        row.innerHTML =
                            '<input type="radio" name="animal_intake_item_id" value="' + animal.animal_intake_item_id + '" class="border-slate-300 text-bucha-primary focus:ring-bucha-primary"' + (checked ? ' checked' : '') + ' required>'
                            + '<span class="font-mono text-xs font-semibold text-slate-900">' + animal.ear_tag + '</span>'
                            + '<span class="text-slate-600">' + animal.species + (animal.sex ? ' · ' + animal.sex : '') + '</span>'
                            + '<span class="ml-auto tabular-nums text-slate-700">' + Number(animal.released_kg).toFixed(2) + ' kg</span>';
                        animalList.appendChild(row);
                    });

                    selectedAnimalRadios().forEach(function (input) {
                        input.addEventListener('change', function () {
                            applySelectionPrefill(executionSelect ? executionSelect.value : '');
                        });
                    });
                }

                function applySelectionPrefill(executionId) {
                    var prefill = executionPrefills[executionId] || {};
                    var animals = animalsByExecution[executionId] || [];
                    var selectedIds = selectedAnimalIdsFromDom();
                    var selected = animals.filter(function (animal) {
                        return selectedIds.indexOf(animal.animal_intake_item_id) !== -1;
                    });

                    pdfDetailKeys.forEach(function (key) {
                        var input = document.querySelector('[name="pdf_details[' + key + ']"]');
                        if (!input) {
                            return;
                        }

                        if (key === 'animal_names') {
                            input.value = selected.map(function (animal) { return animal.ear_tag; }).join(', ');
                            return;
                        }

                        if (key === 'carcass_meat_kg') {
                            var total = selected.reduce(function (sum, animal) {
                                return sum + Number(animal.released_kg || 0);
                            }, 0);
                            input.value = total > 0 ? total.toFixed(2) : '';
                            return;
                        }

                        if (Object.prototype.hasOwnProperty.call(prefill, key)) {
                            input.value = prefill[key] ?? '';
                        } else {
                            input.value = '';
                        }
                    });
                }

                function applyPdfPrefill(executionId) {
                    applySelectionPrefill(executionId);
                }

                function setOptionVisibility(select, facilityId) {
                    if (!select) {
                        return;
                    }

                    Array.from(select.options).forEach(function (opt) {
                        if (opt.value === '') {
                            opt.disabled = false;
                            opt.hidden = false;
                            opt.textContent = facilityId ? labelSelectInspector : labelSelectExecutionFirst;
                            return;
                        }

                        var matches = !facilityId || opt.dataset.facilityId === String(facilityId);
                        opt.disabled = !matches;
                        opt.hidden = !matches;
                    });
                }

                function syncFromExecution() {
                    var selected = executionSelect && executionSelect.options[executionSelect.selectedIndex];
                    var facilityId = selected && selected.dataset.facilityId ? selected.dataset.facilityId : '';
                    var inspectorId = selected && selected.dataset.inspectorId ? selected.dataset.inspectorId : '';

                    setOptionVisibility(inspectorSelect, facilityId);

                    if (facilitySelect) {
                        Array.from(facilitySelect.options).forEach(function (opt) {
                            if (opt.value === '') {
                                opt.disabled = !facilityId;
                                opt.hidden = false;
                                opt.textContent = facilityId ? labelSlaughterFacility : labelSelectExecutionFirst;
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

                    if (executionSelect && executionSelect.value) {
                        renderAnimalSelection(executionSelect.value);
                        applyPdfPrefill(executionSelect.value);
                    } else {
                        renderAnimalSelection('');
                        applyPdfPrefill('');
                    }
                }

                if (executionSelect) {
                    executionSelect.addEventListener('change', syncFromExecution);
                }

                syncFromExecution();
            });
        </script>
    @endpush
</x-app-layout>
