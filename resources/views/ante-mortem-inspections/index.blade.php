@php
    use App\Models\AnteMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Ante-mortem') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Period and actions') }}">
            <form method="get" action="{{ route('ante-mortem-inspections.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('Inspection period') }}">
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
                    <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                @endif
                <p class="hidden text-xs text-slate-400 sm:block">{{ $filters['range_label'] }}</p>
                <a href="{{ route('ante-mortem-inspections.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record inspection') }}</a>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Ante-mortem summary') }}">
            <x-kpi-card stat compact color="slate" :title="$hubStats['inspections_label']" :value="number_format($hubStats['total_inspections'])" glyph="clipboard" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Animals examined')" :value="number_format($hubStats['animals_examined'])" glyph="intake" />
            <x-kpi-card stat compact color="amber" :title="$hubStats['rejected_label']" :value="number_format($hubStats['rejected_count'])" glyph="alert" />
            <x-kpi-card stat compact color="bucha" :title="__('Plans without AM')" :value="number_format($hubStats['plans_without_am'])" glyph="clock" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Inspections') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $inspections->total(), ['count' => number_format($inspections->total())]) }}</p>
            </div>
            @if ($inspections->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ $filters['is_filtered'] ? __('No inspections in this period') : __('No ante-mortem inspections yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Record an inspection after a slaughter session is scheduled.') }}</p>
                    <a href="{{ route('ante-mortem-inspections.create') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record inspection') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">{{ __('Date') }}</th>
                                <th class="px-4 py-2.5">{{ __('Intake') }}</th>
                                <th class="px-4 py-2.5">{{ __('Facility') }}</th>
                                <th class="px-4 py-2.5">{{ __('Inspector') }}</th>
                                <th class="px-4 py-2.5">{{ __('Species') }}</th>
                                <th class="px-4 py-2.5">{{ __('Examined') }}</th>
                                <th class="px-4 py-2.5">{{ __('Approved') }}</th>
                                <th class="px-4 py-2.5">{{ __('Rejected') }}</th>
                                <th class="px-4 py-2.5">{{ __('Per-animal') }}</th>
                                <th class="px-4 py-2.5">{{ __('Source') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inspections as $inspection)
                                <tr class="am-inspection-row cursor-pointer border-t border-slate-100 hover:bg-slate-50/70" data-inspection-id="{{ $inspection->id }}">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-800">{{ $inspection->inspection_date->format('d M Y') }}</td>
                                    <td class="px-4 py-2.5">
                                        @if ($inspection->slaughterPlan?->intake)
                                            <a href="{{ route('animal-intakes.hub', ['reference' => $inspection->slaughterPlan->intake->reference]) }}" class="font-mono text-xs text-bucha-primary hover:underline">{{ $inspection->slaughterPlan->intake->reference ?? '—' }}</a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-800">{{ $inspection->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-800">{{ $inspection->inspector->full_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $inspection->species }}</td>
                                    <td class="px-4 py-2.5 tabular-nums text-slate-800">{{ $inspection->number_examined }}</td>
                                    <td class="px-4 py-2.5 tabular-nums text-emerald-700">{{ $inspection->number_approved }}</td>
                                    <td class="px-4 py-2.5 tabular-nums {{ $inspection->number_rejected > 0 ? 'font-medium text-red-700' : 'text-slate-400' }}">{{ $inspection->number_rejected }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $inspection->hasPerAnimalOutcomes() ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $inspection->hasPerAnimalOutcomes() ? __('Yes') : __('No') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        @if ($inspection->examined_count_source === AnteMortemInspection::SOURCE_ITEMS)
                                            <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('From items') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Manual') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right am-actions">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('ante-mortem-inspections.show', $inspection) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('View') }}</a>
                                            <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="am-detail-row" id="am-detail-{{ $inspection->id }}" style="display:none;">
                                    <td colspan="100" class="bg-slate-50 px-4 py-3">
                                        @if ($inspection->inspectionItems->isNotEmpty())
                                            <p class="mb-2 text-xs font-semibold text-slate-700">{{ __('Individual animal outcomes (:count)', ['count' => $inspection->inspectionItems->count()]) }}</p>
                                            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-slate-500">
                                                            <th class="px-3 py-2">{{ __('Ear tag') }}</th>
                                                            <th class="px-3 py-2">{{ __('Species') }}</th>
                                                            <th class="px-3 py-2">{{ __('Sex') }}</th>
                                                            <th class="px-3 py-2">{{ __('Outcome') }}</th>
                                                            <th class="px-3 py-2">{{ __('Notes') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($inspection->inspectionItems as $inspItem)
                                                            @php
                                                                $outcomeClass = match ($inspItem->outcome) {
                                                                    'approved' => 'bg-emerald-50 text-emerald-800',
                                                                    'rejected' => 'bg-red-50 text-red-800',
                                                                    default => 'bg-amber-50 text-amber-900',
                                                                };
                                                            @endphp
                                                            <tr class="border-t border-slate-100">
                                                                <td class="px-3 py-1.5 font-mono text-xs">
                                                                    {{ $inspItem->intakeItem->ear_tag ?? '—' }}
                                                                    @if ($inspItem->intakeItem && str_starts_with($inspItem->intakeItem->ear_tag, 'LEGACY-'))
                                                                        <span class="ml-1 rounded bg-slate-100 px-1 text-xs text-slate-400">[legacy]</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-1.5">{{ $inspItem->intakeItem->species ?? '—' }}</td>
                                                                <td class="px-3 py-1.5">{{ $inspItem->intakeItem ? ucfirst($inspItem->intakeItem->sex) : '—' }}</td>
                                                                <td class="px-3 py-1.5"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $outcomeClass }}">{{ ucfirst($inspItem->outcome) }}</span></td>
                                                                <td class="px-3 py-1.5 text-slate-500">{{ $inspItem->outcome_notes ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-slate-500">
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
                <div class="border-t border-slate-100 px-4 py-3">{{ $inspections->links() }}</div>
            @endif
        </section>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.am-inspection-row').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.am-actions') || e.target.closest('a')) return;
                    var detail = document.getElementById('am-detail-' + this.dataset.inspectionId);
                    if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
