@php
    use App\Models\Batch;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('batches.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All batches') }}
                </h2>
            </div>
            <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Create batch') }}
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
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total batches') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_batches']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Pending post-mortem') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['pending_pm'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['pending_pm']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready for certificate') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_for_cert'] > 0 ? 'text-blue-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['ready_for_cert']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cold chain issues') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['cold_chain_issues'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['cold_chain_issues']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total quantity') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_quantity'], 2) }}</p>
                </div>
            </div>

            <form method="get" action="{{ route('batches.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
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
                            @foreach (Batch::STATUSES as $statusOption)
                                <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ ucfirst($statusOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_cold_chain" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cold chain') }}</label>
                        <select id="filter_cold_chain" name="cold_chain_status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="ok" @selected(request('cold_chain_status') === 'ok')>{{ __('OK') }}</option>
                            <option value="at_risk" @selected(request('cold_chain_status') === 'at_risk')>{{ __('At risk') }}</option>
                            <option value="compromised" @selected(request('cold_chain_status') === 'compromised')>{{ __('Compromised') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_has_pm" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Has PM') }}</label>
                        <select id="filter_has_pm" name="has_pm" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('has_pm') === '1')>{{ __('Yes') }}</option>
                            <option value="0" @selected(request('has_pm') === '0')>{{ __('No') }}</option>
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
                    @if (request()->hasAny(['facility_id', 'status', 'cold_chain_status', 'has_pm', 'has_cert', 'date_from', 'date_to']))
                        <a href="{{ route('batches.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($batches->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">
                        {{ __('No batches found.') }}
                        @if (request()->hasAny(['facility_id', 'status', 'cold_chain_status', 'has_pm', 'has_cert', 'date_from', 'date_to']))
                            {{ __('Try clearing the filters.') }}
                        @else
                            <a href="{{ route('batches.create') }}" class="text-blue-600 hover:underline">{{ __('Create the first batch →') }}</a>
                        @endif
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Batch code') }}</th>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Execution') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Species') }}</th>
                                    <th class="px-4 py-3">{{ __('Quantity') }}</th>
                                    <th class="px-4 py-3">{{ __('Animals') }}</th>
                                    <th class="px-4 py-3">{{ __('Cold chain') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('PM') }}</th>
                                    <th class="px-4 py-3">{{ __('Certificate') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($batches as $batch)
                                    @php
                                        $statusBadge = match ($batch->status) {
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <tr class="batch-row cursor-pointer hover:bg-slate-50/80" data-batch-id="{{ $batch->id }}">
                                        <td class="px-4 py-3 font-mono text-xs">
                                            <a href="{{ route('batches.show', $batch) }}" class="text-bucha-primary hover:underline">{{ $batch->batch_code }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $batch->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('slaughter-executions.show', $batch->slaughterExecution) }}" class="text-bucha-primary hover:underline">
                                                {{ $batch->slaughterExecution->slaughter_time->format('d M Y') }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">{{ $batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $batch->species }}</td>
                                        <td class="px-4 py-3">{{ number_format($batch->quantity, 2) }} {{ $batch->quantity_unit }}</td>
                                        <td class="px-4 py-3">{{ $batch->animal_count > 0 ? $batch->animal_count : '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $batch->cold_chain_badge_class }}">
                                                {{ ucfirst(str_replace('_', ' ', $batch->cold_chain_status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $statusBadge }}">{{ ucfirst($batch->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($batch->postMortemInspection)
                                                <span class="text-green-600">✓</span>
                                            @else
                                                <span class="text-amber-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($batch->certificate)
                                                <span class="text-green-600">✓</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="batch-actions inline-flex gap-2">
                                                <a href="{{ route('batches.show', $batch) }}" class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                                <a href="{{ route('batches.edit', $batch) }}" class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="batch-detail-row bg-gray-50" id="batch-detail-{{ $batch->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3">
                                            @if ($batch->items->isNotEmpty())
                                                <p class="text-sm font-medium text-gray-700 mb-2">
                                                    {{ __('Animals in this batch (:count)', ['count' => $batch->items->count()]) }}
                                                    — {{ __('Total') }}: {{ number_format($batch->total_meat_quantity_kg, 2) }} kg
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
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($batch->items as $batchItem)
                                                            @php
                                                                $intake = $batchItem->intakeItem;
                                                                $yieldPct = ($intake->live_weight_kg && $intake->live_weight_kg > 0)
                                                                    ? round($batchItem->meat_quantity_kg / $intake->live_weight_kg * 100, 1)
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
                                                                    {{ number_format($batchItem->meat_quantity_kg, 2) }} kg
                                                                </td>
                                                                <td class="py-1 px-2">{{ $yieldPct !== null ? $yieldPct.'%' : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">
                                                    {{ __('No per-animal data — execution used aggregate counts.') }}
                                                    {{ __('Quantity') }}: {{ number_format($batch->quantity, 2) }} {{ $batch->quantity_unit }}.
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $batches->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.batch-row').forEach(function (row) {
                    row.addEventListener('click', function (e) {
                        if (e.target.closest('.batch-actions')) return;
                        var id = this.dataset.batchId;
                        var detail = document.getElementById('batch-detail-' + id);
                        if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
