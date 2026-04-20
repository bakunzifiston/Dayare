<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('batches.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create batch') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('Batch code will be auto-generated.') }}</p>
                <form method="post" action="{{ route('batches.store') }}" class="space-y-6" id="batch-form">
                    @csrf

                    <div>
                        <x-input-label for="slaughter_execution_id" :value="__('Slaughter execution')" />
                        <select id="slaughter_execution_id" name="slaughter_execution_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select slaughter execution') }}</option>
                            @foreach ($executions as $e)
                                <option value="{{ $e['id'] }}" data-facility-id="{{ $e['facility_id'] }}" data-species="{{ $e['species'] }}" @selected(old('slaughter_execution_id') == $e['id'])>{{ $e['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            <option value="">{{ __('Select execution first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}">{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="species_display" :value="__('Species')" />
                        <x-text-input id="species_display" type="text" class="mt-1 block w-full bg-gray-50 text-gray-700" :value="old('species')" readonly />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Automatically inherited from the selected slaughter execution.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="quantity" :value="__('Quantity')" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', 1)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                    </div>

                    <div>
                        <x-input-label for="quantity_unit" :value="__('Unit')" />
                        <select id="quantity_unit" name="quantity_unit" class="mt-1 block w-full border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-md shadow-sm" required>
                            @if (isset($units) && $units->isNotEmpty())
                                @foreach ($units as $unit)
                                    <option value="{{ $unit['code'] }}" @selected(old('quantity_unit') === $unit['code'])>{{ $unit['name'] }}</option>
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
                                <option value="{{ $s }}" @selected(old('status', 'pending') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Create batch') }}</x-primary-button>
                        <a href="{{ route('batches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const executionSelect = document.getElementById('slaughter_execution_id');
            const inspectorSelect = document.getElementById('inspector_id');
            const speciesDisplayInput = document.getElementById('species_display');
            const oldExecutionId = '{{ old("slaughter_execution_id") }}';
            const oldInspectorId = '{{ old("inspector_id") }}';
            function filterInspectors() {
                const selected = executionSelect && executionSelect.options[executionSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                const species = selected && selected.dataset.species;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') {
                        opt.textContent = facilityId ? 'Select inspector' : 'Select execution first';
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                inspectorSelect.value = facilityId && oldExecutionId === executionSelect.value ? oldInspectorId : '';
                if (speciesDisplayInput) speciesDisplayInput.value = species || '';
            }
            if (executionSelect) executionSelect.addEventListener('change', filterInspectors);
            document.addEventListener('DOMContentLoaded', filterInspectors);
        })();
    </script>
</x-app-layout>
