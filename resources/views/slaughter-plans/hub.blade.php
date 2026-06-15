<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Slaughter planning') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Schedule slaughter sessions') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Each plan ties a facility, approved animal intake, inspector, and head count. After approval you can record ante-mortem, execution, batches, and post-mortem.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
                    <a href="{{ route('slaughter-plans.create') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-bucha-primary text-white text-base font-bold shadow-lg shadow-bucha-primary/25 hover:bg-bucha-burgundy transition-colors ring-2 ring-bucha-primary/20">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Schedule slaughter') }}
                    </a>
                    <p class="text-sm text-slate-500 sm:max-w-xs sm:self-center">
                        {{ __('Intake must be approved with available animals and a valid health certificate where required.') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total plans') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalPlans }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Planned') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700">{{ $plannedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Approved') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">{{ $approvedCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Plans with executions') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $plansWithExecutionsCount }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals scheduled (total)') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalAnimalsScheduled }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals scheduled') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['animals_scheduled']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Excluding executed plans') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Assignment gaps') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['assignment_gap_count'] > 0 ? 'text-red-700' : 'text-slate-900' }}" @if ($hubStats['assignment_gap_count'] > 0) title="{{ __('These plans have no animals assigned — may predate the intake redesign') }}" @endif>
                        {{ $hubStats['assignment_gap_count'] }}
                    </p>
                    @if ($hubStats['assignment_gap_count'] > 0)
                        <p class="mt-0.5 text-xs text-red-600">{{ __('Plans with no animals assigned') }}</p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Approved animal intakes') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $approvedAnimalIntakesCount }}</p>
                </div>
            </div>

            {{-- Plans table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($plans->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">{{ __('No slaughter plans yet.') }}</p>
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

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('slaughter-plans.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All plans') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open a session, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('animal-intakes.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Animal intake') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Approved intakes with capacity feed new slaughter plans.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Intake home') }} →</span>
                </a>
                <a href="{{ route('slaughter-executions.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter execution') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record actual runs against approved plans.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Execution home') }} →</span>
                </a>
                <a href="{{ route('inspectors.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Inspectors') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Each plan requires an active inspector for the facility.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Inspectors home') }} →</span>
                </a>
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
