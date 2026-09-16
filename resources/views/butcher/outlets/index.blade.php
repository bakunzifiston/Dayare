@php
    $filters = $filters ?? ['q' => '', 'status' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'primary' => 0];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.outlets.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="outlet_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="outlet_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name, phone, district…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="outlet_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="outlet_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                <option value="active" @selected($filters['status'] === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.outlets.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.outlets.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Add outlet') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Outlets')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-building-store" />
                <x-butcher.kpi-card :label="__('Active')" :value="(string) $kpis['active']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Inactive')" :value="(string) $kpis['inactive']" tone="amber" icon="ti ti-ban" />
                <x-butcher.kpi-card :label="__('Primary')" :value="(string) $kpis['primary']" tone="sky" icon="ti ti-star" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Outlet directory') }}</h3>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count outlet|:count outlets', $outlets->count(), ['count' => $outlets->count()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Location') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($outlets as $outlet)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-building-store text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $outlet->name }}</p>
                                                @if ($outlet->is_primary)
                                                    <span class="mt-1 inline-flex items-center rounded-md bg-bucha-primary/10 px-2 py-0.5 text-xs font-medium text-bucha-burgundy ring-1 ring-inset ring-bucha-primary/20">{{ __('Primary') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">
                                        @if ($outlet->district || $outlet->sector)
                                            {{ $outlet->district ?: '—' }}@if ($outlet->sector)<span class="text-slate-400"> · {{ $outlet->sector }}</span>@endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $outlet->phone }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @if ($outlet->status === \App\Models\ButcherOutlet::STATUS_ACTIVE)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200/80">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.outlets.edit', $outlet) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' ? __('No outlets match your filters.') : __('No outlets yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
