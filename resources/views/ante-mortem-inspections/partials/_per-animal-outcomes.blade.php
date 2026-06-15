@php
    use App\Support\AnteMortemChecklist;

    $speciesName = $species ?? '';
    $checklistItems = AnteMortemChecklist::itemsForInspection($speciesName, true);
    $valueOptions = config('ante_mortem_checklist.value_options');
    $outcomeMap = collect($inspectionItems ?? [])->keyBy('animal_intake_item_id');
    $oldOutcomes = old('item_outcomes', []);
    $storedObservationsByAnimal = $observationsByAnimal ?? collect();
@endphp

<div class="space-y-4">
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
        @endphp
        <div class="overflow-hidden rounded-lg border border-slate-200 {{ $isObs ? 'ring-1 ring-yellow-200' : '' }}">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-sm font-medium text-slate-900">
                        {{ $item->ear_tag }}
                        @if (str_starts_with($item->ear_tag, 'LEGACY-'))
                            <span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
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
                <div class="w-full sm:w-40">
                    <input type="hidden"
                           name="item_outcomes[{{ $index }}][animal_intake_item_id]"
                           value="{{ $item->id }}">
                    <select name="item_outcomes[{{ $index }}][outcome]"
                            class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
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
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-full sm:flex-1">
                    <textarea name="item_outcomes[{{ $index }}][outcome_notes]"
                              rows="1"
                              placeholder="{{ __('Outcome notes (optional)') }}"
                              class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                    >{{ old("item_outcomes.{$index}.outcome_notes", $oldRow['outcome_notes'] ?? $existing?->outcome_notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="p-4">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Inspection checklist') }}</h4>
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Item') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Result') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-600">{{ __('Notes (optional)') }}</th>
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
    @endforeach
</div>
