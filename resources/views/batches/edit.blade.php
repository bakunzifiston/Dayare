<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit batch') }} — {{ $batch->batch_code }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="post" action="{{ route('batches.update', $batch) }}" class="space-y-6" id="batch-edit-form">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="slaughter_execution_id" :value="__('Slaughter execution')" />
                        <select id="slaughter_execution_id" name="slaughter_execution_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach ($executions as $e)
                                <option value="{{ $e['id'] }}" data-facility-id="{{ $e['facility_id'] }}" @selected(old('slaughter_execution_id', $batch->slaughter_execution_id) == $e['id'])>{{ $e['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('slaughter_execution_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
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
                        <x-input-label for="species" :value="__('Species')" />
                        <select id="species" name="species" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach (\App\Models\Batch::SPECIES_OPTIONS as $s)
                                <option value="{{ $s }}" @selected(old('species', $batch->species) === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('species')" />
                    </div>

                    <div>
                        <x-input-label for="quantity" :value="__('Quantity')" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', $batch->quantity)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (\App\Models\Batch::STATUSES as $s)
                                <option value="{{ $s }}" @selected(old('status', $batch->status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

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

    <script>
        (function() {
            const executionSelect = document.getElementById('slaughter_execution_id');
            const inspectorSelect = document.getElementById('inspector_id');
            function filterInspectors() {
                const selected = executionSelect && executionSelect.options[executionSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                const currentOpt = inspectorSelect.options[inspectorSelect.selectedIndex];
                if (currentOpt && currentOpt.hidden) {
                    const visible = Array.from(inspectorSelect.options).find(o => o.value && !o.hidden);
                    inspectorSelect.value = visible ? visible.value : '';
                }
            }
            if (executionSelect) executionSelect.addEventListener('change', filterInspectors);
            document.addEventListener('DOMContentLoaded', filterInspectors);
        })();
    </script>
</x-app-layout>
