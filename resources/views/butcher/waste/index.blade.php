@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Waste & adjustments') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Record disposals and correct inventory weights.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Waste (30d)')" :value="$fmtKg($summary['waste_kg'])" />
                <x-kpi-card stat :title="__('Waste events')" :value="$summary['waste_events']" />
                <x-kpi-card stat :title="__('Net adjustments')" :value="$fmtKg($summary['adjustment_kg'])" />
                <x-kpi-card stat :title="__('Adjustment events')" :value="$summary['adjustment_events']" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Log waste') }}</h3>
                    <form method="post" action="{{ route('butcher.waste.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Batch') }}</label>
                            <select name="batch_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                @foreach ($activeBatches->where('remaining_weight_kg', '>', 0) as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->batch_number }} — {{ $fmtKg($batch->remaining_weight_kg) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('batch_id')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Weight (kg)') }}</label>
                                <input name="weight_disposed_kg" type="number" step="0.001" min="0.1" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Reason') }}</label>
                                <select name="reason" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    @foreach (\App\Models\ButcherDisposalLog::REASONS as $reason)
                                        <option value="{{ $reason }}">{{ str_replace('_', ' ', ucfirst($reason)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Notes') }}</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record waste') }}</button>
                    </form>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha space-y-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Log adjustment') }}</h3>
                    <form method="post" action="{{ route('butcher.waste.adjustments.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Batch') }}</label>
                            <select name="batch_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                @foreach ($activeBatches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->batch_number }} — {{ $fmtKg($batch->remaining_weight_kg) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('batch_id')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Weight change (kg)') }}</label>
                                <input name="weight_change_kg" type="number" step="0.001" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="+2 or -1.5">
                                <p class="mt-1 text-xs text-slate-500">{{ __('Use negative values to reduce stock.') }}</p>
                                <x-input-error :messages="$errors->get('weight_change_kg')" class="mt-1" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Reason') }}</label>
                                <select name="reason" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    @foreach (\App\Models\ButcherInventoryAdjustment::REASONS as $reason)
                                        <option value="{{ $reason }}">{{ str_replace('_', ' ', ucfirst($reason)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Notes') }}</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record adjustment') }}</button>
                    </form>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent waste') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($summary['recent_waste'] as $item)
                            <div class="rounded-lg border border-slate-200 px-3 py-2">
                                <p class="font-medium">{{ $item->batch?->batch_number }} · {{ $fmtKg($item->weight_disposed_kg) }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($item->reason) }} · {{ $item->disposed_at?->format('Y-m-d H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('No waste recorded yet.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent adjustments') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($summary['recent_adjustments'] as $item)
                            <div class="rounded-lg border border-slate-200 px-3 py-2">
                                <p class="font-medium">{{ $item->batch?->batch_number }} · {{ ((float) $item->weight_change_kg > 0 ? '+' : '').$fmtKg($item->weight_change_kg) }}</p>
                                <p class="text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($item->reason)) }} · {{ $item->adjusted_at?->format('Y-m-d H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500">{{ __('No adjustments recorded yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
