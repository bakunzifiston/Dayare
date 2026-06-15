@php
    use App\Models\SlaughterExecution;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('slaughter-executions.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter execution') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All executions') }}
                </h2>
            </div>
            <a href="{{ route('slaughter-executions.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record execution') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total executions') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_executions']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total animals slaughtered') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_slaughtered']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total meat yield') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_meat_kg'], 2) }} kg</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Plans without execution') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['plans_without_execution'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['plans_without_execution'] > 0) title="{{ __('Active plans with no slaughter execution recorded') }}" @endif>
                        {{ number_format($hubStats['plans_without_execution']) }}
                    </p>
                    @if ($hubStats['plans_without_execution'] > 0)
                        <p class="mt-0.5 text-xs text-amber-600">{{ __('Sessions awaiting slaughter execution') }}</p>
                    @endif
                </div>
            </div>

            <form method="get" action="{{ route('slaughter-executions.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                        <label for="filter_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (SlaughterExecution::STATUSES as $statusOption)
                                <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
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
                        <label for="filter_has_items" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Per-animal data') }}</label>
                        <select id="filter_has_items" name="has_items" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('has_items') === '1')>{{ __('With per-animal data') }}</option>
                            <option value="0" @selected(request('has_items') === '0')>{{ __('Without per-animal data') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Apply') }}
                    </button>
                    @if (request()->hasAny(['facility_id', 'status', 'date_from', 'date_to', 'has_items']))
                        <a href="{{ route('slaughter-executions.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($executions->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">
                        {{ __('No slaughter executions found.') }}
                        @if (request()->hasAny(['facility_id', 'status', 'date_from', 'date_to', 'has_items']))
                            {{ __('Try clearing the filters.') }}
                        @endif
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Plan ref') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Animals') }}</th>
                                    <th class="px-4 py-3">{{ __('Total yield') }}</th>
                                    <th class="px-4 py-3">{{ __('Source') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Batches') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($executions as $execution)
                                    @php
                                        $statusBadge = match ($execution->status) {
                                            SlaughterExecution::STATUS_SCHEDULED => 'bg-slate-100 text-slate-700',
                                            SlaughterExecution::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-800',
                                            SlaughterExecution::STATUS_COMPLETED => 'bg-green-100 text-green-800',
                                            SlaughterExecution::STATUS_CANCELLED => 'bg-red-100 text-red-800',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp
                                    <tr class="se-execution-row cursor-pointer hover:bg-slate-50/80 transition-colors" data-execution-id="{{ $execution->id }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                            {{ $execution->slaughter_time->format('d M Y H:i') }}
                                            @if ($execution->exceedsAnteMortemWindow())
                                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800"
                                                      title="{{ $execution->anteMortemWindowReportNote() }}">
                                                    {{ __('AM window exceeded') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($execution->slaughterPlan)
                                                <a href="{{ route('slaughter-plans.show', $execution->slaughterPlan) }}"
                                                   class="font-mono text-xs text-bucha-primary hover:text-bucha-burgundy hover:underline">
                                                    #{{ $execution->slaughter_plan_id }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-800">{{ $execution->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3 tabular-nums text-slate-800">{{ $execution->actual_animals_slaughtered }}</td>
                                        <td class="px-4 py-3 tabular-nums text-slate-800">
                                            @if ($execution->total_meat_quantity_kg > 0)
                                                {{ number_format($execution->total_meat_quantity_kg, 2) }} kg
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($execution->slaughter_count_source === SlaughterExecution::SOURCE_ITEMS)
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">{{ __('From items') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Manual') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge }}">
                                                {{ ucfirst(str_replace('_', ' ', $execution->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 tabular-nums text-slate-800">{{ $execution->batches->count() }}</td>
                                        <td class="px-4 py-3 text-right se-actions">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('slaughter-executions.edit', $execution) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                                <a href="{{ route('slaughter-executions.show', $execution) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('Show') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="se-detail-row bg-gray-50" id="se-detail-{{ $execution->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3">
                                            @if ($execution->executionItems->isNotEmpty())
                                                <p class="text-sm font-medium text-gray-700 mb-2">
                                                    {{ __('Individual animal slaughter (:count animals)', ['count' => $execution->executionItems->count()]) }}
                                                    — {{ __('Total:') }} {{ number_format($execution->total_meat_quantity_kg, 2) }} kg
                                                </p>
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs text-gray-500">
                                                            <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Species') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Sex') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Meat qty') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Yield %') }}</th>
                                                            <th class="pb-1 px-2">{{ __('Notes') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($execution->executionItems as $execItem)
                                                        @php
                                                            $intake   = $execItem->intakeItem;
                                                            $yieldPct = ($intake->live_weight_kg && $intake->live_weight_kg > 0)
                                                                ? round($execItem->meat_quantity_kg / $intake->live_weight_kg * 100, 1)
                                                                : null;
                                                        @endphp
                                                        <tr class="border-t border-gray-100">
                                                            <td class="py-1 px-2 font-mono text-xs">
                                                                {{ $intake->ear_tag }}
                                                                @if (str_starts_with($intake->ear_tag, 'LEGACY-'))
                                                                    <span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                                                                @endif
                                                            </td>
                                                            <td class="py-1 px-2">{{ $intake->species }}</td>
                                                            <td class="py-1 px-2">{{ ucfirst($intake->sex) }}</td>
                                                            <td class="py-1 px-2">
                                                                {{ $intake->live_weight_kg ? number_format($intake->live_weight_kg, 2).' kg' : '—' }}
                                                            </td>
                                                            <td class="py-1 px-2 font-medium">
                                                                {{ number_format($execItem->meat_quantity_kg, 2) }} kg
                                                            </td>
                                                            <td class="py-1 px-2">{{ $yieldPct !== null ? $yieldPct.'%' : '—' }}</td>
                                                            <td class="py-1 px-2 text-gray-500">{{ $execItem->notes ?? '—' }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">{{ __('No per-animal slaughter data recorded for this execution.') }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $executions->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.se-execution-row').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.se-actions')) return;
                    if (e.target.closest('a')) return;
                    var id     = this.dataset.executionId;
                    var detail = document.getElementById('se-detail-' + id);
                    if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
