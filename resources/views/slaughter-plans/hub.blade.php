<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Slaughter planning') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Period and actions') }}">
            <form method="get" action="{{ route('slaughter-plans.hub') }}" class="flex flex-wrap items-center gap-2">
                <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('Slaughter period') }}">
                    @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                        <label class="cursor-pointer">
                            <input type="radio" name="period" value="{{ $periodKey }}" class="peer sr-only" @checked($filters['period'] === $periodKey)>
                            <span class="inline-flex rounded-md px-3 py-1.5 text-xs font-medium text-slate-600 peer-checked:bg-bucha-primary peer-checked:text-white peer-checked:shadow-sm hover:text-slate-900">{{ $periodLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('Date from') }}">
                <span class="text-xs text-slate-400">–</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('Date to') }}">
                <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Apply') }}</button>
                @if ($filters['is_filtered'])
                    <a href="{{ route('slaughter-plans.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                @endif
                <p class="hidden text-xs text-slate-400 sm:block">{{ $filters['range_label'] }}</p>
                <a href="{{ route('slaughter-plans.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule slaughter') }}</a>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Slaughter plans summary') }}">
            <x-kpi-card stat compact color="slate" :title="$hubStats['plans_label']" :value="number_format($totalPlans)" glyph="clipboard" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Approved')" :value="number_format($approvedCount)" glyph="check" />
            <x-kpi-card stat compact color="amber" :title="__('With executions')" :value="number_format($plansWithExecutionsCount)" glyph="play" />
            <x-kpi-card stat compact color="bucha" :title="__('Animals scheduled')" :value="number_format($hubStats['cattle_count'] + $hubStats['goat_count'] + $hubStats['sheep_count'])" glyph="intake" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Plans') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $plans->total(), ['count' => number_format($plans->total())]) }}</p>
            </div>
            @if ($plans->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ $filters['is_filtered'] ? __('No slaughter plans in this period') : __('No slaughter plans yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Schedule a session after animals are received at intake.') }}</p>
                    <a href="{{ route('slaughter-plans.create') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule slaughter') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">{{ __('Date & time') }}</th>
                                <th class="px-4 py-2.5">{{ __('Facility') }}</th>
                                <th class="px-4 py-2.5">{{ __('Species') }}</th>
                                <th class="px-4 py-2.5">{{ __('Assigned') }}</th>
                                <th class="px-4 py-2.5">{{ __('Intake ref') }}</th>
                                <th class="px-4 py-2.5">{{ __('Animals') }}</th>
                                <th class="px-4 py-2.5">{{ __('Status') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                <tr class="plan-row cursor-pointer border-t border-slate-100 hover:bg-slate-50/70" data-plan-id="{{ $plan->id }}">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-800">{{ $plan->slaughterDateDisplay() }}</td>
                                    <td class="px-4 py-2.5 text-slate-800">{{ $plan->facility->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $plan->species }}</td>
                                    <td class="px-4 py-2.5">
                                        @if ($plan->animal_intake_id)
                                            <span class="{{ $plan->isFullyAssigned() ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium' }}">
                                                {{ $plan->assigned_count }} / {{ $plan->number_of_animals_scheduled }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($plan->intake)
                                            <a href="{{ route('animal-intakes.hub', ['reference' => $plan->intake->reference]) }}" class="font-mono text-xs text-bucha-primary hover:underline">{{ $plan->intake->reference ?? '—' }}</a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums text-slate-800">{{ $plan->number_of_animals_scheduled }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $plan->status === \App\Models\SlaughterPlan::STATUS_APPROVED ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ ucfirst($plan->status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right plan-actions">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('slaughter-plans.show', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                            <a href="{{ route('slaughter-plans.edit', $plan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('slaughter-plans.destroy', $plan) }}" class="inline" onsubmit="return confirm(@js(__('Are you sure you want to delete this slaughter plan? This cannot be undone.')));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="plan-detail-row" id="plan-detail-{{ $plan->id }}" style="display:none;">
                                    <td colspan="100" class="bg-slate-50 px-4 py-3">
                                        @if ($plan->assignedItems->isNotEmpty())
                                            <p class="mb-2 text-xs font-semibold text-slate-700">{{ __('Assigned animals (:count)', ['count' => $plan->assignedItems->count()]) }}</p>
                                            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-slate-500">
                                                            <th class="px-3 py-2">{{ __('Ear tag') }}</th>
                                                            <th class="px-3 py-2">{{ __('Species') }}</th>
                                                            <th class="px-3 py-2">{{ __('Sex') }}</th>
                                                            <th class="px-3 py-2">{{ __('Age') }}</th>
                                                            <th class="px-3 py-2">{{ __('Weight') }}</th>
                                                            <th class="px-3 py-2">{{ __('Health status') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($plan->assignedItems as $item)
                                                            @php
                                                                $badgeClass = match ($item->health_status) {
                                                                    'healthy' => 'bg-emerald-50 text-emerald-800',
                                                                    'under_observation' => 'bg-amber-50 text-amber-900',
                                                                    default => 'bg-red-50 text-red-800',
                                                                };
                                                            @endphp
                                                            <tr class="border-t border-slate-100">
                                                                <td class="px-3 py-1.5 font-mono text-xs">
                                                                    {{ $item->ear_tag }}
                                                                    @if (str_starts_with($item->ear_tag, 'LEGACY-'))
                                                                        <span class="ml-1 rounded bg-slate-100 px-1 text-xs text-slate-400">[legacy]</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-1.5">{{ $item->species }}</td>
                                                                <td class="px-3 py-1.5">{{ ucfirst($item->sex) }}</td>
                                                                <td class="px-3 py-1.5">{{ $item->age_months ? $item->age_months.' '.__('months') : '—' }}</td>
                                                                <td class="px-3 py-1.5">{{ $item->live_weight_kg ? $item->live_weight_kg.' kg' : '—' }}</td>
                                                                <td class="px-3 py-1.5"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $item->health_status_label }}</span></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @elseif ($plan->intake && $plan->intake->items->isNotEmpty())
                                            <p class="text-sm text-slate-500">
                                                {{ __('No animals assigned to this plan yet.') }}
                                                <a href="{{ route('slaughter-plans.edit', $plan) }}" class="font-medium text-bucha-primary hover:underline">{{ __('Edit and save the plan') }}</a>
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
                <div class="border-t border-slate-100 px-4 py-3">{{ $plans->links() }}</div>
            @endif
        </section>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.plan-row').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.plan-actions') || e.target.closest('a')) return;
                    var detail = document.getElementById('plan-detail-' + this.dataset.planId);
                    if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
