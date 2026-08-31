@php
    use App\Support\AnteMortemChecklist;

    $legacyObservations = $inspection->observations->whereNull('animal_intake_item_id');
    $observationsByAnimal = $inspection->observations
        ->whereNotNull('animal_intake_item_id')
        ->groupBy('animal_intake_item_id');
    $checklistItems = AnteMortemChecklist::itemsForInspection($inspection->species, $inspection->hasPerAnimalOutcomes());
@endphp

<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Ante-mortem') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $inspection->inspection_date->format('d M Y') }}</p>
                    <p class="text-xs text-slate-500">{{ $inspection->species }} · {{ $inspection->number_examined }} {{ __('examined') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('slaughter-plans.show', $inspection->slaughterPlan) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Plan') }}</a>
                    <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Slaughter session ID') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('slaughter-plans.show', $inspection->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                            #{{ $inspection->slaughter_plan_id }} — {{ $inspection->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $inspection->slaughterPlan->facility->facility_name }})
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspection date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->inspection_date->format('l, d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-bucha-primary hover:underline">{{ $inspection->inspector->full_name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Species') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->species }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Number examined') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $inspection->number_examined }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Number approved') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $inspection->number_approved }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Number rejected') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $inspection->number_rejected }}</dd>
                </div>
                @if ($inspection->notes)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap text-sm text-slate-900">{{ $inspection->notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">
                    {{ $inspection->hasPerAnimalOutcomes() ? __('Individual animal inspections') : __('Inspection checklist') }}
                </p>
            </div>
            <div class="px-4 py-4">
                @if ($inspection->hasPerAnimalOutcomes())
                    <div class="space-y-4">
                        @foreach ($inspection->inspectionItems as $inspItem)
                            @php
                                $animalObservations = $observationsByAnimal->get($inspItem->animal_intake_item_id, collect());
                                $outcomeClass = match ($inspItem->outcome) {
                                    'approved' => 'bg-emerald-50 text-emerald-800',
                                    'rejected' => 'bg-red-50 text-red-800',
                                    default => 'bg-amber-50 text-amber-900',
                                };
                            @endphp
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-mono text-sm font-medium text-slate-900">
                                            {{ $inspItem->intakeItem->ear_tag }}
                                            @if (str_starts_with($inspItem->intakeItem->ear_tag, 'LEGACY-'))
                                                <span class="ml-1 rounded bg-slate-100 px-1 text-xs font-normal text-slate-400">[legacy]</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $inspItem->intakeItem->species }}
                                            <span class="mx-1">·</span>
                                            {{ ucfirst($inspItem->intakeItem->sex) }}
                                        </p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $outcomeClass }}">
                                        {{ ucfirst($inspItem->outcome) }}
                                    </span>
                                </div>
                                @if ($inspItem->conditions || $inspItem->action_taken)
                                    <div class="space-y-1 border-b border-slate-100 px-4 py-2 text-sm text-slate-600">
                                        @if ($inspItem->conditions)
                                            <p><span class="font-medium text-slate-500">{{ __('Condition(s)') }}:</span> {{ $inspItem->conditions }}</p>
                                        @endif
                                        @if ($inspItem->action_taken)
                                            <p><span class="font-medium text-slate-500">{{ __('Action taken') }}:</span> {{ $inspItem->action_taken }}</p>
                                        @endif
                                    </div>
                                @elseif ($inspItem->outcome_notes)
                                    <div class="border-b border-slate-100 px-4 py-2 text-sm text-slate-600">
                                        {{ $inspItem->outcome_notes }}
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h4 class="mb-2 text-xs font-semibold text-slate-500">{{ __('Inspection checklist') }}</h4>
                                    @if ($animalObservations->isEmpty())
                                        <p class="text-sm text-slate-500">{{ __('No checklist observations recorded for this animal.') }}</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead>
                                                    <tr class="text-left text-xs text-slate-500">
                                                        <th class="px-3 py-2 font-medium">{{ __('Item') }}</th>
                                                        <th class="px-3 py-2 font-medium">{{ __('Result') }}</th>
                                                        <th class="px-3 py-2 font-medium">{{ __('Notes') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($animalObservations as $observation)
                                                        <tr>
                                                            <td class="px-3 py-2">{{ $checklistItems[$observation->item]['label'] ?? str($observation->item)->replace('_', ' ')->title() }}</td>
                                                            <td class="px-3 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                                            <td class="px-3 py-2">{{ $observation->notes ?: '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if ($legacyObservations->isEmpty())
                        <p class="text-sm text-slate-500">{{ __('No checklist observations recorded.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500">
                                        <th class="px-3 py-2 font-medium">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ __('Result') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($legacyObservations as $observation)
                                        <tr>
                                            <td class="px-3 py-2">{{ $checklistItems[$observation->item]['label'] ?? str($observation->item)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ $observation->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                @if ($inspection->notes_for_under_observation)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p class="mb-1 text-xs font-medium text-amber-800">{{ __('Notes for under-observation animals') }}</p>
                        <p class="text-sm text-amber-900">{{ $inspection->notes_for_under_observation }}</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
