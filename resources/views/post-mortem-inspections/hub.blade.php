@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('post-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
            {{ __('Record inspection') }}
        </a>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="get" action="{{ route('post-mortem-inspections.hub') }}" class="hub-period-filter">
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
                            <a href="{{ route('post-mortem-inspections.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
                <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
            </form>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Post-mortem summary') }}">
                <x-kpi-card stat compact color="slate" :title="$hubStats['inspections_label']" :value="number_format($hubStats['total_inspections'])" glyph="clipboard" />
                <x-kpi-card stat compact color="bucha-success" :title="__('Animals examined')" :value="number_format($hubStats['animals_examined'])" glyph="intake" />
                <x-kpi-card stat compact color="amber" :title="$hubStats['condemned_label']" :value="number_format($hubStats['condemned_count'])" glyph="alert" />
                <x-kpi-card stat compact color="bucha" :title="__('Ready for certificate')" :value="number_format($hubStats['ready_for_cert'])" glyph="certificate" />
            </section>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($inspections->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No post-mortem inspections in this period.') : __('No post-mortem inspections recorded yet.') }}
                        </p>
                        <a href="{{ route('post-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Record first inspection') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Batch') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Inspector') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Examined (kg)') }}</th>
                                    <th class="px-4 py-3">{{ __('Approved (kg)') }}</th>
                                    <th class="px-4 py-3">{{ __('Condemned (kg)') }}</th>
                                    <th class="px-4 py-3">{{ __('Per-animal') }}</th>
                                    <th class="px-4 py-3">{{ __('Result') }}</th>
                                    <th class="px-4 py-3">{{ __('Certificate') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($inspections as $pm)
                                    @php
                                        $resultBadge = match ($pm->result) {
                                            PostMortemInspection::RESULT_APPROVED => 'bg-green-100 text-green-800',
                                            PostMortemInspection::RESULT_PARTIAL => 'bg-yellow-100 text-yellow-800',
                                            PostMortemInspection::RESULT_REJECTED => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <tr class="pm-row cursor-pointer hover:bg-slate-50/80" data-pm-id="{{ $pm->id }}">
                                        <td class="px-4 py-3 text-gray-600">{{ $pm->inspection_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">
                                            <a href="{{ route('batches.show', $pm->batch) }}" class="text-bucha-primary hover:underline">{{ $pm->batch->batch_code ?? '—' }}</a>
                                        </td>
                                        <td class="px-4 py-3">{{ $pm->batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $pm->inspector->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $pm->species }}</td>
                                        <td class="px-4 py-3">{{ $pm->total_examined }}</td>
                                        <td class="px-4 py-3 text-green-700">{{ $pm->approved_quantity }}</td>
                                        <td class="px-4 py-3 {{ $pm->condemned_quantity > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                            {{ $pm->condemned_quantity }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($pm->hasPerAnimalOutcomes())
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">{{ __('Yes') }}</span>
                                            @else
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($pm->result)
                                                <span class="text-xs px-2 py-0.5 rounded-full {{ $resultBadge }}">{{ ucfirst($pm->result) }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($pm->batch->certificate)
                                                <span class="text-green-600">✓</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="pm-actions inline-flex gap-2">
                                                <a href="{{ route('post-mortem-inspections.show', $pm) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('View') }}</a>
                                                <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="pm-detail-row bg-gray-50" id="pm-detail-{{ $pm->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3">
                                            @if ($pm->inspectionItems->isNotEmpty())
                                                <p class="text-sm font-medium text-gray-700 mb-2">
                                                    {{ __('Individual animal outcomes (:count)', ['count' => $pm->inspectionItems->count()]) }}
                                                </p>
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-gray-500">
                                                            <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Species') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Batch meat qty') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Outcome') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Carcass weight') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Released (kg)') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Cold room') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Notes') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($pm->inspectionItems as $pmItem)
                                                            @php
                                                                $outcomeClass = match ($pmItem->outcome) {
                                                                    'approved' => 'bg-green-100 text-green-800',
                                                                    'condemned' => 'bg-red-100 text-red-800',
                                                                    default => 'bg-yellow-100 text-yellow-800',
                                                                };
                                                            @endphp
                                                            <tr class="border-t border-gray-100">
                                                                <td class="py-1 px-2 font-mono text-xs">
                                                                    {{ $pmItem->intakeItem->ear_tag }}
                                                                    @if (str_starts_with($pmItem->intakeItem->ear_tag, 'LEGACY-'))
                                                                        <span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-1 px-2">{{ $pmItem->intakeItem->species }}</td>
                                                                <td class="py-1 px-2">
                                                                    {{ $pmItem->batchItem ? number_format($pmItem->batchItem->meat_quantity_kg, 2).' kg' : '—' }}
                                                                </td>
                                                                <td class="py-1 px-2">
                                                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $outcomeClass }}">
                                                                        {{ ucfirst($pmItem->outcome) }}
                                                                    </span>
                                                                </td>
                                                                <td class="py-1 px-2">
                                                                    @php
                                                                        $carcassKg = $pmItem->displayCarcassWeightKg();
                                                                        $batchRelease = $releaseLookup->get($pm->batch_id, collect());
                                                                        $animalStorage = $batchRelease->get($pmItem->animal_intake_item_id);
                                                                    @endphp
                                                                    {{ $carcassKg !== null ? number_format($carcassKg, 2).' kg' : '—' }}
                                                                </td>
                                                                <x-batch.animal-release-cells :storage="$animalStorage" />
                                                                <td class="py-1 px-2 text-gray-500">{{ $pmItem->outcome_notes ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">
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
                    <div class="px-4 py-3 border-t border-slate-100">{{ $inspections->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.pm-row').forEach(function (row) {
                    row.addEventListener('click', function (e) {
                        if (e.target.closest('.pm-actions')) return;
                        if (e.target.closest('a')) return;
                        var id = this.dataset.pmId;
                        var detail = document.getElementById('pm-detail-' + id);
                        if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
