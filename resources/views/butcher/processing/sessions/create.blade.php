@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.processing.sessions.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Sessions') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-scissors text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Open session') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Start cutting from a source batch.') }}</p>
                    </div>
                </div>
            </div>

            @if ($batches->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('No batches available for processing. Receive stock first.') }}
                    <a href="{{ route('butcher.inventory.batches.index') }}" class="ml-1 font-semibold underline">{{ __('View inventory') }}</a>
                </div>
            @else
                <form method="post" action="{{ route('butcher.processing.sessions.store') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm" id="cutting-open-form">
                    @csrf
                    <div class="space-y-6 p-4 sm:p-6">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Session') }}</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                                    <select id="outlet_id" name="outlet_id" required class="{{ $fieldClass }}">
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="session_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Session date') }}</label>
                                    <input id="session_date" name="session_date" type="date" value="{{ old('session_date', now()->toDateString()) }}" class="{{ $fieldClass }}">
                                    <x-input-error :messages="$errors->get('session_date')" class="mt-2" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="batch_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source batch') }}</label>
                                    <select id="batch_id" name="batch_id" required class="{{ $fieldClass }}">
                                        <option value="">{{ __('Select batch…') }}</option>
                                        @foreach ($batches as $batch)
                                            <option
                                                value="{{ $batch->id }}"
                                                data-remaining="{{ $batch->remaining_weight_kg }}"
                                                data-blocked="{{ $batch->isSafetyBlocked() ? '1' : '0' }}"
                                                @selected(old('batch_id') == $batch->id)
                                            >
                                                {{ $batch->batch_number }} — {{ ucfirst($batch->meat_type) }} — {{ $fmtKg($batch->remaining_weight_kg) }} kg left
                                                @if ($batch->hasTemperatureBreach()) — {{ __('TEMP BREACH') }} @endif
                                                @if ($batch->isExpired()) — {{ __('EXPIRED') }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('batch_id')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('safety_override_required')" class="mt-2" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="source_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source weight (kg)') }}</label>
                                    <input id="source_weight_kg" name="source_weight_kg" type="number" step="0.001" min="0.1" value="{{ old('source_weight_kg') }}" required class="{{ $fieldClass }}">
                                    <p id="remaining-hint" class="mt-1 text-xs text-slate-500"></p>
                                    <x-input-error :messages="$errors->get('source_weight_kg')" class="mt-2" />
                                </div>
                                @if ($canOverride)
                                    <div id="override-box" class="sm:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3 space-y-2" style="display:none">
                                        <label for="safety_override_reason" class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('Override reason') }}</label>
                                        <textarea id="safety_override_reason" name="safety_override_reason" rows="2" class="mt-1 block w-full rounded-lg border-amber-300 text-sm" placeholder="{{ __('Required for Manager/Owner override') }}">{{ old('safety_override_reason') }}</textarea>
                                        <x-input-error :messages="$errors->get('safety_override_reason')" class="mt-2" />
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                        <a href="{{ route('butcher.processing.sessions.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Open session') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if ($batches->isNotEmpty())
        <script>
            const batchSelect = document.getElementById('batch_id');
            const weightInput = document.getElementById('source_weight_kg');
            const hint = document.getElementById('remaining-hint');
            const form = document.getElementById('cutting-open-form');
            function updateHint() {
                const opt = batchSelect.selectedOptions[0];
                const remaining = opt?.dataset?.remaining;
                const blocked = opt?.dataset?.blocked === '1';
                const overrideBox = document.getElementById('override-box');
                if (remaining) {
                    hint.textContent = @json(__('Maximum available: :kg kg')).replace(':kg', parseFloat(remaining).toFixed(2));
                    weightInput.max = remaining;
                } else {
                    hint.textContent = '';
                }
                if (overrideBox) {
                    overrideBox.style.display = blocked ? 'block' : 'none';
                }
            }
            batchSelect.addEventListener('change', updateHint);
            updateHint();
            if (form) {
                form.addEventListener('submit', function (e) {
                    const opt = batchSelect.selectedOptions[0];
                    if (opt?.dataset?.blocked === '1') {
                        if (!confirm(@js(__('This batch is safety-blocked. Proceed with a logged Manager/Owner override?')))) {
                            e.preventDefault();
                        }
                    }
                });
            }
        </script>
    @endif
</x-app-layout>
