<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Slaughter planning') }}
            </h2>
            <a href="{{ route('slaughter-plans.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Schedule slaughter') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="get" action="{{ route('slaughter-plans.hub') }}" class="hub-period-filter">
                <div class="hub-period-filter__bar">
                    <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Slaughter period') }}">
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
                            <a href="{{ route('slaughter-plans.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
                <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
            </form>

            {{-- Summary KPI bar --}}
            <div class="profile-kpi-grid">
                <x-entity.kpi-stat :label="$hubStats['plans_label']" :value="number_format($totalPlans)" accent>
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Approved')" :value="number_format($approvedCount)">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('With executions')" :value="number_format($plansWithExecutionsCount)">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Cattle')" :value="number_format($hubStats['cattle_count'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Goat')" :value="number_format($hubStats['goat_count'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Sheep')" :value="number_format($hubStats['sheep_count'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
            </div>

            {{-- Plans table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($plans->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No slaughter plans in this period.') : __('No slaughter plans yet.') }}
                        </p>
                        <a href="{{ route('slaughter-plans.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Schedule first slaughter') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Assigned') }}</th>
                                    <th class="px-4 py-3">{{ __('Intake ref') }}</th>
                                    <th class="px-4 py-3">{{ __('Animals') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($plans as $plan)
                                    <tr class="plan-row cursor-pointer hover:bg-slate-50/80 transition-colors" data-plan-id="{{ $plan->id }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ $plan->slaughter_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $plan->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $plan->species }}</td>
                                        <td class="px-4 py-3">
                                            @if ($plan->animal_intake_id)
                                                <span class="{{ $plan->isFullyAssigned() ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium' }}">
                                                    {{ $plan->assigned_count }} / {{ $plan->number_of_animals_scheduled }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($plan->intake)
                                                <a href="{{ route('animal-intakes.hub', ['reference' => $plan->intake->reference]) }}"
                                                   class="font-mono text-xs text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                                    {{ $plan->intake->reference ?? '—' }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 tabular-nums text-slate-800">{{ $plan->number_of_animals_scheduled }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                @if ($plan->status === \App\Models\SlaughterPlan::STATUS_APPROVED) bg-emerald-100 text-emerald-800
                                                @else bg-amber-100 text-amber-800 @endif">
                                                {{ ucfirst($plan->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right plan-actions">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('slaughter-plans.show', $plan) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('View') }}</a>
                                                <a href="{{ route('slaughter-plans.edit', $plan) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                                <form method="POST" action="{{ route('slaughter-plans.destroy', $plan) }}" class="inline" onsubmit="return confirm(@js(__('Are you sure you want to delete this slaughter plan? This cannot be undone.')));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="plan-detail-row bg-slate-50/60" id="plan-detail-{{ $plan->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3">
                                            @if ($plan->assignedItems->isNotEmpty())
                                                <p class="text-sm font-medium text-slate-700 mb-2">
                                                    {{ __('Assigned animals (:count)', ['count' => $plan->assignedItems->count()]) }}
                                                </p>
                                                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
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
                                                            @foreach ($plan->assignedItems as $item)
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
                                                                        @php
                                                                            $badgeClass = match ($item->health_status) {
                                                                                'healthy' => 'bg-green-100 text-green-800',
                                                                                'under_observation' => 'bg-amber-100 text-amber-800',
                                                                                default => 'bg-red-100 text-red-800',
                                                                            };
                                                                        @endphp
                                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                                            {{ $item->health_status_label }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @elseif ($plan->intake && $plan->intake->items->isNotEmpty())
                                                <p class="text-sm text-slate-500">
                                                    {{ __('No animals assigned to this plan yet.') }}
                                                    <a href="{{ route('slaughter-plans.edit', $plan) }}" class="font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                                        {{ __('Edit and save the plan') }}
                                                    </a>
                                                    {{ __('to assign :count animal(s) from intake :ref.', [
                                                        'count' => $plan->number_of_animals_scheduled,
                                                        'ref' => $plan->intake->reference ?? '#'.$plan->intake->id,
                                                    ]) }}
                                                </p>
                                            @else
                                                <p class="text-sm text-slate-500">
                                                    {{ __('This intake predates individual animal tracking. Run') }}
                                                    <code class="rounded bg-slate-100 px-1 text-xs">php artisan intake:backfill</code>
                                                    {{ __('to generate item records, then save the plan to assign animals.') }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $plans->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.plan-row').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.plan-actions')) {
                        return;
                    }
                    var id = this.dataset.planId;
                    var detail = document.getElementById('plan-detail-' + id);
                    if (detail) {
                        detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
