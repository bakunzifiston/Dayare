<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter planning') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Edit plan') }}</p>
                    <p class="text-xs text-slate-500">{{ $plan->slaughterDateDisplay() }}</p>
                </div>
                <a href="{{ route('slaughter-plans.show', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                @if ($plan->hasAssignmentGap())
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('This plan has no animals assigned — it may predate individual animal tracking. Save the plan to trigger assignment.') }}
                    </div>
                @elseif (! $plan->isFullyAssigned())
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('Only :assigned of :scheduled animals are currently assigned. Save to rebalance.', ['assigned' => $plan->assigned_count, 'scheduled' => $plan->number_of_animals_scheduled]) }}
                    </div>
                @endif

                <form method="post" action="{{ route('slaughter-plans.update', $plan) }}" class="space-y-5" id="slaughter-plan-edit-form">
                    @csrf
                    @method('put')

                    @php
                        $slaughterDateValue = old(
                            'slaughter_date',
                            $plan->slaughter_date?->timezone(config('app.display_timezone', 'Africa/Kigali'))->format('Y-m-d\TH:i'),
                        );
                    @endphp
                    <div>
                        <x-input-label for="slaughter_date" :value="__('Slaughter date & time')" />
                        <x-text-input id="slaughter_date" name="slaughter_date" type="datetime-local" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="$slaughterDateValue" required />
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_date')" />
                    </div>

                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $plan->facility_id) == $f->id)>{{ $f->facility_name }} ({{ $f->facility_type }})</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                    </div>

                    <div>
                        <x-input-label for="animal_intake_id" :value="__('Animal intake')" />
                        <select id="animal_intake_id" name="animal_intake_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
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
                                    @selected(old('animal_intake_id', $plan->animal_intake_id) == $intake['id'])
                                >{{ $intake['label'] ?? ($intake['reference'] ?? 'Intake #'.$intake['id']) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_intake_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
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
                        @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                        <select id="species" name="species" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', $plan->species) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="number_of_animals_scheduled" :value="__('Number of animals scheduled')" />
                        <x-text-input id="number_of_animals_scheduled" name="number_of_animals_scheduled" type="number" min="1" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('number_of_animals_scheduled', $plan->number_of_animals_scheduled)" required />
                        <div id="animal-preview-panel" class="mt-4" style="display: none;"></div>

                        @if (isset($assignedAnimals) && $assignedAnimals->isNotEmpty())
                            <div id="assigned-animals-table" class="mt-4">
                                <p class="text-sm text-slate-500 mb-2">
                                    {{ __('Currently assigned: :count of :scheduled', ['count' => $assignedAnimals->count(), 'scheduled' => $plan->number_of_animals_scheduled]) }}
                                </p>
                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">{{ __('Ear tag') }}</th>
                                                <th class="px-3 py-2">{{ __('Species') }}</th>
                                                <th class="px-3 py-2">{{ __('Sex') }}</th>
                                                <th class="px-3 py-2">{{ __('Age') }}</th>
                                                <th class="px-3 py-2">{{ __('Weight') }}</th>
                                                <th class="px-3 py-2">{{ __('Health status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($assignedAnimals as $item)
                                                <tr>
                                                    <td class="px-3 py-2 font-mono text-xs">
                                                        {{ $item->ear_tag }}
                                                        @if (str_starts_with($item->ear_tag, 'LEGACY-'))
                                                            <span class="ml-1 inline-flex items-center rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">[legacy]</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">{{ $item->species }}</td>
                                                    <td class="px-3 py-2">{{ ucfirst($item->sex) }}</td>
                                                    <td class="px-3 py-2">{{ $item->age_months ? $item->age_months.' '.__('months') : '—' }}</td>
                                                    <td class="px-3 py-2">{{ $item->live_weight_kg ? $item->live_weight_kg.' kg' : '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                            @if ($item->health_status === 'healthy') bg-green-100 text-green-800
                                                            @elseif ($item->health_status === 'under_observation') bg-amber-100 text-amber-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            {{ $item->health_status_label }}
                                                        </span>
                                                        @if ($item->health_status === 'under_observation')
                                                            <small class="text-amber-700 ml-1">{{ __('Under observation — will be reviewed at ante-mortem') }}</small>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <x-input-error class="mt-2" :messages="$errors->get('number_of_animals_scheduled')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
                            @foreach (\App\Models\SlaughterPlan::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $plan->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Update plan') }}</button>
                        <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    @include('slaughter-plans.partials.assignment-form-scripts', ['createForm' => false])
</x-app-layout>
