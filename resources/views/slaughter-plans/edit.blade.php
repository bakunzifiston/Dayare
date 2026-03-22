<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit slaughter plan') }} — {{ $plan->slaughter_date->format('d M Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('slaughter-plans.update', $plan) }}" class="space-y-6" id="slaughter-plan-edit-form">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="slaughter_date" :value="__('Slaughter date')" />
                        <x-text-input id="slaughter_date" name="slaughter_date" type="date" class="mt-1 block w-full" :value="old('slaughter_date', $plan->slaughter_date->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_date')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $plan->facility_id) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="animal_intake_id" :value="__('Animal intake')" />
                        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            <option value="">{{ __('Select facility first') }}</option>
                            @foreach ($eligibleIntakes ?? [] as $intake)
                                <option value="{{ $intake['id'] }}" data-facility-id="{{ $intake['facility_id'] }}" @selected(old('animal_intake_id', $plan->animal_intake_id) == $intake['id'])>{{ $intake['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select inspector') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id', $plan->inspector_id) == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach (\App\Models\SlaughterPlan::SPECIES_OPTIONS as $s)
                                <option value="{{ $s }}" @selected(old('species', $plan->species) === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="number_of_animals_scheduled" :value="__('Number of animals scheduled')" />
                        <x-text-input id="number_of_animals_scheduled" name="number_of_animals_scheduled" type="number" min="1" class="mt-1 block w-full" :value="old('number_of_animals_scheduled', $plan->number_of_animals_scheduled)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('number_of_animals_scheduled')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\SlaughterPlan::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $plan->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update plan') }}</x-primary-button>
                        <a href="{{ route('slaughter-plans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
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
            function filterByFacility(select, dataAttr) {
                if (!select || !facilitySelect) return;
                const fid = facilitySelect.value;
                Array.from(select.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.getAttribute(dataAttr) !== fid;
                });
                const cur = select.options[select.selectedIndex];
                if (cur && cur.hidden) {
                    const visible = Array.from(select.options).find(o => o.value && !o.hidden);
                    select.value = visible ? visible.value : '';
                }
            }
            function filterInspectors() {
                const fid = facilitySelect && facilitySelect.value;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.dataset.facilityId !== fid;
                });
                const currentOpt = inspectorSelect.options[inspectorSelect.selectedIndex];
                if (currentOpt && currentOpt.hidden) {
                    const visible = Array.from(inspectorSelect.options).find(o => o.value && !o.hidden);
                    inspectorSelect.value = visible ? visible.value : '';
                }
            }
            function filterIntakes() { filterByFacility(intakeSelect, 'data-facility-id'); }
            if (facilitySelect) {
                facilitySelect.addEventListener('change', function() { filterInspectors(); filterIntakes(); });
            }
            document.addEventListener('DOMContentLoaded', function() { filterInspectors(); filterIntakes(); });
        })();
    </script>
</x-app-layout>
