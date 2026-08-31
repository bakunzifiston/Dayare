@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Post-mortem') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Period and actions') }}">
            <form method="get" action="{{ route('post-mortem-inspections.hub') }}" class="flex flex-wrap items-center gap-2">
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
                    <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                @endif
                <p class="hidden text-xs text-slate-400 sm:block">{{ $filters['range_label'] }}</p>
                <a href="{{ route('post-mortem-inspections.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record inspection') }}</a>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Post-mortem summary') }}">
            <x-kpi-card stat compact color="slate" :title="$hubStats['inspections_label']" :value="number_format($hubStats['total_inspections'])" glyph="clipboard" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Animals examined')" :value="number_format($hubStats['animals_examined'])" glyph="intake" />
            <x-kpi-card stat compact color="amber" :title="$hubStats['condemned_label']" :value="number_format($hubStats['condemned_count'])" glyph="alert" />
            <x-kpi-card stat compact color="bucha" :title="__('Ready for certificate')" :value="number_format($hubStats['ready_for_cert'])" glyph="certificate" />
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Inspections') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $inspections->total(), ['count' => number_format($inspections->total())]) }}</p>
            </div>
            @if ($inspections->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ $filters['is_filtered'] ? __('No inspections in this period') : __('No post-mortem inspections yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Record an inspection after a slaughter execution is complete.') }}</p>
                    <a href="{{ route('post-mortem-inspections.create') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record inspection') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">{{ __('Date') }}</th>
                                <th class="px-4 py-2.5">{{ __('Batch') }}</th>
                                <th class="px-4 py-2.5">{{ __('Facility') }}</th>
                                <th class="px-4 py-2.5">{{ __('Inspector') }}</th>
                                <th class="px-4 py-2.5">{{ __('Species') }}</th>
                                <th class="px-4 py-2.5">{{ __('Examined (kg)') }}</th>
                                <th class="px-4 py-2.5">{{ __('Approved (kg)') }}</th>
                                <th class="px-4 py-2.5">{{ __('Condemned (kg)') }}</th>
                                <th class="px-4 py-2.5">{{ __('Per-animal') }}</th>
                                <th class="px-4 py-2.5">{{ __('Result') }}</th>
                                <th class="px-4 py-2.5">{{ __('Certificate') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inspections as $pm)
                                @php
                                    $resultBadge = match ($pm->result) {
                                        PostMortemInspection::RESULT_APPROVED => 'bg-emerald-50 text-emerald-800',
                                        PostMortemInspection::RESULT_PARTIAL => 'bg-amber-50 text-amber-900',
                                        PostMortemInspection::RESULT_REJECTED => 'bg-red-50 text-red-800',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <tr class="pm-row cursor-pointer border-t border-slate-100 hover:bg-slate-50/70" data-pm-id="{{ $pm->id }}">
                                    <td class="px-4 py-2.5 text-slate-600">{{ $pm->inspection_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">
                                        <a href="{{ route('batches.show', $pm->batch) }}" class="text-bucha-primary hover:underline">{{ $pm->batch->batch_code ?? '—' }}</a>
                                    </td>
                                    <td class="px-4 py-2.5">{{ $pm->batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $pm->inspector->full_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $pm->species }}</td>
                                    <td class="px-4 py-2.5 tabular-nums">{{ $pm->total_examined }}</td>
                                    <td class="px-4 py-2.5 tabular-nums text-emerald-700">{{ $pm->approved_quantity }}</td>
                                    <td class="px-4 py-2.5 tabular-nums {{ $pm->condemned_quantity > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $pm->condemned_quantity }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $pm->hasPerAnimalOutcomes() ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $pm->hasPerAnimalOutcomes() ? __('Yes') : __('No') }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($pm->result)
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $resultBadge }}">{{ ucfirst($pm->result) }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($pm->batch->certificate)
                                            <span class="text-emerald-600">✓</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="pm-actions inline-flex gap-2">
                                            <a href="{{ route('post-mortem-inspections.show', $pm) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('View') }}</a>
                                            <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="pm-detail-row" id="pm-detail-{{ $pm->id }}" style="display:none;">
                                    <td colspan="100" class="bg-slate-50 px-4 py-3">
                                        @if ($pm->inspectionItems->isNotEmpty())
                                            <p class="mb-2 text-xs font-semibold text-slate-700">{{ __('Individual animal outcomes (:count)', ['count' => $pm->inspectionItems->count()]) }}</p>
                                            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-slate-500">
                                                            <th class="px-3 py-2">{{ __('Ear tag') }}</th>
                                                            <th class="px-3 py-2">{{ __('Species') }}</th>
                                                            <th class="px-3 py-2">{{ __('Batch meat qty') }}</th>
                                                            <th class="px-3 py-2">{{ __('Outcome') }}</th>
                                                            <th class="px-3 py-2">{{ __('Carcass weight') }}</th>
                                                            <th class="px-3 py-2">{{ __('Released (kg)') }}</th>
                                                            <th class="px-3 py-2">{{ __('Cold room') }}</th>
                                                            <th class="px-3 py-2">{{ __('Notes') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($pm->inspectionItems as $pmItem)
                                                            @php
                                                                $outcomeClass = match ($pmItem->outcome) {
                                                                    'approved' => 'bg-emerald-50 text-emerald-800',
                                                                    'condemned' => 'bg-red-50 text-red-800',
                                                                    default => 'bg-amber-50 text-amber-900',
                                                                };
                                                                $carcassKg = $pmItem->displayCarcassWeightKg();
                                                                $batchRelease = $releaseLookup->get($pm->batch_id, collect());
                                                                $animalStorage = $batchRelease->get($pmItem->animal_intake_item_id);
                                                            @endphp
                                                            <tr class="border-t border-slate-100">
                                                                <td class="px-3 py-1.5 font-mono text-xs">
                                                                    {{ $pmItem->intakeItem->ear_tag }}
                                                                    @if (str_starts_with($pmItem->intakeItem->ear_tag, 'LEGACY-'))
                                                                        <span class="ml-1 rounded bg-slate-100 px-1 text-xs text-slate-400">[legacy]</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-1.5">{{ $pmItem->intakeItem->species }}</td>
                                                                <td class="px-3 py-1.5">{{ $pmItem->batchItem ? number_format($pmItem->batchItem->meat_quantity_kg, 2).' kg' : '—' }}</td>
                                                                <td class="px-3 py-1.5"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $outcomeClass }}">{{ ucfirst($pmItem->outcome) }}</span></td>
                                                                <td class="px-3 py-1.5">{{ $carcassKg !== null ? number_format($carcassKg, 2).' kg' : '—' }}</td>
                                                                <x-batch.animal-release-cells :storage="$animalStorage" />
                                                                <td class="px-3 py-1.5 text-slate-500">{{ $pmItem->outcome_notes ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-slate-500">
                                                {{ __('No individual animal outcomes recorded. Inspection used aggregate counts only — examined: :examined, approved: :approved, condemned: :condemned.', [
                                                    'examined' => $pm->total_examined,
                                                    'approved' => $pm->approved_quantity,
                                                    'condemned' => $pm->condemned_quantity,
                                                ]) }}
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
                document.querySelectorAll('.pm-row').forEach(function (row) {
                    row.addEventListener('click', function (e) {
                        if (e.target.closest('.pm-actions') || e.target.closest('a')) return;
                        var detail = document.getElementById('pm-detail-' + this.dataset.pmId);
                        if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
