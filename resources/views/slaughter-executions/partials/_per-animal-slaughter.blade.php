@php
    $executionMap = collect($executionItems ?? [])->keyBy('animal_intake_item_id');
    $slaughteredElsewhereIds = collect($slaughteredItemIds ?? [])->map(fn ($id) => (int) $id);
    $slaughteredElsewhereDetails = collect($slaughteredDetails ?? [])->keyBy('animal_intake_item_id');
    $currentExecutionIds = collect($currentExecutionItemIds ?? [])->map(fn ($id) => (int) $id);
    $oldSlaughters = collect(old('item_slaughters', []))->keyBy('animal_intake_item_id');

    $isSlaughteredElsewhere = function (int $itemId) use ($slaughteredElsewhereIds, $currentExecutionIds, $executionMap): bool {
        return $slaughteredElsewhereIds->contains($itemId)
            && ! $currentExecutionIds->contains($itemId)
            && ! $executionMap->has($itemId);
    };

    $pendingItems = $approvedItems->reject(fn ($ai) => $isSlaughteredElsewhere($ai->intakeItem->id));
    $approvedCount = $approvedItems->count();
    $remainingCount = $pendingItems->count();
    $slaughteredCount = $approvedCount - $remainingCount;
    $pendingIndex = 0;
@endphp

<div class="mb-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
    <span class="font-semibold">{{ $slaughteredCount }}</span> / {{ $approvedCount }}
    {{ __('slaughtered on this session') }}
    <span class="mx-1">·</span>
    <span class="font-semibold">{{ $remainingCount }}</span>
    {{ __('remaining') }}
</div>

@if ($slaughteredCount > 0)
    <div class="mb-6">
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-800">
            {{ __('Already slaughtered') }} ({{ $slaughteredCount }})
        </h4>
        <div class="overflow-hidden rounded-lg border border-green-200">
            <table class="min-w-full divide-y divide-green-100 text-sm">
                <thead class="bg-green-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-green-900">{{ __('Ear tag') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-green-900">{{ __('Animal') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-green-900">{{ __('Meat (kg)') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-green-900">{{ __('Slaughter time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-50 bg-white">
                    @foreach ($approvedItems as $ai)
                        @php
                            $item = $ai->intakeItem;
                            $detail = $slaughteredElsewhereDetails->get($item->id);
                            $alreadyDone = $isSlaughteredElsewhere($item->id);
                        @endphp
                        @if (! $alreadyDone)
                            @continue
                        @endif
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs">{{ $detail['ear_tag'] ?? $item->ear_tag }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $detail['species'] ?? $item->species }} · {{ $detail['sex'] ?? ucfirst($item->sex) }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ isset($detail['meat_quantity_kg']) ? number_format((float) $detail['meat_quantity_kg'], 2) : '—' }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $detail['slaughter_time'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($remainingCount > 0)
    <div>
        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-600">
            {{ __('Remaining animals') }} ({{ $remainingCount }})
        </h4>
        <div class="space-y-4">
            @foreach ($pendingItems as $ai)
                @php
                    $item = $ai->intakeItem;
                    $existing = $executionMap->get($item->id);
                    $oldRow = $oldSlaughters->get($item->id);
                    $isChecked = $oldRow !== null || $existing !== null;
                    $defaultMeat = $oldRow['meat_quantity_kg'] ?? $existing?->meat_quantity_kg ?? (
                        $item->live_weight_kg ? round((float) $item->live_weight_kg * 0.5, 2) : ''
                    );
                @endphp
                <div class="overflow-hidden rounded-lg border border-slate-200 slaughter-animal-card" data-animal-id="{{ $item->id }}">
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox"
                                   class="slaughter-animal-checkbox rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary"
                                   @checked($isChecked)>
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Slaughter now') }}</span>
                        </label>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-medium text-slate-900">{{ $item->ear_tag }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $item->species }} · {{ ucfirst($item->sex) }}
                                @if ($item->live_weight_kg)
                                    · {{ number_format($item->live_weight_kg, 2) }} kg {{ __('live') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="slaughter-animal-fields grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 {{ $isChecked ? '' : 'hidden' }}">
                        <input type="hidden"
                               class="slaughter-animal-id"
                               name="item_slaughters[{{ $pendingIndex }}][animal_intake_item_id]"
                               value="{{ $item->id }}"
                               @disabled(! $isChecked)>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('Meat quantity (kg)') }}</label>
                            <input type="number"
                                   name="item_slaughters[{{ $pendingIndex }}][meat_quantity_kg]"
                                   value="{{ $isChecked ? $defaultMeat : '' }}"
                                   min="0.1" max="9999" step="0.01"
                                   class="slaughter-meat-qty mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                   placeholder="kg"
                                   @disabled(! $isChecked)>
                            @error("item_slaughters.{$pendingIndex}.meat_quantity_kg")
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('Notes (optional)') }}</label>
                            <input type="text"
                                   name="item_slaughters[{{ $pendingIndex }}][notes]"
                                   value="{{ $isChecked ? ($oldRow['notes'] ?? $existing?->notes ?? '') : '' }}"
                                   class="slaughter-notes mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                   @disabled(! $isChecked)>
                        </div>
                    </div>
                </div>
                @php $pendingIndex++; @endphp
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-500">
            {{ __('Check the animal(s) you are slaughtering now, enter dressed weight, and save. Return later to record the next animal(s).') }}
        </p>
    </div>
@else
    <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
        {{ __('All approved animals on this session have been slaughtered.') }}
    </div>
@endif
