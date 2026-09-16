@php
    $filters = $filters ?? ['q' => '', 'meat_type' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'active' => 0, 'inactive' => 0];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.processing.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Processing') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.processing.types.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="cut_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="cut_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Cut name…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="cut_meat" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat') }}</label>
                            <select id="cut_meat" name="meat_type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['meat_type'] === 'all')>{{ __('All types') }}</option>
                                @foreach ($meatTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['meat_type'] === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.processing.types.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.processing.types.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Add cut type') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                <x-butcher.kpi-card :label="__('Cut types')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-cut" />
                <x-butcher.kpi-card :label="__('Active')" :value="(string) $kpis['active']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Inactive')" :value="(string) $kpis['inactive']" tone="amber" icon="ti ti-ban" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Cut types') }}</h3>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count type|:count types', $cutTypes->total(), ['count' => $cutTypes->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Name') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Meat') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Expected yield') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($cutTypes as $cutType)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-cut text-[1.15rem] leading-none"></i>
                                            </span>
                                            <p class="font-medium text-slate-900">{{ $cutType->name }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 capitalize text-slate-700">{{ $cutType->meat_type }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ number_format((float) $cutType->expected_yield_pct, 1) }}%</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($cutType->is_active)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200/80">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No cut types yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($cutTypes->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $cutTypes->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
