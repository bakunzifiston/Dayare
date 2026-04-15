<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('slaughter-plans.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter planning') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Schedule slaughter') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('slaughter-plans.store') }}" class="space-y-6" id="slaughter-plan-form">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_date" :value="__('Slaughter date')" />
                        <x-text-input id="slaughter_date" name="slaughter_date" type="date" class="mt-1 block w-full" :value="old('slaughter_date')" required min="{{ date('Y-m-d') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_date')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', request('facility_id')) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="animal_intake_id" :value="__('Animal intake (required)')" />
                        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility first') }}</option>
                            @foreach ($eligibleIntakes ?? [] as $intake)
                                <option value="{{ $intake['id'] }}" data-facility-id="{{ $intake['facility_id'] }}" @selected(old('animal_intake_id', request('animal_intake_id')) == $intake['id'])>{{ $intake['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Slaughter cannot be created without a linked animal intake. Health certificate must be valid.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select facility first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}">{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        @php
                            $speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect();
                        @endphp
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species') === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="number_of_animals_scheduled" :value="__('Number of animals scheduled')" />
                        <x-text-input id="number_of_animals_scheduled" name="number_of_animals_scheduled" type="number" min="1" class="mt-1 block w-full" :value="old('number_of_animals_scheduled')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('number_of_animals_scheduled')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\SlaughterPlan::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', 'planned') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Create plan') }}</x-primary-button>
                        <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const facilitySelect = document.getElementById('facility_id');
            const inspectorSelect = document.getElementById('inspector_id');
            const intakeSelect = document.getElementById('animal_intake_id');
            const oldFacilityId = '{{ old('facility_id', request('facility_id')) }}';
            const oldInspectorId = '{{ old('inspector_id') }}';
            function filterByFacility(select, dataAttr) {
                if (!select || !facilitySelect) return;
                const fid = facilitySelect.value;
                Array.from(select.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    const optFid = opt.getAttribute(dataAttr);
                    opt.hidden = optFid !== fid;
                });
                if (select.value && select.options[select.selectedIndex].hidden) select.value = '';
            }
            function filterInspectors() {
                const fid = facilitySelect && facilitySelect.value;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') {
                        opt.textContent = fid ? '{{ __('Select inspector') }}' : '{{ __('Select facility first') }}';
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = opt.dataset.facilityId !== fid;
                });
                inspectorSelect.value = fid && oldFacilityId === fid ? oldInspectorId : '';
            }
            function filterIntakes() {
                filterByFacility(intakeSelect, 'data-facility-id');
            }
            if (facilitySelect) {
                facilitySelect.addEventListener('change', function() {
                    filterInspectors();
                    filterIntakes();
                });
            }
            document.addEventListener('DOMContentLoaded', function() {
                filterInspectors();
                filterIntakes();
            });
        })();
    </script>
</x-app-layout>
