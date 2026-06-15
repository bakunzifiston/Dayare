@php
    $alreadyBatchedLookup = array_fill_keys($alreadyBatchedIds ?? [], true);
@endphp

<div class="border border-gray-200 rounded-md">
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
        <p class="text-sm font-medium text-gray-700">{{ __('Select animals for this batch') }}</p>
        <div class="flex gap-3 text-xs">
            <button type="button" id="select-all-animals" class="text-blue-600 hover:underline">{{ __('Select all') }}</button>
            <button type="button" id="deselect-all-animals" class="text-gray-500 hover:underline">{{ __('Deselect all') }}</button>
        </div>
    </div>
    <div class="p-3">
        <div id="animal-selection-summary" class="mb-3 text-sm text-gray-600">
            <span id="selected-animal-count">0</span> {{ __('animals selected') }}
            — {{ __('estimated yield') }}: <strong><span id="selected-yield">0.00</span> kg</strong>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500">
                    <th class="pb-1 px-2 w-8"></th>
                    <th class="pb-1 px-2">{{ __('Session') }}</th>
                    <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                    <th class="pb-1 px-2">{{ __('Species') }}</th>
                    <th class="pb-1 px-2">{{ __('Sex') }}</th>
                    <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                    <th class="pb-1 px-2">{{ __('Meat qty (execution)') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $animal)
                    @php
                        $alreadyBatched = $animal['already_batched'] ?? isset($alreadyBatchedLookup[$animal['animal_id']]);
                        $isChecked = is_array(old('selected_animal_ids'))
                            ? in_array($animal['animal_id'], old('selected_animal_ids'), true)
                            : ! $alreadyBatched;
                    @endphp
                    <tr class="border-t border-gray-100 {{ $alreadyBatched ? 'opacity-50' : '' }}">
                        <td class="py-1 px-2">
                            <input type="checkbox"
                                   name="selected_animal_ids[]"
                                   value="{{ $animal['animal_id'] }}"
                                   class="animal-checkbox"
                                   data-meat-kg="{{ $animal['meat_quantity_kg'] }}"
                                   data-execution-id="{{ $animal['execution_id'] }}"
                                   @disabled($alreadyBatched)
                                   @checked($isChecked && ! $alreadyBatched)>
                        </td>
                        <td class="py-1 px-2 text-xs text-slate-500">{{ $animal['session_label'] }}</td>
                        <td class="py-1 px-2 font-mono text-xs">
                            {{ $animal['ear_tag'] }}
                            @if (str_starts_with($animal['ear_tag'], 'LEGACY-'))
                                <span class="ml-1 text-xs text-gray-400 bg-gray-100 px-1 rounded">[legacy]</span>
                            @endif
                        </td>
                        <td class="py-1 px-2">{{ $animal['species'] }}</td>
                        <td class="py-1 px-2">{{ $animal['sex'] }}</td>
                        <td class="py-1 px-2">
                            {{ $animal['live_weight_kg'] ? number_format($animal['live_weight_kg'], 2).' kg' : '—' }}
                        </td>
                        <td class="py-1 px-2">
                            {{ number_format($animal['meat_quantity_kg'], 2) }} kg
                            @if ($alreadyBatched)
                                <span class="ml-1 text-xs text-amber-600 bg-amber-50 px-1 rounded">{{ __('Already batched') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
