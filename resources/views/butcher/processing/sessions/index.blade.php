@php
    $filters = $filters ?? ['q' => '', 'status' => 'all'];
    $kpis = $kpis ?? ['total' => 0, 'open' => 0, 'closed' => 0, 'yield_kg' => 0];
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
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

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.processing.sessions.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="session_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="session_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Session #, batch…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="session_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="session_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.processing.sessions.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.processing.sessions.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Open session') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Sessions')" :value="(string) $kpis['total']" tone="bucha" icon="ti ti-scissors" />
                <x-butcher.kpi-card :label="__('Open')" :value="(string) $kpis['open']" tone="amber" icon="ti ti-clock" />
                <x-butcher.kpi-card :label="__('Closed')" :value="(string) $kpis['closed']" tone="emerald" icon="ti ti-circle-check" />
                <x-butcher.kpi-card :label="__('Yield')" :value="$fmtKg($kpis['yield_kg'])" tone="sky" icon="ti ti-scale" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Cutting sessions') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Break down source batches into cuts.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count session|:count sessions', $sessions->total(), ['count' => $sessions->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Session') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Source') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5 text-right">{{ __('Yield') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($sessions as $session)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-scissors text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $session->session_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $session->session_date?->toDateString() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $session->batch?->batch_number ?: '—' }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtKg($session->source_weight_kg) }}</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">
                                        @if ($session->status === 'closed')
                                            {{ $fmtKg($session->total_cuts_weight_kg) }}
                                            <span class="block text-xs text-slate-500">{{ number_format((float) $session->wastage_pct, 1) }}%</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$session->status" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.processing.sessions.show', $session) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' ? __('No sessions match your filters.') : __('No processing sessions yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($sessions->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $sessions->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
