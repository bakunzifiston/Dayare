<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Slaughter plan') }} — {{ $plan->slaughter_date->format('d M Y') }}
            </h2>
            <div class="flex gap-2">
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
                    {{ __('Back to plans') }}
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
                                    {{ $plan->animalIntake->intake_date->format('d M Y') }} — {{ $plan->animalIntake->supplier_firstname }} {{ $plan->animalIntake->supplier_lastname }}
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

            @if ($plan->anteMortemInspections->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Ante-mortem inspections') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($plan->anteMortemInspections as $am)
                            <li class="py-2">
                                <a href="{{ route('ante-mortem-inspections.show', $am) }}" class="font-medium text-bucha-primary hover:underline">{{ $am->inspection_date->format('d M Y') }} — {{ $am->species }}</a>
                                <span class="text-sm text-gray-500"> {{ $am->number_examined }} examined, {{ $am->number_approved }} approved, {{ $am->number_rejected }} rejected</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($plan->slaughterExecutions->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Slaughter executions') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($plan->slaughterExecutions as $ex)
                            <li class="py-2">
                                <a href="{{ route('slaughter-executions.show', $ex) }}" class="font-medium text-bucha-primary hover:underline">{{ $ex->slaughter_time->format('d M Y H:i') }}</a>
                                <span class="text-sm text-gray-500"> {{ $ex->actual_animals_slaughtered }} animals · {{ ucfirst(str_replace('_', ' ', $ex->status)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
