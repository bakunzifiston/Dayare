<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record post-mortem inspection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('One post-mortem inspection per batch. Only batches without an inspection are listed.') }}</p>
                <form method="post" action="{{ route('post-mortem-inspections.store') }}" class="space-y-6" id="post-mortem-form">
                    @csrf

                    <div>
                        <x-input-label for="batch_id" :value="__('Batch')" />
                        <select id="batch_id" name="batch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b['id'] }}" data-facility-id="{{ $b['facility_id'] }}" @selected(old('batch_id') == $b['id'])>{{ $b['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspector_id" :value="__('Inspector')" />
                        <select id="inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">{{ __('Select batch first') }}</option>
                            @foreach ($inspectorsByFacility as $fid => $inspectors)
                                @foreach ($inspectors as $insp)
                                    <option value="{{ $insp['id'] }}" data-facility-id="{{ $fid }}">{{ $insp['label'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('inspector_id')" />
                    </div>

                    <div>
                        <x-input-label for="inspection_date" :value="__('Inspection date')" />
                        <x-text-input id="inspection_date" name="inspection_date" type="date" class="mt-1 block w-full" :value="old('inspection_date', date('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('inspection_date')" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="total_examined" :value="__('Total examined')" />
                            <x-text-input id="total_examined" name="total_examined" type="number" min="0" class="mt-1 block w-full" :value="old('total_examined', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_examined')" />
                        </div>
                        <div>
                            <x-input-label for="approved_quantity" :value="__('Approved quantity')" />
                            <x-text-input id="approved_quantity" name="approved_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('approved_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('approved_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="condemned_quantity" :value="__('Condemned quantity')" />
                            <x-text-input id="condemned_quantity" name="condemned_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('condemned_quantity', 0)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('condemned_quantity')" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">{{ __('Approved + Condemned cannot exceed Total Examined.') }}</p>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex gap-4">
                        <x-primary-button>{{ __('Save inspection') }}</x-primary-button>
                        <a href="{{ route('post-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const batchSelect = document.getElementById('batch_id');
            const inspectorSelect = document.getElementById('inspector_id');
            const oldBatchId = '{{ old('batch_id') }}';
            const oldInspectorId = '{{ old('inspector_id') }}';
            function filterInspectors() {
                const selected = batchSelect && batchSelect.options[batchSelect.selectedIndex];
                const facilityId = selected && selected.dataset.facilityId;
                if (!inspectorSelect) return;
                Array.from(inspectorSelect.options).forEach(opt => {
                    if (opt.value === '') {
                        opt.textContent = facilityId ? 'Select inspector' : 'Select batch first';
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = opt.dataset.facilityId !== facilityId;
                });
                inspectorSelect.value = facilityId && oldBatchId === batchSelect.value ? oldInspectorId : '';
            }
            if (batchSelect) batchSelect.addEventListener('change', filterInspectors);
            document.addEventListener('DOMContentLoaded', filterInspectors);
        })();
    </script>
</x-app-layout>
