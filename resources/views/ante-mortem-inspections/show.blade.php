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
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ante-mortem inspection') }} — {{ $inspection->inspection_date->format('d M Y') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Back to list') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter session ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('slaughter-plans.show', $inspection->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                                #{{ $inspection->slaughter_plan_id }} — {{ $inspection->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $inspection->slaughterPlan->facility->facility_name }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspection date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->inspection_date->format('l, d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $inspection->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number examined') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_examined }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number approved') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_approved }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number rejected') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_rejected }}</dd>
                    </div>
                    @if ($inspection->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Notes') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $inspection->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($inspection->hasPerAnimalOutcomes())
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Individual animal inspections') }}</h3>
                    <div class="space-y-4">
                        @foreach ($inspection->inspectionItems as $inspItem)
                            @php
                                $animalObservations = $observationsByAnimal->get($inspItem->animal_intake_item_id, collect());
                                $outcomeClass = match ($inspItem->outcome) {
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-yellow-100 text-yellow-800',
                                };
                            @endphp
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-mono text-sm font-medium text-slate-900">
                                            {{ $inspItem->intakeItem->ear_tag }}
                                            @if (str_starts_with($inspItem->intakeItem->ear_tag, 'LEGACY-'))
                                                <span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $inspItem->intakeItem->species }}
                                            <span class="mx-1">·</span>
                                            {{ ucfirst($inspItem->intakeItem->sex) }}
                                        </p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $outcomeClass }}">
                                        {{ ucfirst($inspItem->outcome) }}
                                    </span>
                                </div>
                                @if ($inspItem->outcome_notes)
                                    <div class="border-b border-slate-100 px-4 py-2 text-sm text-slate-600">
                                        {{ $inspItem->outcome_notes }}
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Inspection checklist') }}</h4>
                                    @if ($animalObservations->isEmpty())
                                        <p class="text-sm text-gray-500">{{ __('No checklist observations recorded for this animal.') }}</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Item') }}</th>
                                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Result') }}</th>
                                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Notes') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white">
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Inspection checklist') }}</h3>
                    @if ($legacyObservations->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('No checklist observations recorded.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Result') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
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
                    <div class="mt-4 rounded bg-yellow-50 border border-yellow-200 p-3">
                        <p class="text-xs font-medium text-yellow-700 mb-1">
                            {{ __('Notes for under-observation animals') }}
                        </p>
                        <p class="text-sm text-yellow-800">
                            {{ $inspection->notes_for_under_observation }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
