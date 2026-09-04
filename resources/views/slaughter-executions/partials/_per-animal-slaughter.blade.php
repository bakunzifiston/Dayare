@php
    $executionMap = collect($executionItems ?? [])->keyBy('animal_intake_item_id');
    $recordedIds = collect($slaughteredItemIds ?? [])->map(fn ($id) => (int) $id);
    $recordedDetails = collect($slaughteredDetails ?? [])->keyBy('animal_intake_item_id');
    $oldSlaughters = collect(old('item_slaughters', []))->keyBy('animal_intake_item_id');

    $isRecorded = fn (int $itemId): bool => $recordedIds->contains($itemId);

    $recordedItems = $approvedItems->filter(fn ($ai) => $isRecorded($ai->intakeItem->id));
    $pendingItems = $approvedItems->reject(fn ($ai) => $isRecorded($ai->intakeItem->id));
    $approvedCount = $approvedItems->count();
    $recordedCount = $recordedItems->count();
    $pendingCount = $pendingItems->count();
    $recordedIndex = 0;
    $pendingIndex = 0;
@endphp

<div class="mb-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
    <span id="slaughter-session-recorded-count" class="font-semibold">{{ $recordedCount }}</span> / <span id="slaughter-session-approved-count">{{ $approvedCount }}</span>
    {{ __('slaughtered on this session') }}
    <span class="mx-1">·</span>
    <span id="slaughter-session-pending-count" class="font-semibold">{{ $pendingCount }}</span>
    {{ __('remaining to record') }}
</div>

@if ($recordedCount > 0)
    <div class="mb-6">
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-800">
            {{ __('Already slaughtered') }} ({{ $recordedCount }})
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
                    @foreach ($recordedItems as $ai)
                        @php
                            $item = $ai->intakeItem;
                            $detail = $recordedDetails->get($item->id, []);
                            $executionItem = $executionMap->get($item->id);
                            $oldRow = $oldSlaughters->get($item->id);
                            $meatQty = $oldRow['meat_quantity_kg'] ?? $executionItem?->meat_quantity_kg ?? ($detail['meat_quantity_kg'] ?? '');
                            $notes = $oldRow['notes'] ?? $executionItem?->notes ?? '';
                        @endphp
                        <tr class="slaughter-recorded-row" data-animal-id="{{ $item->id }}">
                            <td class="px-3 py-2 font-mono text-xs">
                                <span class="inline-flex items-center gap-2">
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-800">{{ __('Done') }}</span>
                                    {{ $detail['ear_tag'] ?? $item->ear_tag }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ $detail['species'] ?? $item->species }} · {{ $detail['sex'] ?? ucfirst($item->sex) }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $meatQty !== '' ? number_format((float) $meatQty, 2) : '—' }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $detail['slaughter_time'] ?? '—' }}</td>
                        </tr>
                        <tr class="hidden slaughter-recorded-inputs" aria-hidden="true">
                            <td colspan="4">
                                <input type="hidden" data-field="animal_intake_item_id" name="item_slaughters[{{ $recordedIndex }}][animal_intake_item_id]" value="{{ $item->id }}">
                                <input type="hidden" data-field="meat_quantity_kg" name="item_slaughters[{{ $recordedIndex }}][meat_quantity_kg]" value="{{ $meatQty }}">
                                <input type="hidden" data-field="notes" name="item_slaughters[{{ $recordedIndex }}][notes]" value="{{ $notes }}">
                            </td>
                        </tr>
                        @php $recordedIndex++; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($pendingCount > 0)
    <div>
        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-600">
            {{ __('Add slaughter now') }} ({{ $pendingCount }})
        </h4>
        <div class="space-y-4">
            @foreach ($pendingItems as $ai)
                @php
                    $item = $ai->intakeItem;
                    $oldRow = $oldSlaughters->get($item->id);
                    $isChecked = $oldRow !== null || ($pendingCount === 1 && $oldSlaughters->isEmpty());
                    $defaultMeat = $oldRow['meat_quantity_kg'] ?? (
                        $item->live_weight_kg ? round((float) $item->live_weight_kg * 0.5, 2) : ''
                    );
                @endphp
                <div class="overflow-hidden rounded-lg border border-slate-200 slaughter-animal-card slaughter-animal-card--pending" data-animal-id="{{ $item->id }}">
                    <label class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer">
                        <span class="inline-flex items-center gap-2 shrink-0">
                            <input type="checkbox"
                                   class="slaughter-animal-checkbox rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary"
                                   @checked($isChecked)>
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Slaughter now') }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-medium text-slate-900">{{ $item->ear_tag }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $item->species }} · {{ ucfirst($item->sex) }}
                                @if ($item->live_weight_kg)
                                    · {{ number_format($item->live_weight_kg, 2) }} kg {{ __('live') }}
                                @endif
                            </p>
                        </div>
                    </label>
                    <div class="slaughter-animal-fields grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 {{ $isChecked ? '' : 'hidden' }}">
                        <input type="hidden"
                               class="slaughter-animal-id"
                               value="{{ $item->id }}"
                               @disabled(! $isChecked)>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('Meat quantity (kg)') }}</label>
                            <input type="number"
                                   class="slaughter-meat-qty mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                   value="{{ $isChecked ? $defaultMeat : '' }}"
                                   min="0.1" max="9999" step="0.01"
                                   placeholder="kg"
                                   @disabled(! $isChecked)>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('Notes (optional)') }}</label>
                            <input type="text"
                                   class="slaughter-notes mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary"
                                   value="{{ $isChecked ? ($oldRow['notes'] ?? '') : '' }}"
                                   @disabled(! $isChecked)>
                        </div>
                    </div>
                </div>
                @php $pendingIndex++; @endphp
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-500">
            {{ __('Check the animal(s) you are slaughtering now, enter dressed weight, and save. Animals marked Done above are already recorded.') }}
        </p>
    </div>
@elseif ($recordedCount > 0)
    <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
        {{ __('All approved animals on this session have been slaughtered.') }}
    </div>
@endif
