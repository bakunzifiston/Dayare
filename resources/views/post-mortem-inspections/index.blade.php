@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('post-mortem-inspections.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Post-mortem inspections') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All post-mortem inspections') }}
                </h2>
            </div>
            <a href="{{ route('post-mortem-inspections.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('+ New inspection') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total inspections') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_inspections']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals examined') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['animals_examined']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Condemned this week') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['condemned_this_week'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['condemned_this_week']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Batches without PM') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['batches_without_pm'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['batches_without_pm'] > 0) title="{{ __('Batches pending post-mortem inspection') }}" @endif>
                        {{ number_format($hubStats['batches_without_pm']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready for certificate') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_for_cert'] > 0 ? 'text-blue-700' : 'text-slate-900' }}"
                       @if ($hubStats['ready_for_cert'] > 0) title="{{ __('PM approved, no certificate issued yet') }}" @endif>
                        {{ number_format($hubStats['ready_for_cert']) }}
                    </p>
                </div>
            </div>

            <form method="get" action="{{ route('post-mortem-inspections.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div>
                        <label for="filter_batch_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Batch') }}</label>
                        <select id="filter_batch_id" name="batch_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) request('batch_id') === (string) $batch->id)>{{ $batch->batch_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_result" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Result') }}</label>
                        <select id="filter_result" name="result" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="{{ PostMortemInspection::RESULT_APPROVED }}" @selected(request('result') === PostMortemInspection::RESULT_APPROVED)>{{ __('Approved') }}</option>
                            <option value="{{ PostMortemInspection::RESULT_PARTIAL }}" @selected(request('result') === PostMortemInspection::RESULT_PARTIAL)>{{ __('Partial') }}</option>
                            <option value="{{ PostMortemInspection::RESULT_REJECTED }}" @selected(request('result') === PostMortemInspection::RESULT_REJECTED)>{{ __('Rejected') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_has_per_animal" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Has per-animal data') }}</label>
                        <select id="filter_has_per_animal" name="has_per_animal" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('has_per_animal') === '1')>{{ __('Yes') }}</option>
                            <option value="0" @selected(request('has_per_animal') === '0')>{{ __('No') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_has_cert" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Has certificate') }}</label>
                        <select id="filter_has_cert" name="has_cert" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('has_cert') === '1')>{{ __('Yes') }}</option>
                            <option value="0" @selected(request('has_cert') === '0')>{{ __('No') }}</option>
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
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Apply') }}
                    </button>
                    @if (request()->hasAny(['batch_id', 'result', 'has_per_animal', 'has_cert', 'date_from', 'date_to']))
                        <a href="{{ route('post-mortem-inspections.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($inspections->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">
                        {{ __('No post-mortem inspections found.') }}
                        @if (request()->hasAny(['batch_id', 'result', 'has_per_animal', 'has_cert', 'date_from', 'date_to']))
                            {{ __('Try clearing the filters.') }}
                        @else
                            <a href="{{ route('post-mortem-inspections.create') }}" class="text-blue-600 hover:underline">{{ __('Record the first one →') }}</a>
                        @endif
                    </p>
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
                                    <th class="px-4 py-3">{{ __('Examined') }}</th>
                                    <th class="px-4 py-3">{{ __('Approved') }}</th>
                                    <th class="px-4 py-3">{{ __('Condemned') }}</th>
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
                                                <a href="{{ route('post-mortem-inspections.show', $pm) }}" class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                                <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
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
                                                                    {{ $pmItem->carcass_weight_kg ? number_format($pmItem->carcass_weight_kg, 2).' kg' : '—' }}
                                                                </td>
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
                        var id = this.dataset.pmId;
                        var detail = document.getElementById('pm-detail-' + id);
                        if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
