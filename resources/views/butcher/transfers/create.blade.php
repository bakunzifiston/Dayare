<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.transfers.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transfers') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Transfer stock') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.transfers.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4" id="transfer-form">
                @csrf

                <div>
                    <x-input-label for="batch_id" :value="__('Source batch')" />
                    <select id="batch_id" name="batch_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ __('Select batch…') }}</option>
                        @foreach ($batches as $batch)
                            <option
                                value="{{ $batch->id }}"
                                data-outlet="{{ $batch->outlet_id }}"
                                data-remaining="{{ $batch->remaining_weight_kg }}"
                                @selected(old('batch_id') == $batch->id)
                            >
                                {{ $batch->batch_number }} — {{ $batch->outlet?->name }} — {{ ucfirst($batch->meat_type) }} ({{ number_format((float) $batch->remaining_weight_kg, 2) }} kg)
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('batch_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="from_outlet_id" :value="__('From outlet')" />
                        <select id="from_outlet_id" name="from_outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('from_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('from_outlet_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="to_outlet_id" :value="__('To outlet')" />
                        <select id="to_outlet_id" name="to_outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('to_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('to_outlet_id')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="quantity_kg" :value="__('Quantity (kg)')" />
                    <x-text-input id="quantity_kg" name="quantity_kg" type="number" step="0.001" min="0.001" class="mt-1 block w-full" :value="old('quantity_kg')" required />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Available') }}: <span id="available-kg">—</span></p>
                    <x-input-error :messages="$errors->get('quantity_kg')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Transfer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const batchSelect = document.getElementById('batch_id');
            const fromSelect = document.getElementById('from_outlet_id');
            const available = document.getElementById('available-kg');
            const qty = document.getElementById('quantity_kg');

            function sync() {
                const opt = batchSelect.selectedOptions[0];
                if (!opt || !opt.value) {
                    available.textContent = '—';
                    return;
                }
                if (opt.dataset.outlet) fromSelect.value = opt.dataset.outlet;
                available.textContent = (parseFloat(opt.dataset.remaining || '0')).toFixed(3) + ' kg';
                if (!qty.value) qty.value = opt.dataset.remaining || '';
            }

            batchSelect?.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-app-layout>
