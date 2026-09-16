@php
    $filters = $filters ?? ['q' => '', 'status' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'valid' => 0, 'expiring' => 0, 'expired' => 0];
    $typeLabel = static fn (string $type): string => str_replace('_', ' ', ucfirst($type));
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.permits.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="permit_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="permit_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Number, issuer…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="permit_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="permit_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $typeLabel($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.permits.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.permits.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Add permit') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Permits')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-certificate" />
                <x-butcher.kpi-card :label="__('Valid')" :value="(string) $kpis['valid']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Expiring (30d)')" :value="(string) $kpis['expiring']" tone="amber" icon="ti ti-clock" />
                <x-butcher.kpi-card :label="__('Expired')" :value="(string) $kpis['expired']" tone="rose" icon="ti ti-alert-circle" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Permit register') }}</h3>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count permit|:count permits', $permits->count(), ['count' => $permits->count()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Permit') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Issued by') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Validity') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($permits as $permit)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-certificate text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $permit->permit_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $typeLabel($permit->permit_type) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $permit->issued_by }}</td>
                                    <td class="px-4 py-3 sm:px-5 tabular-nums text-slate-700">
                                        {{ optional($permit->issue_date)->format('Y-m-d') }}
                                        <span class="text-slate-400">→</span>
                                        {{ optional($permit->expiry_date)->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">{{ $typeLabel($permit->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            @if ($permit->documentUrl())
                                                <a href="{{ $permit->documentUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    <i class="ti ti-file text-sm leading-none" aria-hidden="true"></i>
                                                    {{ __('View') }}
                                                </a>
                                            @endif
                                            <a href="{{ route('butcher.permits.edit', $permit) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' ? __('No permits match your filters.') : __('No permits yet.') }}
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
