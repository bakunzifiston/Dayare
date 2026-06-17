@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cutting & processing') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Open cutting sessions, log yields, and print shelf labels.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('butcher.cutting.types.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cut types') }}</a>
                <a href="{{ route('butcher.cutting.sessions.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open session') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Sessions today')" :value="$summary['sessions_today']" :href="route('butcher.cutting.sessions.index')" />
                <x-kpi-card stat :title="__('Yield today (kg)')" :value="$fmtKg($summary['yield_today_kg'])" :href="route('butcher.cutting.sessions.index')" />
                <x-kpi-card stat :title="__('Avg wastage % (30d)')" :value="number_format((float) $summary['avg_wastage_pct'], 1).'%'" :href="route('butcher.cutting.sessions.index')" />
                <x-kpi-card stat :title="__('Total yield (30d kg)')" :value="$fmtKg($summary['total_yield_kg'])" :href="route('butcher.cutting.sessions.index')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Open sessions') }}</h3>
                        <a href="{{ route('butcher.cutting.sessions.index') }}" class="text-xs font-semibold text-bucha-primary hover:underline">{{ __('All sessions') }}</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($summary['open_sessions'] as $session)
                            <a href="{{ route('butcher.cutting.sessions.show', $session) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ $session->session_number }}</p>
                                    <x-butcher.status-badge :status="$session->status" />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $session->batch?->batch_number }} · {{ $fmtKg($session->source_weight_kg) }} kg · {{ $session->session_date?->toDateString() }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No open cutting sessions.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recently closed') }}</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($summary['recent_closed_sessions'] as $session)
                            <a href="{{ route('butcher.cutting.sessions.show', $session) }}" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ $session->session_number }}</p>
                                    <span class="text-xs text-slate-500">{{ number_format((float) $session->wastage_pct, 1) }}% {{ __('wastage') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $fmtKg($session->total_cuts_weight_kg) }} kg yield · {{ $session->closed_at?->format('M j, H:i') }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No closed sessions yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
