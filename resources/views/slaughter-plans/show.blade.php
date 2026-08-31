<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter planning') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $plan->slaughterDateDisplay() }}</p>
                    <p class="text-xs text-slate-500">{{ $plan->facility->facility_name ?? '—' }} · {{ ucfirst($plan->status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('slaughter-plans.edit', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('slaughter-plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this slaughter plan? This cannot be undone.') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Slaughter date & time') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $plan->slaughterDateDisplay() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($plan->status) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Facility') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('businesses.facilities.show', [$plan->facility->business, $plan->facility]) }}" class="text-bucha-primary hover:underline">
                            {{ $plan->facility->facility_name }} ({{ $plan->facility->facility_type }})
                        </a>
                    </dd>
                </div>
                @if ($plan->animalIntake)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Animal intake') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('animal-intakes.show', $plan->animalIntake) }}" class="text-bucha-primary hover:underline">
                                {{ $plan->animalIntake->intakeDatetimeLabel() }} — {{ $plan->animalIntake->supplier_firstname }} {{ $plan->animalIntake->supplier_lastname }}
                            </a>
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('inspectors.show', $plan->inspector) }}" class="text-bucha-primary hover:underline">{{ $plan->inspector->full_name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Species') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $plan->species }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Number of animals scheduled') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $plan->number_of_animals_scheduled }}</dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Ante-mortem inspections') }}</p>
                @if ($plan->anteMortemInspections->isEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_RECORD_ANTE_MORTEM))
                    <a href="{{ route('ante-mortem-inspections.create', ['slaughter_plan_id' => $plan->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Record inspection') }}</a>
                @endif
            </div>
            @if ($plan->anteMortemInspections->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($plan->anteMortemInspections as $am)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <a href="{{ route('ante-mortem-inspections.show', $am) }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ $am->inspection_date->format('d M Y') }} — {{ $am->species }}</a>
                            <span class="text-xs text-slate-500">{{ $am->number_examined }} {{ __('examined') }} · {{ $am->number_approved }} {{ __('approved') }} · {{ $am->number_rejected }} {{ __('rejected') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No ante-mortem inspections recorded for this plan yet.') }}</p>
            @endif
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Slaughter executions') }}</p>
                @if ($plan->slaughterExecutions->isEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER))
                    <a href="{{ route('slaughter-executions.create', ['slaughter_plan_id' => $plan->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Record execution') }}</a>
                @elseif ($plan->slaughterExecutions->isNotEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER))
                    <a href="{{ route('slaughter-executions.edit', $plan->slaughterExecutions->sortByDesc('id')->first()) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Continue execution') }}</a>
                @endif
            </div>
            @if ($plan->slaughterExecutions->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($plan->slaughterExecutions as $ex)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <a href="{{ route('slaughter-executions.show', $ex) }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ $ex->slaughter_time->format('d M Y H:i') }}</a>
                            <span class="text-xs text-slate-500">{{ $ex->actual_animals_slaughtered }} {{ __('animals') }} · {{ ucfirst(str_replace('_', ' ', $ex->status)) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No slaughter executions recorded for this plan yet.') }}</p>
            @endif
        </section>
    </div>
</x-app-layout>
