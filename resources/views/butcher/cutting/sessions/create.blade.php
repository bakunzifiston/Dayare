@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Open cutting session') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Select a stored batch and the weight to pull for cutting.') }}</p>
            </div>
            <a href="{{ route('butcher.cutting.sessions.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($batches->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('No batches available for cutting. Receive stock into cold storage first.') }}
                    <a href="{{ route('butcher.storage.batches.index') }}" class="ml-1 font-semibold underline">{{ __('View batches') }}</a>
                </div>
            @else
                <form method="post" action="{{ route('butcher.cutting.sessions.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-5">
                    @csrf
                    <div>
                        <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                        <select id="outlet_id" name="outlet_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('outlet_id')" class="mt-1" />
                    </div>
                    <div>
                        <label for="batch_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source batch') }}</label>
                        <select id="batch_id" name="batch_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Select batch…') }}</option>
                            @foreach ($batches as $batch)
                                <option
                                    value="{{ $batch->id }}"
                                    data-remaining="{{ $batch->remaining_weight_kg }}"
                                    @selected(old('batch_id') == $batch->id)
                                >
                                    {{ $batch->batch_number }} — {{ ucfirst($batch->meat_type) }} — {{ $fmtKg($batch->remaining_weight_kg) }} kg left
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('batch_id')" class="mt-1" />
                    </div>
                    <div>
                        <label for="source_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source weight (kg)') }}</label>
                        <input id="source_weight_kg" name="source_weight_kg" type="number" step="0.001" min="0.1" value="{{ old('source_weight_kg') }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <p id="remaining-hint" class="mt-1 text-xs text-slate-500"></p>
                        <x-input-error :messages="$errors->get('source_weight_kg')" class="mt-1" />
                    </div>
                    <div>
                        <label for="session_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Session date') }}</label>
                        <input id="session_date" name="session_date" type="date" value="{{ old('session_date', now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <x-input-error :messages="$errors->get('session_date')" class="mt-1" />
                    </div>
                    <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open session') }}</button>
                </form>
            @endif
        </div>
    </div>

    @if ($batches->isNotEmpty())
        <script>
            const batchSelect = document.getElementById('batch_id');
            const weightInput = document.getElementById('source_weight_kg');
            const hint = document.getElementById('remaining-hint');
            function updateHint() {
                const opt = batchSelect.selectedOptions[0];
                const remaining = opt?.dataset?.remaining;
                if (remaining) {
                    hint.textContent = @json(__('Maximum available: :kg kg')).replace(':kg', parseFloat(remaining).toFixed(2));
                    weightInput.max = remaining;
                } else {
                    hint.textContent = '';
                }
            }
            batchSelect.addEventListener('change', updateHint);
            updateHint();
        </script>
    @endif
</x-app-layout>
