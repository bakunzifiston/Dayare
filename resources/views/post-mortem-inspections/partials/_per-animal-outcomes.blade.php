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
            $showCondemnation = in_array($selectedOutcome, ['approved', 'condemned'], true);
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

                            <tr @class(['pm-approved-weight-field bg-slate-50/80', 'hidden' => $isCondemned])>
                                <td class="px-3 py-2 font-medium text-slate-800">{{ __('After PM (kg)') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    <input type="number"
                                           name="item_outcomes[{{ $index }}][carcass_weight_kg]"
                                           value="{{ old("item_outcomes.{$index}.carcass_weight_kg", $carcassDefault) }}"
                                           min="0.1" max="9999" step="0.01"
                                           placeholder="kg"
                                           class="pm-carcass-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $showCondemnation]) data-pm-condemnation-row>
                                <td class="px-3 py-2 font-medium text-amber-900" colspan="3" data-pm-condemnation-heading>
                                    @if ($isCondemned)
                                        {{ __('Condemnation details') }}
                                    @else
                                        {{ __('Partial condemnation (optional with approved)') }}
                                    @endif
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $showCondemnation]) data-pm-condemnation-row>
                                <td class="px-3 py-2 align-top font-medium text-amber-900">{{ __('Condemned organs') }}</td>
                                <td class="px-3 py-2" colspan="2">
                                    @php
                                        $organRows = old(
                                            "item_outcomes.{$index}.condemned_organs",
                                            $oldRow['condemned_organs']
                                                ?? $existingData['condemned_organs']
                                                ?? ($existing?->condemnedOrganEntries() ?? []),
                                        );
                                        if (! is_array($organRows) || $organRows === []) {
                                            if ($seizedPart !== '' || ($condemnedWeightDefault !== '' && $condemnedWeightDefault !== null)) {
                                                $organRows = [[
                                                    'organ_name' => $seizedPart,
                                                    'weight_kg' => $condemnedWeightDefault,
                                                ]];
                                            } else {
                                                $organRows = [['organ_name' => '', 'weight_kg' => '']];
                                            }
                                        }
                                    @endphp
                                    <div class="pm-condemned-organs space-y-2" data-pm-condemned-organs data-animal-index="{{ $index }}">
                                        @foreach ($organRows as $organIndex => $organRow)
                                            <div class="pm-condemned-organ-row flex flex-wrap items-end gap-2" data-pm-organ-row>
                                                <div class="min-w-[10rem] flex-1">
                                                    <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-amber-800/80">{{ __('Organ name') }}</label>
                                                    <select name="item_outcomes[{{ $index }}][condemned_organs][{{ $organIndex }}][organ_name]"
                                                            class="pm-condemned-organ block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                                        <option value="">{{ __('Select organ') }}</option>
                                                        @foreach ($organOptions as $organOption)
                                                            <option value="{{ $organOption }}" @selected(($organRow['organ_name'] ?? '') === $organOption)>{{ $organOption }}</option>
                                                        @endforeach
                                                        @if (($organRow['organ_name'] ?? '') !== '' && ! in_array($organRow['organ_name'], $organOptions, true))
                                                            <option value="{{ $organRow['organ_name'] }}" selected>{{ $organRow['organ_name'] }}</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="w-32">
                                                    <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-amber-800/80">{{ __('Quantity (kg)') }}</label>
                                                    <input type="number"
                                                           name="item_outcomes[{{ $index }}][condemned_organs][{{ $organIndex }}][weight_kg]"
                                                           value="{{ $organRow['weight_kg'] ?? '' }}"
                                                           min="0.1" max="9999" step="0.01"
                                                           placeholder="kg"
                                                           class="pm-condemned-weight block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                                </div>
                                                <button type="button"
                                                        class="pm-remove-organ inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-2.5 text-xs font-medium text-red-700 hover:bg-red-50"
                                                        data-pm-remove-organ>
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button"
                                            class="pm-add-organ mt-2 inline-flex h-8 items-center rounded-md border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-900 hover:bg-amber-50"
                                            data-pm-add-organ>
                                        {{ __('Add organ') }}
                                    </button>
                                    @error("item_outcomes.{$index}.condemned_organs")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            <tr @class(['pm-condemnation-row bg-amber-50/70', 'hidden' => ! $showCondemnation]) data-pm-condemnation-row>
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
