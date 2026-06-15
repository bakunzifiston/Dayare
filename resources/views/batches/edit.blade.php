<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('batches.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit batch') }} — {{ $batch->batch_code }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($batch->isColdChainCompromised())
                <div class="mb-4 rounded bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                    <strong>{{ __('Cold chain compromised') }}</strong> — {{ __('temperature violations recorded. Review before approving.') }}
                </div>
            @elseif ($batch->isColdChainAtRisk())
                <div class="mb-4 rounded bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-800">
                    <strong>{{ __('Cold chain at risk') }}</strong> — {{ __('temperature has approached threshold limits.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('batches.update', $batch) }}" class="space-y-6" id="batch-edit-form">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="slaughter_execution_id" :value="__('Slaughter execution')" />
                        <select id="slaughter_execution_id" name="slaughter_execution_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @foreach ($executions as $e)
                                <option value="{{ $e['id'] }}" data-facility-id="{{ $e['facility_id'] }}" data-species="{{ $e['species'] }}" @selected(old('slaughter_execution_id', $batch->slaughter_execution_id) == $e['id'])>{{ $e['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select inspector') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}" @selected(old('inspector_id', $batch->inspector_id) == $insp['id'])>{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="species_display" :value="__('Species')" />
                        <x-text-input id="species_display" type="text" class="mt-1 block w-full bg-gray-50 text-gray-700" :value="old('species', $batch->species)" readonly />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Automatically inherited from the selected slaughter execution.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="quantity" :value="__('Quantity')" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', $batch->quantity)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                    </div>

                    <div>
                        <x-input-label for="quantity_unit" :value="__('Unit')" />
                        <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @if (isset($units) && $units->isNotEmpty())
                                @foreach ($units as $unit)
                                    <option value="{{ $unit['code'] }}" @selected(old('quantity_unit', $batch->quantity_unit) === $unit['code'])>{{ $unit['name'] }}</option>
                                @endforeach
                            @else
                                <option value="">{{ __('No configured units available') }}</option>
                            @endif
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('quantity_unit')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm">
                            @foreach (\App\Models\Batch::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $batch->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        @if (! $batch->hasPostMortem())
                            <p class="mt-1 text-xs text-amber-600">
                                {{ __('Post-mortem required before approving.') }}
                                <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}"
                                   class="underline">{{ __('Record post-mortem →') }}</a>
                            </p>
                        @endif
                    </div>

                    @if ($batch->hasPerAnimalData())
                        <details class="mt-4 border border-gray-200 rounded-md" open>
                            <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 select-none">
                                {{ __('Individual animal quantities (:count animals)', ['count' => $batch->animal_count]) }}
                            </summary>
                            <div class="p-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs text-gray-500">
                                            <th class="pb-1 px-2">{{ __('Ear tag') }}</th>
                                            <th class="pb-1 px-2">{{ __('Species') }}</th>
                                            <th class="pb-1 px-2">{{ __('Live weight') }}</th>
                                            <th class="pb-1 px-2">{{ __('Meat qty (kg)') }}</th>
                                            <th class="pb-1 px-2">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($batch->items as $batchItem)
                                            <tr class="border-t border-gray-100">
                                                <td class="py-1 px-2 font-mono text-xs">{{ $batchItem->intakeItem->ear_tag }}</td>
                                                <td class="py-1 px-2">{{ $batchItem->intakeItem->species }}</td>
                                                <td class="py-1 px-2">
                                                    {{ $batchItem->intakeItem->live_weight_kg
                                                        ? number_format($batchItem->intakeItem->live_weight_kg, 2).' kg' : '—' }}
                                                </td>
                                                <td class="py-1 px-2">
                                                    <input type="hidden"
                                                           name="item_quantities[{{ $loop->index }}][slaughter_execution_item_id]"
                                                           value="{{ $batchItem->slaughter_execution_item_id }}">
                                                    <input type="number"
                                                           name="item_quantities[{{ $loop->index }}][meat_quantity_kg]"
                                                           value="{{ old("item_quantities.{$loop->index}.meat_quantity_kg", $batchItem->meat_quantity_kg) }}"
                                                           min="0.01" max="9999" step="0.01"
                                                           class="w-24 text-sm rounded border-gray-300">
                                                    @error("item_quantities.{$loop->index}.meat_quantity_kg")
                                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="py-1 px-2">
                                                    <input type="text"
                                                           name="item_quantities[{{ $loop->index }}][notes]"
                                                           value="{{ old("item_quantities.{$loop->index}.notes", $batchItem->notes ?? '') }}"
                                                           class="w-full text-sm rounded border-gray-300"
                                                           placeholder="{{ __('Optional') }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endif

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Update batch') }}</x-primary-button>
                        <a href="{{ route('batches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var executionSelect = document.getElementById('slaughter_execution_id');
                var inspectorSelect = document.getElementById('inspector_id');
                var speciesDisplayInput = document.getElementById('species_display');
                function filterInspectors() {
                    var selected = executionSelect && executionSelect.options[executionSelect.selectedIndex];
                    var facilityId = selected && selected.dataset.facilityId;
                    var species = selected && selected.dataset.species;
                    if (!inspectorSelect) return;
                    Array.from(inspectorSelect.options).forEach(function (opt) {
                        if (opt.value === '') { opt.hidden = false; return; }
                        opt.hidden = opt.dataset.facilityId !== facilityId;
                    });
                    var currentOpt = inspectorSelect.options[inspectorSelect.selectedIndex];
                    if (currentOpt && currentOpt.hidden) {
                        var visible = Array.from(inspectorSelect.options).find(function (o) { return o.value && !o.hidden; });
                        inspectorSelect.value = visible ? visible.value : '';
                    }
                    if (speciesDisplayInput) speciesDisplayInput.value = species || '';
                }
                document.addEventListener('DOMContentLoaded', function () {
                    if (executionSelect) executionSelect.addEventListener('change', filterInspectors);
                    filterInspectors();
                });
            }());
        </script>
    @endpush
</x-app-layout>
