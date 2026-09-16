@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.transfers.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Transfers') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-arrows-exchange text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('New transfer') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Move stock between outlets.') }}</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('butcher.transfers.store') }}" id="transfer-form" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                @csrf

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Transfer') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="batch_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source batch') }}</label>
                                <select id="batch_id" name="batch_id" required class="{{ $fieldClass }}">
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
                            <div>
                                <label for="from_outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From outlet') }}</label>
                                <select id="from_outlet_id" name="from_outlet_id" required class="{{ $fieldClass }}">
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('from_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('from_outlet_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="to_outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To outlet') }}</label>
                                <select id="to_outlet_id" name="to_outlet_id" required class="{{ $fieldClass }}">
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('to_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('to_outlet_id')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="quantity_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Quantity (kg)') }}</label>
                                <input id="quantity_kg" name="quantity_kg" type="number" step="0.001" min="0.001" required value="{{ old('quantity_kg') }}" class="{{ $fieldClass }}">
                                <p class="mt-1 text-xs text-slate-500">{{ __('Available') }}: <span id="available-kg">—</span></p>
                                <x-input-error :messages="$errors->get('quantity_kg')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.transfers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-arrows-exchange text-base leading-none" aria-hidden="true"></i>
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
