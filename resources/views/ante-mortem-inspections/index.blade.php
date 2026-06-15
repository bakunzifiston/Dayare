@php
    use App\Models\AnimalIntake;
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

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Ante-mortem inspection hub') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Review ante-mortem records by slaughter session, filter by facility or inspector, and drill into per-animal outcomes.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total inspections') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_inspections']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals examined') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['animals_examined']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Rejected this week') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['rejected_this_week'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['rejected_this_week']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Plans without AM') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['plans_without_am'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['plans_without_am'] > 0) title="{{ __('Active plans with no ante-mortem inspection recorded') }}" @endif>
                        {{ number_format($hubStats['plans_without_am']) }}
                    </p>
                    @if ($hubStats['plans_without_am'] > 0)
                        <p class="mt-0.5 text-xs text-amber-600">{{ __('Sessions awaiting ante-mortem') }}</p>
                    @endif
                </div>
            </div>

            <form method="get" action="{{ route('ante-mortem-inspections.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                    <div>
                        <label for="filter_facility_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Facility') }}</label>
                        <select id="filter_facility_id" name="facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_species" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Species') }}</label>
                        <select id="filter_species" name="species" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (AnimalIntake::SPECIES_OPTIONS as $speciesOption)
                                <option value="{{ $speciesOption }}" @selected(request('species') === $speciesOption)>{{ __($speciesOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_inspector_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Inspector') }}</label>
                        <select id="filter_inspector_id" name="inspector_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($inspectors as $inspector)
                                <option value="{{ $inspector->id }}" @selected((string) request('inspector_id') === (string) $inspector->id)>{{ $inspector->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_date_from" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Date from') }}</label>
                        <input id="filter_date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                    <div>
                        <label for="filter_date_to" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Date to') }}</label>
                        <input id="filter_date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                    <div>
                        <label for="filter_count_source" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Count source') }}</label>
                        <select id="filter_count_source" name="count_source" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="{{ AnteMortemInspection::SOURCE_MANUAL }}" @selected(request('count_source') === AnteMortemInspection::SOURCE_MANUAL)>{{ __('Manual') }}</option>
                            <option value="{{ AnteMortemInspection::SOURCE_ITEMS }}" @selected(request('count_source') === AnteMortemInspection::SOURCE_ITEMS)>{{ __('From assigned items') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="has_rejections" value="1" @checked(request()->boolean('has_rejections'))
                               class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                        {{ __('Show only inspections with rejected animals') }}
                    </label>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Apply') }}
                        </button>
                        @if (request()->hasAny(['facility_id', 'species', 'inspector_id', 'date_from', 'date_to', 'count_source', 'has_rejections']))
                            <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($inspections->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">
                        {{ __('No ante-mortem inspections found.') }}
                        @if (request()->hasAny(['facility_id', 'species', 'inspector_id', 'date_from', 'date_to', 'count_source', 'has_rejections']))
                            {{ __('Try clearing the filters.') }}
                        @endif
                    </p>
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
                    <div class="px-4 py-3 border-t border-slate-100">{{ $inspections->withQueryString()->links() }}</div>
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
