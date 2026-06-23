@php
    use App\Models\BusinessUser;
    use App\Models\SlaughterExecution;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Slaughter execution') }}
            </h2>
            <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record execution') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <form method="get" action="{{ route('slaughter-executions.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Execution period') }}">
                            @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                                <label class="hub-period-filter__toggle">
                                    <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                    <span>{{ $periodLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="hub-period-filter__range">
                            <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                            <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                            <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                            <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                            <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                        </div>

                        <div class="hub-period-filter__actions">
                            <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                            @if ($filters['is_filtered'])
                                <a href="{{ route('slaughter-executions.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="$hubStats['executions_label']" :value="number_format($hubStats['total_executions'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Animals slaughtered')" :value="number_format($hubStats['total_slaughtered'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Cattle')" :value="number_format($hubStats['cattle_kg'], 1).' kg'">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Goat')" :value="number_format($hubStats['goat_kg'], 1).' kg'">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Sheep')" :value="number_format($hubStats['sheep_kg'], 1).' kg'">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Total meat yield')" :value="number_format($hubStats['total_meat_kg'], 1).' kg'">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Executions today')"
                        :value="number_format($hubStats['executions_today'])"
                        :accent="$hubStats['executions_today'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Plans without execution')"
                        :value="number_format($hubStats['plans_without_execution'])"
                        :accent="$hubStats['plans_without_execution'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Completed without batch')"
                        :value="number_format($hubStats['pending_batches'])"
                        :accent="$hubStats['pending_batches'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($executions->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No slaughter executions in this period.') : __('No slaughter executions recorded yet.') }}
                        </p>
                        <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Record first execution') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($executions as $execution)
                            @php
                                $plan = $execution->slaughterPlan;
                                $statusTone = match ($execution->status) {
                                    SlaughterExecution::STATUS_IN_PROGRESS => 'active',
                                    SlaughterExecution::STATUS_COMPLETED => 'active',
                                    SlaughterExecution::STATUS_CANCELLED => 'danger',
                                    default => 'muted',
                                };
                                $statusLabel = ucfirst(str_replace('_', ' ', $execution->status));
                                $initial = strtoupper(substr($plan?->species ?? 'S', 0, 1));
                                $meatYield = $execution->hasPerAnimalSlaughter()
                                    ? number_format($execution->total_meat_quantity_kg, 1).' kg'
                                    : '—';
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('slaughter-executions.show', $execution) }}">
                                        {{ __('Plan #:id', ['id' => $execution->slaughter_plan_id]) }}
                                    </a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $plan?->facility?->facility_name ?? '—' }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="$statusLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Slaughter time')">{{ $execution->slaughter_time->format('d M Y H:i') }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Species')">{{ $plan?->species ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Intake ref')">
                                    @if ($plan?->intake?->reference)
                                        <a href="{{ route('animal-intakes.hub', ['reference' => $plan->intake->reference]) }}" class="font-mono text-xs text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                            {{ $plan->intake->reference }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Animals slaughtered')">{{ number_format($execution->actual_animals_slaughtered) }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Meat yield')">{{ $meatYield }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Per-animal data')">
                                    {{ $execution->hasPerAnimalSlaughter() ? __('Yes') : __('No') }}
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Batches')">{{ number_format($execution->batches->count()) }}</x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight :value="number_format($execution->actual_animals_slaughtered)" :label="__('Animals')" />
                                    <x-entity.profile-highlight
                                        :value="$execution->hasPerAnimalSlaughter() ? number_format($execution->total_meat_quantity_kg, 1).' kg' : '—'"
                                        :label="__('Yield')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('slaughter-executions.show', $execution)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('slaughter-executions.edit', $execution)">{{ __('Edit') }}</x-entity.text-action>
                                    @if ($execution->status === SlaughterExecution::STATUS_COMPLETED && $execution->batches->isEmpty() && auth()->user()?->canProcessorPermission(BusinessUser::PERMISSION_CREATE_BATCH))
                                        <x-entity.text-action :href="route('batches.create', ['slaughter_execution_id' => $execution->id])">{{ __('Create batch') }}</x-entity.text-action>
                                    @endif
                                    <x-entity.text-action-delete
                                        :action="route('slaughter-executions.destroy', $execution)"
                                        :confirm="__('Are you sure you want to delete this slaughter execution? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $executions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
