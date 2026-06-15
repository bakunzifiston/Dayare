@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('rica.slaughterhouses.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← All slaughterhouses') }}</a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    {{ $facility->facility_name }}
                </h2>
                <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                    {{ __('Slaughterhouse') }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                {{ $facility->business->business_name ?? '—' }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('rica.slaughterhouses.show', $facility) }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="date_from" :value="__('Date from')" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from', $dateFrom->toDateString())" />
                    </div>
                    <div>
                        <x-input-label for="date_to" :value="__('Date to')" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to', $dateTo->toDateString())" />
                    </div>
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                    <p class="text-sm text-slate-500 ml-auto">
                        {{ $dateFrom->format('d M Y') }} – {{ $dateTo->format('d M Y') }}
                    </p>
                </form>
            </section>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-kpi-card stat glyph="intake" color="blue" :title="__('Animals slaughtered')" :value="$stats['animals_slaughtered']" />
                <x-kpi-card stat glyph="weight" color="slate" :title="__('Total meat yield')" :value="number_format($stats['total_meat_kg'], 2).' kg'" />
                <x-kpi-card stat glyph="alert" :color="$stats['condemned'] > 0 ? 'bucha' : 'slate'" :title="__('Animals condemned at PM')" :value="$stats['condemned']" />
                <x-kpi-card stat glyph="certificate" color="green" :title="__('Certificates issued')" :value="$stats['certificates']" />
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Species breakdown') }}</h3>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="pb-1 px-2">{{ __('Species') }}</th>
                                <th class="pb-1 px-2">{{ __('Animals slaughtered') }}</th>
                                <th class="pb-1 px-2">{{ __('Total meat (kg)') }}</th>
                                <th class="pb-1 px-2">{{ __('Avg yield (kg)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($speciesBreakdown as $row)
                                <tr class="border-t border-gray-100">
                                    <td class="py-1 px-2">{{ ucfirst((string) $row->species) }}</td>
                                    <td class="py-1 px-2 tabular-nums">{{ number_format((int) $row->count) }}</td>
                                    <td class="py-1 px-2 tabular-nums">{{ number_format((float) $row->total_kg, 2) }} kg</td>
                                    <td class="py-1 px-2 tabular-nums">
                                        {{ (int) $row->count > 0 ? number_format((float) $row->total_kg / (int) $row->count, 2).' kg' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 px-2 text-gray-400 text-center">
                                        {{ __('No slaughter data in this period.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($speciesBreakdown->isNotEmpty())
                            <tfoot>
                                <tr class="border-t border-gray-200 bg-gray-50">
                                    <td class="py-1 px-2 font-medium text-xs text-gray-600">{{ __('Total') }}</td>
                                    <td class="py-1 px-2 font-medium tabular-nums">{{ number_format($stats['animals_slaughtered']) }}</td>
                                    <td class="py-1 px-2 font-medium tabular-nums">{{ number_format($stats['total_meat_kg'], 2) }} kg</td>
                                    <td class="py-1 px-2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent slaughter executions') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3">{{ __('Animals') }}</th>
                                <th class="px-4 py-3">{{ __('Meat (kg)') }}</th>
                                <th class="px-4 py-3">{{ __('Batches') }}</th>
                                <th class="px-4 py-3">{{ __('PM result') }}</th>
                                <th class="px-4 py-3">{{ __('Certificate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentExecutions as $execution)
                                @php
                                    $pmResult = $execution->batches->first()?->postMortemInspection?->result;
                                    $pmBadge = match ($pmResult) {
                                        PostMortemInspection::RESULT_APPROVED => 'bg-green-100 text-green-800',
                                        PostMortemInspection::RESULT_PARTIAL => 'bg-amber-100 text-amber-800',
                                        PostMortemInspection::RESULT_REJECTED => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-500',
                                    };
                                    $hasCert = $execution->batches->contains(fn ($b) => $b->certificate !== null);
                                    $meatKg = (float) $execution->total_meat_quantity_kg;
                                @endphp
                                <tr class="rica-exec-row cursor-pointer hover:bg-slate-50/70 border-b border-slate-100" data-exec-id="{{ $execution->id }}">
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $execution->slaughter_time?->format('d M Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-slate-700">
                                        {{ number_format((int) $execution->actual_animals_slaughtered) }}
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-slate-700">
                                        {{ $meatKg > 0 ? number_format($meatKg, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-slate-700">
                                        {{ $execution->batches->count() }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($pmResult)
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $pmBadge }}">
                                                {{ ucfirst($pmResult) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">{{ __('None') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($hasCert)
                                            <span class="text-green-600 font-medium">✓</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr id="rica-exec-detail-{{ $execution->id }}" style="display:none;" class="bg-gray-50">
                                    <td colspan="100" class="px-4 py-3">
                                        @if ($execution->executionItems->isNotEmpty())
                                            <p class="text-sm font-medium text-gray-700 mb-2">
                                                {{ __('Individual animals (:count)', ['count' => $execution->executionItems->count()]) }}
                                            </p>
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="text-left text-xs text-gray-500">
                                                        <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                                        <th class="pb-1 px-2">{{ __('Species') }}</th>
                                                        <th class="pb-1 px-2">{{ __('Sex') }}</th>
                                                        <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                                                        <th class="pb-1 px-2">{{ __('Meat qty') }}</th>
                                                        <th class="pb-1 px-2">{{ __('PM outcome') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($execution->executionItems as $ei)
                                                        @php
                                                            $intake = $ei->intakeItem;
                                                            $pmItem = $ei->batchItems->first()?->postMortemOutcome;
                                                            $pmBadge = match ($pmItem?->outcome) {
                                                                'approved' => 'bg-green-100 text-green-800',
                                                                'condemned' => 'bg-red-100 text-red-800',
                                                                'deferred' => 'bg-yellow-100 text-yellow-800',
                                                                default => 'bg-gray-100 text-gray-500',
                                                            };
                                                        @endphp
                                                        <tr class="border-t border-gray-100">
                                                            <td class="py-1 px-2 font-mono text-xs">{{ $intake?->ear_tag ?? '—' }}</td>
                                                            <td class="py-1 px-2">{{ $intake ? ucfirst((string) $intake->species) : '—' }}</td>
                                                            <td class="py-1 px-2">{{ $intake ? ucfirst((string) $intake->sex) : '—' }}</td>
                                                            <td class="py-1 px-2">
                                                                {{ $intake?->live_weight_kg ? number_format((float) $intake->live_weight_kg, 2).' kg' : '—' }}
                                                            </td>
                                                            <td class="py-1 px-2 font-medium tabular-nums">
                                                                {{ number_format((float) $ei->meat_quantity_kg, 2) }} kg
                                                            </td>
                                                            <td class="py-1 px-2">
                                                                <span class="text-xs px-2 py-0.5 rounded-full {{ $pmBadge }}">
                                                                    {{ $pmItem ? ucfirst($pmItem->outcome) : __('Not recorded') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <p class="text-sm text-gray-500">
                                                {{ __('No individual animal data recorded for this execution.') }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ __('No slaughter executions recorded for this facility.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        'use strict';
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.rica-exec-row').forEach(function (row) {
                row.addEventListener('click', function () {
                    var id = this.dataset.execId;
                    var detail = document.getElementById('rica-exec-detail-' + id);
                    if (detail) {
                        detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    }
                });
            });
        });
    }());
    </script>
    @endpush
</x-app-layout>
