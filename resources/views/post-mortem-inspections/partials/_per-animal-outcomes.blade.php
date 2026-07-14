@php
    use App\Support\PostMortemChecklist;

    $speciesName = $species ?? '';
    $checklistItems = PostMortemChecklist::itemsForInspection($speciesName, true);
    $organOptions = PostMortemChecklist::organOptionsForSpecies($speciesName);
    $valueOptions = config('post_mortem_checklist.value_options');
    $outcomeMap = collect($inspectionItems ?? [])->keyBy('animal_intake_item_id');
    $oldOutcomes = old('item_outcomes', []);
    $existingOutcomes = $existingInspectionOutcomes ?? [];
@endphp

<div class="space-y-4">
    @foreach ($animals as $animal)
        @php
            $index = $loop->index;
            $animalId = (int) $animal['animal_intake_item_id'];
            $existing = $outcomeMap->get($animalId);
            $existingData = $existingOutcomes[$animalId] ?? null;
            $oldRow = is_array($oldOutcomes[$index] ?? null)
                ? $oldOutcomes[$index]
                : (collect($oldOutcomes)->firstWhere('animal_intake_item_id', $animalId) ?? []);
            $animalObs = $oldRow['observations'] ?? ($existingData['observations'] ?? []);
            $sourceLabel = ($animal['source'] ?? 'batch') === 'execution'
                ? __('From slaughter execution')
                : __('In batch');
            $beforePmKg = (float) ($animal['meat_quantity_kg'] ?? 0);
            $carcassDefault = $oldRow['carcass_weight_kg'] ?? $existing?->carcass_weight_kg ?? $existingData['carcass_weight_kg'] ?? '';
            $condemnedWeightDefault = $oldRow['condemned_weight_kg'] ?? $existing?->condemned_weight_kg ?? $existingData['condemned_weight_kg'] ?? '';
            $selectedOutcome = isset($oldRow['outcome'])
                ? $oldRow['outcome']
                : ($existing?->outcome ?? ($existingData['outcome'] ?? ''));
            $isCondemned = $selectedOutcome === 'condemned';
            $seizedPart = old(
                "item_outcomes.{$index}.seized_part",
                $oldRow['seized_part'] ?? $existing?->seized_part ?? $existingData['seized_part'] ?? '',
            );
            $reason = old(
                "item_outcomes.{$index}.reason",
                $oldRow['reason'] ?? $existing?->reason ?? $existingData['reason'] ?? '',
            );
        @endphp
        <div class="overflow-hidden rounded-lg border border-slate-200" data-pm-animal-card data-meat-kg="{{ $beforePmKg }}">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-sm font-medium text-slate-900">
                        {{ $animal['ear_tag'] }}
                        @if (str_starts_with($animal['ear_tag'], 'LEGACY-'))
                            <span class="ml-1 text-xs font-normal text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ $animal['species'] }} · {{ $animal['sex'] }}
                        <span class="mx-1">·</span>
                        {{ $animal['session_label'] }}
                        <span class="mx-1">·</span>
                        <span class="text-slate-600">{{ $sourceLabel }}</span>
                    </p>
                </div>
                @if (! empty($animal['batch_item_id']))
                    <input type="hidden" name="item_outcomes[{{ $index }}][batch_item_id]" value="{{ $animal['batch_item_id'] }}">
                @endif
                <input type="hidden" name="item_outcomes[{{ $index }}][animal_intake_item_id]" value="{{ $animalId }}">
                <div class="text-right">
                    <p class="text-sm font-medium tabular-nums text-slate-900">{{ number_format($beforePmKg, 2) }} kg</p>
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('Before PM') }}</p>
                </div>
                <div @class([
                    'w-full sm:w-28 pm-approved-weight-field',
                    'hidden' => $isCondemned,
                ])>
                    <label class="mb-1 block text-xs font-medium text-slate-600">{{ __('After PM (kg)') }}</label>
                    <input type="number"
                           name="item_outcomes[{{ $index }}][carcass_weight_kg]"
                           value="{{ old("item_outcomes.{$index}.carcass_weight_kg", $carcassDefault) }}"
                           min="0.1" max="9999" step="0.01"
                           placeholder="kg"
                           class="pm-carcass-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                </div>
            </div>

            <div class="p-4">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Post-mortem checklist') }}</h4>
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

                            <tr class="bg-slate-50/80">
                                <td class="px-3 py-2 font-medium text-slate-800">{{ __('Decision') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    <select name="item_outcomes[{{ $index }}][outcome]"
                                            class="pm-animal-outcome block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                            required>
                                        <option value="">{{ __('Select decision') }}</option>
                                        @foreach (['approved', 'condemned', 'deferred'] as $outcomeOption)
                                            <option value="{{ $outcomeOption }}" @selected($selectedOutcome === $outcomeOption)>
                                                {{ ucfirst($outcomeOption) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $isCondemned]) data-pm-condemnation-row>
                                <td class="px-3 py-2 font-medium text-amber-900">{{ __('Condemned organ') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    <select name="item_outcomes[{{ $index }}][seized_part]"
                                            class="pm-condemned-organ block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                        <option value="">{{ __('Select organ') }}</option>
                                        @foreach ($organOptions as $organOption)
                                            <option value="{{ $organOption }}" @selected($seizedPart === $organOption)>{{ $organOption }}</option>
                                        @endforeach
                                        @if ($seizedPart !== '' && ! in_array($seizedPart, $organOptions, true))
                                            <option value="{{ $seizedPart }}" selected>{{ $seizedPart }}</option>
                                        @endif
                                    </select>
                                    @error("item_outcomes.{$index}.seized_part")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $isCondemned]) data-pm-condemnation-row>
                                <td class="px-3 py-2 font-medium text-amber-900">{{ __('Condemned weight (kg)') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    <input type="number"
                                           name="item_outcomes[{{ $index }}][condemned_weight_kg]"
                                           value="{{ old("item_outcomes.{$index}.condemned_weight_kg", $condemnedWeightDefault) }}"
                                           min="0.1" max="9999" step="0.01"
                                           placeholder="kg"
                                           class="pm-condemned-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                    @error("item_outcomes.{$index}.condemned_weight_kg")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $isCondemned]) data-pm-condemnation-row>
                                <td class="px-3 py-2 font-medium text-amber-900">{{ __('Reason for condemnation') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    <input type="text"
                                           name="item_outcomes[{{ $index }}][reason]"
                                           value="{{ $reason }}"
                                           placeholder="{{ __('e.g. Cysts, abscess') }}"
                                           class="block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                    @error("item_outcomes.{$index}.reason")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
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
