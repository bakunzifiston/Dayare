@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
    $summary = $summary ?? [];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('butcher.processing.types.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cut types') }}</a>
                    <a href="{{ route('butcher.processing.sessions.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('All sessions') }}</a>
                    <a href="{{ route('butcher.processing.sessions.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Open session') }}
                    </a>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Sessions today')" :value="(string) ($summary['sessions_today'] ?? 0)" tone="bucha" icon="ti ti-scissors" />
                <x-butcher.kpi-card :label="__('Yield today')" :value="$fmtKg($summary['yield_today_kg'] ?? 0).' kg'" tone="emerald" icon="ti ti-scale" />
                <x-butcher.kpi-card :label="__('Avg wastage (30d)')" :value="number_format((float) ($summary['avg_wastage_pct'] ?? 0), 1).'%'" tone="amber" icon="ti ti-percentage" />
                <x-butcher.kpi-card :label="__('Total yield (30d)')" :value="$fmtKg($summary['total_yield_kg'] ?? 0).' kg'" tone="sky" icon="ti ti-chart-bar" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Open sessions') }}</h3>
                        <a href="{{ route('butcher.processing.sessions.index', ['status' => 'open']) }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('View all') }}</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($summary['open_sessions'] ?? [] as $session)
                            <a href="{{ route('butcher.processing.sessions.show', $session) }}" class="flex items-start gap-3 px-4 py-3 sm:px-5 hover:bg-slate-50/80">
                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                    <i class="ti ti-scissors text-[1.15rem] leading-none"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-medium text-slate-900">{{ $session->session_number }}</p>
                                        <x-butcher.status-badge :status="$session->status" />
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $session->batch?->batch_number }} · {{ $fmtKg($session->source_weight_kg) }} kg · {{ $session->session_date?->toDateString() }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No open processing sessions.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Recently closed') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($summary['recent_closed_sessions'] ?? [] as $session)
                            <a href="{{ route('butcher.processing.sessions.show', $session) }}" class="flex items-start gap-3 px-4 py-3 sm:px-5 hover:bg-slate-50/80">
                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                    <i class="ti ti-circle-check text-[1.15rem] leading-none"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-medium text-slate-900">{{ $session->session_number }}</p>
                                        <span class="text-xs text-slate-500">{{ number_format((float) $session->wastage_pct, 1) }}% {{ __('wastage') }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $fmtKg($session->total_cuts_weight_kg) }} kg yield · {{ $session->closed_at?->format('M j, H:i') }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No closed sessions yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
