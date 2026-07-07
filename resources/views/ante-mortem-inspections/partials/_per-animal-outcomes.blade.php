@php
    use App\Support\AnteMortemChecklist;

    $speciesName = $species ?? '';
    $checklistItems = AnteMortemChecklist::itemsForInspection($speciesName, true);
    $valueOptions = config('ante_mortem_checklist.value_options');
    $outcomeMap = collect($inspectionItems ?? [])->keyBy('animal_intake_item_id');
    $oldOutcomes = old('item_outcomes', []);
    $storedObservationsByAnimal = $observationsByAnimal ?? collect();
    $animalCount = $assignedItems->count();
    $expandAllByDefault = $animalCount <= 3;
@endphp

<div class="max-h-[min(70vh,48rem)] space-y-3 overflow-y-auto pr-1" data-am-animal-list>
    @foreach ($assignedItems as $item)
        @php
            $index = $loop->index;
            $existing = $outcomeMap->get($item->id);
            $oldRow = $oldOutcomes[$index] ?? [];
            $isObs = $item->health_status === \App\Models\AnimalIntakeItem::HEALTH_OBSERVATION;
            $badgeClass = match ($item->health_status) {
                \App\Models\AnimalIntakeItem::HEALTH_HEALTHY => 'bg-green-100 text-green-800',
                \App\Models\AnimalIntakeItem::HEALTH_OBSERVATION => 'bg-yellow-100 text-yellow-800',
                default => 'bg-red-100 text-red-800',
            };
            $animalObs = $oldRow['observations'] ?? [];
            if ($animalObs === [] && $storedObservationsByAnimal->has($item->id)) {
                $animalObs = $storedObservationsByAnimal->get($item->id)
                    ->mapWithKeys(fn ($obs) => [$obs->item => ['value' => $obs->value, 'notes' => $obs->notes]])
                    ->all();
            }
            $selectedOutcome = isset($oldRow['outcome'])
                ? $oldRow['outcome']
                : ($existing?->outcome ?? (! $isObs ? 'approved' : ''));
            $conditions = old(
                "item_outcomes.{$index}.conditions",
                $oldRow['conditions'] ?? $existing?->conditions ?? '',
            );
            $actionTaken = old(
                "item_outcomes.{$index}.action_taken",
                $oldRow['action_taken'] ?? $existing?->action_taken ?? '',
            );
            $isUnhealthyOutcome = in_array($selectedOutcome, ['rejected', 'deferred'], true);
            $defaultOpen = $expandAllByDefault || $isObs;
        @endphp
        <details
            class="am-animal-card overflow-hidden rounded-lg border border-slate-200 bg-white {{ $isObs ? 'ring-1 ring-yellow-200' : '' }}"
            data-am-animal-card
            @if ($defaultOpen) open @endif
        >
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700">
                    {{ $loop->iteration }}
                </span>
                <div class="min-w-[10rem] flex-1">
                    <p class="font-mono text-sm font-medium text-slate-900">
                        {{ $item->ear_tag }}
                        @if (str_starts_with($item->ear_tag, 'LEGACY-'))
                            <span class="ml-1 rounded bg-gray-100 px-1 text-xs font-normal text-gray-400">[legacy]</span>
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ ucfirst($item->sex) }}
                        <span class="mx-1">·</span>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $badgeClass }}">
                            {{ $item->health_status_label }}
                        </span>
                    </p>
                </div>
                <div class="w-full sm:w-44" onclick="event.stopPropagation()">
                    <input type="hidden"
                           name="item_outcomes[{{ $index }}][animal_intake_item_id]"
                           value="{{ $item->id }}">
                    <label class="mb-1 block text-xs font-medium text-slate-500 sm:sr-only">{{ __('Outcome') }}</label>
                    <select name="item_outcomes[{{ $index }}][outcome]"
                            class="am-animal-outcome block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        @foreach (['approved', 'rejected', 'deferred'] as $outcomeOption)
                            <option value="{{ $outcomeOption }}"
                                @selected(
                                    isset($oldRow['outcome'])
                                        ? $oldRow['outcome'] === $outcomeOption
                                        : ($existing
                                            ? $existing->outcome === $outcomeOption
                                            : (! $isObs && $outcomeOption === 'approved'))
                                )>
                                {{ ucfirst($outcomeOption) }}
                            </option>
                        @endforeach
                    </select>
                    @error("item_outcomes.{$index}.outcome")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <span class="ml-auto hidden text-slate-400 sm:inline" aria-hidden="true">▾</span>
            </summary>

            <div class="space-y-4 p-4">
                <div @class([
                    'am-unhealthy-fields rounded-lg border border-amber-200 bg-amber-50/60 p-4',
                    'hidden' => ! $isUnhealthyOutcome,
                ]) data-am-unhealthy-fields>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('Unhealthy animal details (RICA report)') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">{{ __('Condition(s)') }}</label>
                            <input type="text"
                                   name="item_outcomes[{{ $index }}][conditions]"
                                   value="{{ $conditions }}"
                                   placeholder="{{ __('e.g. Lameness, fever') }}"
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            @error("item_outcomes.{$index}.conditions")
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">{{ __('Action taken') }}</label>
                            <input type="text"
                                   name="item_outcomes[{{ $index }}][action_taken]"
                                   value="{{ $actionTaken }}"
                                   placeholder="{{ __('e.g. Deferred, sent back') }}"
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            @error("item_outcomes.{$index}.action_taken")
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('Required for rejected/deferred animals unless abnormal findings are recorded in the checklist below.') }}</p>
                </div>

                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Inspection checklist') }}</h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600 min-w-[10rem]">{{ __('Result') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600 min-w-[12rem]">{{ __('Notes (optional)') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($checklistItems as $itemKey => $meta)
                                    @php
                                        $obsValue = $animalObs[$itemKey]['value'] ?? '';
                                        $obsNotes = $animalObs[$itemKey]['notes'] ?? '';
                                        $options = $valueOptions[$meta['type']] ?? [];
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-slate-700">{{ $meta['label'] }}</td>
                                        <td class="px-3 py-2">
                                            @if ($meta['type'] === 'free_text')
                                                <input type="text"
                                                       name="item_outcomes[{{ $index }}][observations][{{ $itemKey }}][value]"
                                                       value="{{ $obsValue }}"
                                                       class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                                       required />
                                            @else
                                                <select name="item_outcomes[{{ $index }}][observations][{{ $itemKey }}][value]"
                                                        class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                                        required>
                                                    <option value="">{{ __('Select') }}</option>
                                                    @foreach ($options as $option)
                                                        <option value="{{ $option }}" @selected($obsValue === $option)>
                                                            {{ ucfirst($option) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   name="item_outcomes[{{ $index }}][observations][{{ $itemKey }}][notes]"
                                                   value="{{ $obsNotes }}"
                                                   maxlength="5000"
                                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error("item_outcomes.{$index}.observations")
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </details>
    @endforeach
</div>
