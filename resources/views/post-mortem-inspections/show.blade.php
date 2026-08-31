<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Post-mortem') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $inspection->inspection_date?->format('d M Y') ?? __('No date') }}</p>
                    <p class="text-xs text-slate-500">{{ $inspection->batch->batch_code }} · {{ str($inspection->result ?? 'approved')->replace('_', ' ')->title() }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('post-mortem-inspections.edit', $inspection) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('batches.show', $inspection->batch) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View batch') }}</a>
                    <a href="{{ route('post-mortem-inspections.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Batch') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('batches.show', $inspection->batch) }}" class="text-bucha-primary hover:underline">{{ $inspection->batch->batch_code }}</a>
                        — {{ $inspection->batch->species }} ({{ $inspection->batch->quantity }})
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspection date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->inspection_date?->format('l, d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-bucha-primary hover:underline">{{ $inspection->inspector->full_name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Species') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->species ?: $inspection->batch->species }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Computed result') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ str($inspection->result ?? 'approved')->replace('_', ' ')->title() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Total examined meat') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $inspection->total_examined, 2) }} kg</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Carcass meat approved') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) ($meatTotals['approved_carcass_kg'] ?? $inspection->approved_quantity), 2) }} kg</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Other meat approved') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) ($meatTotals['approved_other_meat_kg'] ?? 0), 2) }} kg</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Condemned meat') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $inspection->condemned_quantity, 2) }} kg</dd>
                </div>
                @if ($inspection->notes)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap text-sm text-slate-900">{{ $inspection->notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        @if ($inspection->inspectionItems->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Individual animal outcomes') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500">
                                <th class="px-4 py-2 font-medium">{{ __('Ear tag') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Outcome') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Before PM (kg)') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('After PM (kg)') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Condemned organ') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Condemned (kg)') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($inspection->inspectionItems as $item)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs">{{ $item->intakeItem->ear_tag ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($item->outcome) }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ $item->batchItem ? number_format($item->batchItem->meat_quantity_kg, 2).' kg' : '—' }}</td>
                                    <td class="px-3 py-2 tabular-nums">
                                        @php $carcassKg = $item->displayCarcassWeightKg(); @endphp
                                        {{ $carcassKg !== null ? number_format($carcassKg, 2).' kg' : '—' }}
                                    </td>
                                    <td class="px-3 py-2">{{ $item->seized_part ?: '—' }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ $item->condemned_weight_kg ? number_format($item->condemned_weight_kg, 2).' kg' : '—' }}</td>
                                    <td class="px-3 py-2">{{ $item->reason ?: ($item->outcome_notes ?: '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @php $legacyObservations = $inspection->observations->whereNull('animal_intake_item_id'); @endphp

        @if ($inspection->inspectionItems->isEmpty())
            <div class="grid gap-5 md:grid-cols-2">
                <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Carcass inspection') }}</p>
                    </div>
                    <div class="px-4 py-4">
                        @php $carcass = $legacyObservations->where('category', 'carcass'); @endphp
                        @if ($carcass->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('No carcass observations recorded.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs text-slate-500">
                                            <th class="px-2 py-2 font-medium">{{ __('Item') }}</th>
                                            <th class="px-2 py-2 font-medium">{{ __('Status') }}</th>
                                            <th class="px-2 py-2 font-medium">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($carcass as $observation)
                                            <tr>
                                                <td class="px-2 py-2">{{ str($observation->item)->replace('_', ' ')->title() }}</td>
                                                <td class="px-2 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                                <td class="px-2 py-2">{{ $observation->notes ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Organ inspection') }}</p>
                    </div>
                    <div class="px-4 py-4">
                        @php $organs = $legacyObservations->where('category', 'organ'); @endphp
                        @if ($organs->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('No organ observations recorded.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs text-slate-500">
                                            <th class="px-2 py-2 font-medium">{{ __('Item') }}</th>
                                            <th class="px-2 py-2 font-medium">{{ __('Status') }}</th>
                                            <th class="px-2 py-2 font-medium">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($organs as $observation)
                                            <tr>
                                                <td class="px-2 py-2">{{ str($observation->item)->replace('_', ' ')->title() }}</td>
                                                <td class="px-2 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                                <td class="px-2 py-2">{{ $observation->notes ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Decision & comment') }}</p>
                </div>
                <div class="px-4 py-4">
                    @php
                        $decisionItems = $legacyObservations->filter(function ($observation) {
                            return $observation->category === 'decision' || in_array($observation->item, ['decision', 'comment'], true);
                        });
                    @endphp
                    @if ($decisionItems->isEmpty())
                        <p class="text-sm text-slate-500">{{ __('No decision/comment observations recorded.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500">
                                        <th class="px-2 py-2 font-medium">{{ __('Item') }}</th>
                                        <th class="px-2 py-2 font-medium">{{ __('Status') }}</th>
                                        <th class="px-2 py-2 font-medium">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($decisionItems as $observation)
                                        <tr>
                                            <td class="px-2 py-2">{{ str($observation->item)->replace('_', ' ')->title() }}</td>
                                            <td class="px-2 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                            <td class="px-2 py-2">{{ $observation->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
