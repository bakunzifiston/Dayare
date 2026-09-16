@php
    $filters = $filters ?? ['q' => '', 'type' => 'all'];
    $kpis = $kpis ?? ['waste_kg' => 0, 'waste_events' => 0, 'adjustment_kg' => 0, 'adjustment_events' => 0];
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
    $showWaste = $filters['type'] !== 'adjustment';
    $showAdjustments = $filters['type'] !== 'waste';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.waste.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="waste_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="waste_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Batch #, reason…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="waste_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</label>
                            <select id="waste_type" name="type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['type'] === 'all')>{{ __('All') }}</option>
                                <option value="waste" @selected($filters['type'] === 'waste')>{{ __('Waste') }}</option>
                                <option value="adjustment" @selected($filters['type'] === 'adjustment')>{{ __('Adjustments') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.waste.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.waste.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Log waste') }}
                        </a>
                        <a href="{{ route('butcher.waste.adjustments.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="ti ti-adjustments text-base leading-none" aria-hidden="true"></i>
                            {{ __('Log adjustment') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Waste')" :value="$fmtKg($kpis['waste_kg'])" tone="rose" icon="ti ti-trash" />
                <x-butcher.kpi-card :label="__('Waste events')" :value="(string) $kpis['waste_events']" tone="amber" icon="ti ti-list-details" />
                <x-butcher.kpi-card :label="__('Net adjustments')" :value="((float) $kpis['adjustment_kg'] > 0 ? '+' : '').$fmtKg($kpis['adjustment_kg'])" tone="sky" icon="ti ti-adjustments" />
                <x-butcher.kpi-card :label="__('Adjustment events')" :value="(string) $kpis['adjustment_events']" tone="bucha" icon="ti ti-clipboard-list" />
            </div>

            @if ($showWaste)
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Waste') }}</h3>
                        <span class="text-xs text-slate-500">{{ trans_choice(':count event|:count events', $waste->total(), ['count' => $waste->total()]) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                    <th class="px-4 py-3 sm:px-5 text-right">{{ __('Weight') }}</th>
                                    <th class="px-4 py-3 sm:px-5">{{ __('Reason') }}</th>
                                    <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('When') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($waste as $item)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-start gap-3">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                    <i class="ti ti-trash text-[1.15rem] leading-none"></i>
                                                </span>
                                                <div>
                                                    <p class="font-medium text-slate-900">{{ $item->batch?->batch_number ?: '—' }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500 sm:hidden">{{ $item->disposed_at?->format('Y-m-d H:i') }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtKg($item->weight_disposed_kg) }}</td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-700">{{ str_replace('_', ' ', ucfirst($item->reason)) }}</td>
                                        <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $item->disposed_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No waste recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($waste->hasPages())
                        <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $waste->links() }}</div>
                    @endif
                </section>
            @endif

            @if ($showAdjustments)
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Adjustments') }}</h3>
                        <span class="text-xs text-slate-500">{{ trans_choice(':count event|:count events', $adjustments->total(), ['count' => $adjustments->total()]) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                    <th class="px-4 py-3 sm:px-5 text-right">{{ __('Change') }}</th>
                                    <th class="px-4 py-3 sm:px-5">{{ __('Reason') }}</th>
                                    <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('When') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($adjustments as $item)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-start gap-3">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                    <i class="ti ti-adjustments text-[1.15rem] leading-none"></i>
                                                </span>
                                                <div>
                                                    <p class="font-medium text-slate-900">{{ $item->batch?->batch_number ?: '—' }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500 sm:hidden">{{ $item->adjusted_at?->format('Y-m-d H:i') }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ ((float) $item->weight_change_kg > 0 ? '+' : '').$fmtKg($item->weight_change_kg) }}</td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-700">{{ str_replace('_', ' ', ucfirst($item->reason)) }}</td>
                                        <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $item->adjusted_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No adjustments recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($adjustments->hasPages())
                        <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $adjustments->links() }}</div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
