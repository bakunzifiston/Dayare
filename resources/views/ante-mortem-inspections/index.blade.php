@php
    use App\Models\AnteMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Ante-mortem inspections') }}
            </h2>
            <a href="{{ route('ante-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record inspection') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="get" action="{{ route('ante-mortem-inspections.index') }}" class="hub-period-filter">
                <div class="hub-period-filter__bar">
                    <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Inspection period') }}">
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
                            <a href="{{ route('ante-mortem-inspections.index') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
                <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
            </form>

            {{-- Summary KPI bar --}}
            <div class="profile-kpi-grid">
                <x-entity.kpi-stat :label="$hubStats['inspections_label']" :value="number_format($hubStats['total_inspections'])" accent>
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Animals examined')" :value="number_format($hubStats['animals_examined'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
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
                <x-entity.kpi-stat
                    :label="$hubStats['rejected_label']"
                    :value="number_format($hubStats['rejected_count'])"
                    :accent="$hubStats['rejected_count'] > 0"
                >
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat
                    :label="__('Plans without AM')"
                    :value="number_format($hubStats['plans_without_am'])"
                    :accent="$hubStats['plans_without_am'] > 0"
                >
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($inspections->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No ante-mortem inspections in this period.') : __('No ante-mortem inspections recorded yet.') }}
                        </p>
                        <a href="{{ route('ante-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Record first inspection') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Plan') }}</th>
                                    <th class="px-4 py-3">{{ __('Intake') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Inspector') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Examined') }}</th>
                                    <th class="px-4 py-3">{{ __('Approved') }}</th>
                                    <th class="px-4 py-3">{{ __('Rejected') }}</th>
                                    <th class="px-4 py-3">{{ __('Per-animal') }}</th>
                                    <th class="px-4 py-3">{{ __('Source') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($inspections as $inspection)
                                    <tr class="am-inspection-row cursor-pointer hover:bg-slate-50/80 transition-colors" data-inspection-id="{{ $inspection->id }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ $inspection->inspection_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            @if ($inspection->slaughterPlan)
                                                <a href="{{ route('slaughter-plans.show', $inspection->slaughterPlan) }}"
                                                   class="font-mono text-xs text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                                    #{{ $inspection->slaughter_plan_id }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($inspection->slaughterPlan?->intake)
                                                <a href="{{ route('animal-intakes.hub', ['reference' => $inspection->slaughterPlan->intake->reference]) }}"
                                                   class="font-mono text-xs text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                                    {{ $inspection->slaughterPlan->intake->reference ?? '—' }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-800">{{ $inspection->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $inspection->inspector->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $inspection->species }}</td>
                                        <td class="px-4 py-3 tabular-nums text-slate-800">{{ $inspection->number_examined }}</td>
                                        <td class="px-4 py-3 tabular-nums text-emerald-700">{{ $inspection->number_approved }}</td>
                                        <td class="px-4 py-3 tabular-nums {{ $inspection->number_rejected > 0 ? 'text-red-700 font-medium' : 'text-slate-400' }}">
                                            {{ $inspection->number_rejected }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($inspection->hasPerAnimalOutcomes())
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Yes') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($inspection->examined_count_source === AnteMortemInspection::SOURCE_ITEMS)
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">{{ __('From items') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Manual') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right am-actions">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                                <a href="{{ route('ante-mortem-inspections.show', $inspection) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('Show') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="am-detail-row" id="am-detail-{{ $inspection->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3 bg-gray-50">
                                            @if ($inspection->inspectionItems->isNotEmpty())
                                                <p class="text-sm font-medium text-gray-700 mb-2">
                                                    {{ __('Individual animal outcomes (:count)', ['count' => $inspection->inspectionItems->count()]) }}
                                                </p>
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-gray-500">
                                                            <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Species') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Sex') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Outcome') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Notes') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($inspection->inspectionItems as $inspItem)
                                                            @php
                                                                $outcomeClass = match ($inspItem->outcome) {
                                                                    'approved' => 'bg-green-100 text-green-800',
                                                                    'rejected' => 'bg-red-100 text-red-800',
                                                                    default => 'bg-yellow-100 text-yellow-800',
                                                                };
                                                            @endphp
                                                            <tr class="border-t border-gray-100">
                                                                <td class="py-1 px-2 font-mono text-xs">
                                                                    {{ $inspItem->intakeItem->ear_tag ?? '—' }}
                                                                    @if ($inspItem->intakeItem && str_starts_with($inspItem->intakeItem->ear_tag, 'LEGACY-'))
                                                                        <span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-1 px-2">{{ $inspItem->intakeItem->species ?? '—' }}</td>
                                                                <td class="py-1 px-2">{{ $inspItem->intakeItem ? ucfirst($inspItem->intakeItem->sex) : '—' }}</td>
                                                                <td class="py-1 px-2">
                                                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $outcomeClass }}">
                                                                        {{ ucfirst($inspItem->outcome) }}
                                                                    </span>
                                                                </td>
                                                                <td class="py-1 px-2 text-gray-500">{{ $inspItem->outcome_notes ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">
                                                    {{ __('No individual animal outcomes recorded for this inspection.') }}
                                                    @if ($inspection->examined_count_source === AnteMortemInspection::SOURCE_MANUAL)
                                                        {{ __('Counts were entered manually.') }}
                                                    @endif
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $inspections->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.am-inspection-row').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.am-actions')) {
                        return;
                    }
                    if (e.target.closest('a')) {
                        return;
                    }
                    var id = this.dataset.inspectionId;
                    var detail = document.getElementById('am-detail-' + id);
                    if (detail) {
                        detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
