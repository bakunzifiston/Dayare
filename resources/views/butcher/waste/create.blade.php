@php
    $isWaste = ($mode ?? 'waste') === 'waste';
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.waste.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Waste & Adjustments') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti {{ $isWaste ? 'ti-trash' : 'ti-adjustments' }} text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $isWaste ? __('Log waste') : __('Log adjustment') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $isWaste ? __('Record disposed stock from a batch.') : __('Correct batch weight up or down.') }}
                        </p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isWaste ? route('butcher.waste.store') : route('butcher.waste.adjustments.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
                onsubmit="return confirm(@js($isWaste ? __('Record this waste disposal? Stock will be reduced.') : __('Record this inventory adjustment? Stock weights will change immediately.')))"
            >
                @csrf

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $isWaste ? __('Waste') : __('Adjustment') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="batch_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Batch') }}</label>
                                <select id="batch_id" name="batch_id" required class="{{ $fieldClass }}">
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>
                                            {{ $batch->batch_number }} — {{ $fmtKg($batch->remaining_weight_kg) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('batch_id')" class="mt-2" />
                            </div>

                            @if ($isWaste)
                                <div>
                                    <label for="weight_disposed_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Weight (kg)') }}</label>
                                    <input id="weight_disposed_kg" name="weight_disposed_kg" type="number" step="0.001" min="0.1" required value="{{ old('weight_disposed_kg') }}" class="{{ $fieldClass }}">
                                    <x-input-error :messages="$errors->get('weight_disposed_kg')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="reason" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Reason') }}</label>
                                    <select id="reason" name="reason" required class="{{ $fieldClass }}">
                                        @foreach (\App\Models\ButcherDisposalLog::REASONS as $reason)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ str_replace('_', ' ', ucfirst($reason)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div>
                                    <label for="weight_change_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Weight change (kg)') }}</label>
                                    <input id="weight_change_kg" name="weight_change_kg" type="number" step="0.001" required value="{{ old('weight_change_kg') }}" class="{{ $fieldClass }}" placeholder="+2 or -1.5">
                                    <x-input-error :messages="$errors->get('weight_change_kg')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="reason" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Reason') }}</label>
                                    <select id="reason" name="reason" required class="{{ $fieldClass }}">
                                        @foreach (\App\Models\ButcherInventoryAdjustment::REASONS as $reason)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ str_replace('_', ' ', ucfirst($reason)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.waste.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isWaste ? 'ti-trash' : 'ti-check' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isWaste ? __('Record waste') : __('Record adjustment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
