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

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Slaughter executions summary') }}">
                    <x-kpi-card stat compact color="slate" :title="$hubStats['executions_label']" :value="number_format($hubStats['total_executions'])" glyph="play" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Animals slaughtered')" :value="number_format($hubStats['total_slaughtered'])" glyph="intake" />
                    <x-kpi-card stat compact color="amber" :title="__('Total meat yield')" :value="number_format($hubStats['total_meat_kg'], 1)" subtitle="kg" glyph="weight" />
                    <x-kpi-card stat compact color="bucha" :title="__('Plans without execution')" :value="number_format($hubStats['plans_without_execution'])" glyph="alert" />
                </section>

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
                                    @if ($execution->status === SlaughterExecution::STATUS_IN_PROGRESS)
                                        <x-entity.text-action :href="route('slaughter-executions.edit', $execution)">{{ __('Continue') }}</x-entity.text-action>
                                    @endif
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
