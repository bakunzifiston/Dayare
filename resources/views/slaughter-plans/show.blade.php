<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('slaughter-plans.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter planning') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Slaughter plan') }} — {{ $plan->slaughter_date->format('d M Y') }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('slaughter-plans.edit', $plan) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('slaughter-plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this slaughter plan? This cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Delete') }}
                    </button>
                </form>
                <a href="{{ route('slaughter-plans.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All plans') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $plan->slaughter_date->format('l, d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($plan->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Facility') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('businesses.facilities.show', [$plan->facility->business, $plan->facility]) }}" class="text-bucha-primary hover:underline">
                                {{ $plan->facility->facility_name }} ({{ $plan->facility->facility_type }})
                            </a>
                        </dd>
                    </div>
                    @if ($plan->animalIntake)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Animal intake') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('animal-intakes.show', $plan->animalIntake) }}" class="text-bucha-primary hover:underline">
                                    {{ $plan->animalIntake->intakeDatetimeLabel() }} — {{ $plan->animalIntake->supplier_firstname }} {{ $plan->animalIntake->supplier_lastname }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $plan->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $plan->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $plan->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number of animals scheduled') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $plan->number_of_animals_scheduled }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Ante-mortem inspections') }}</h3>
                    @if ($plan->anteMortemInspections->isEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_RECORD_ANTE_MORTEM))
                        <a href="{{ route('ante-mortem-inspections.create', ['slaughter_plan_id' => $plan->id]) }}"
                           class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                            <i class="ti ti-stethoscope text-base" aria-hidden="true"></i>
                            {{ __('Record ante-mortem inspection') }}
                        </a>
                    @endif
                </div>
                @if ($plan->anteMortemInspections->isNotEmpty())
                    <ul class="divide-y divide-gray-200">
                        @foreach ($plan->anteMortemInspections as $am)
                            <li class="py-2">
                                <a href="{{ route('ante-mortem-inspections.show', $am) }}" class="font-medium text-bucha-primary hover:underline">{{ $am->inspection_date->format('d M Y') }} — {{ $am->species }}</a>
                                <span class="text-sm text-gray-500"> {{ $am->number_examined }} {{ __('examined') }}, {{ $am->number_approved }} {{ __('approved') }}, {{ $am->number_rejected }} {{ __('rejected') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">{{ __('No ante-mortem inspections recorded for this plan yet.') }}</p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Slaughter executions') }}</h3>
                    @if ($plan->slaughterExecutions->isEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER))
                        <a href="{{ route('slaughter-executions.create', ['slaughter_plan_id' => $plan->id]) }}"
                           class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                            <i class="ti ti-bolt text-base" aria-hidden="true"></i>
                            {{ __('Record slaughter execution') }}
                        </a>
                    @endif
                </div>
                @if ($plan->slaughterExecutions->isNotEmpty())
                    <ul class="divide-y divide-gray-200">
                        @foreach ($plan->slaughterExecutions as $ex)
                            <li class="py-2">
                                <a href="{{ route('slaughter-executions.show', $ex) }}" class="font-medium text-bucha-primary hover:underline">{{ $ex->slaughter_time->format('d M Y H:i') }}</a>
                                <span class="text-sm text-gray-500"> {{ $ex->actual_animals_slaughtered }} {{ __('animals') }} · {{ ucfirst(str_replace('_', ' ', $ex->status)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">{{ __('No slaughter executions recorded for this plan yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
