@php
    $slaughterLocalNow = now(config('app.display_timezone', 'Africa/Kigali'))->format('Y-m-d\TH:i');
@endphp
<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter planning') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Schedule slaughter') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Assign animals from an intake to a slaughter session.') }}</p>
                </div>
                <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                <form method="post" action="{{ route('slaughter-plans.store') }}" class="space-y-5" id="slaughter-plan-form">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_date" :value="__('Slaughter date & time')" />
                        <x-text-input id="slaughter_date" name="slaughter_date" type="datetime-local" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm"
                            :value="old('slaughter_date', $slaughterLocalNow)" required min="{{ $slaughterLocalNow }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_date')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="">{{ __('Select facility') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', request('facility_id')) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="animal_intake_id" :value="__('Animal intake (required)')" />
                        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="">{{ __('Select facility first') }}</option>
                            @foreach ($eligibleIntakes ?? [] as $intake)
                                @php
                                    $animalsForIntake = collect($intakeAnimals[$intake['id']] ?? []);
                                    $speciesMixCounts = $animalsForIntake->countBy('species')->all();
                                @endphp
                                <option
                                    value="{{ $intake['id'] }}"
                                    data-facility-id="{{ $intake['facility_id'] }}"
                                    data-species-mix="{{ json_encode($speciesMixCounts) }}"
                                    data-animals="{{ json_encode($animalsForIntake->values()) }}"
                                    @selected(old('animal_intake_id', request('animal_intake_id')) == $intake['id'])
                                >{{ $intake['label'] ?? ($intake['reference'] ?? 'Intake #'.$intake['id']) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Select a facility first — only submitted intakes with available animals for that facility are listed.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
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
                        <select id="species" name="species" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species') === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="number_of_animals_scheduled" :value="__('Number of animals scheduled')" />
                        <x-text-input id="number_of_animals_scheduled" name="number_of_animals_scheduled" type="number" min="1" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('number_of_animals_scheduled')" required />
                        <div id="animal-preview-panel" class="mt-4" style="display: none;"></div>
                        <x-input-error class="mt-2" :messages="$errors->get('number_of_animals_scheduled')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
                            @foreach (\App\Models\SlaughterPlan::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', \App\Models\SlaughterPlan::STATUS_APPROVED) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create plan') }}</button>
                        <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    @include('slaughter-plans.partials.assignment-form-scripts', ['createForm' => true])
</x-app-layout>
