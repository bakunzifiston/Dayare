<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('slaughter-plans.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter planning') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit slaughter plan') }} — {{ $plan->slaughter_date->format('d M Y') }}
                </h2>
            </div>
            <a href="{{ route('slaughter-plans.show', $plan) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">{{ __('Back to plan') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($plan->hasAssignmentGap())
                    <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <svg class="inline-block h-4 w-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        {{ __('This plan has no animals assigned — it may predate individual animal tracking. Save the plan to trigger assignment.') }}
                    </div>
                @elseif (! $plan->isFullyAssigned())
                    <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <svg class="inline-block h-4 w-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        {{ __('Only :assigned of :scheduled animals are currently assigned. Save to rebalance.', ['assigned' => $plan->assigned_count, 'scheduled' => $plan->number_of_animals_scheduled]) }}
                    </div>
                @endif

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
                        @php($speciesOptions = auth()->user()?->configuredSpeciesNames() ?? collect())
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($speciesOptions as $s)
                                <option value="{{ $s }}" @selected(old('species', $plan->species) === $s)>{{ __($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="number_of_animals_scheduled" :value="__('Number of animals scheduled')" />
                        <x-text-input id="number_of_animals_scheduled" name="number_of_animals_scheduled" type="number" min="1" class="mt-1 block w-full" :value="old('number_of_animals_scheduled', $plan->number_of_animals_scheduled)" required />
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
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\SlaughterPlan::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $plan->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update plan') }}</x-primary-button>
                        <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('slaughter-plans.partials.assignment-form-scripts', ['createForm' => false])
</x-app-layout>
