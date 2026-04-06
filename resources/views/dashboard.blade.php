<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Dashboard') }}</span>
    </x-slot>

    @php
        $logisticsTotal = ($kpis['transport_trips'] ?? 0) + ($kpis['delivery_confirmations'] ?? 0);
        $inventoryUnits = $kpis['batches'] ?? 0;
        $incidents = max(0, (int) ($complianceSummary['attention'] ?? 0));
        $showAsideAlerts = ($complianceSummary['pending_plans'] ?? 0) > 0 || ($incidents > 0);
    @endphp

    <div class="py-4 lg:py-6">
        <div class="max-w-[1600px] mx-auto px-0 sm:px-0">
            <div class="grid grid-cols-1 gap-6 xl:gap-8 {{ $showAsideAlerts ? 'xl:grid-cols-[1fr_320px]' : '' }}">
                {{-- Main column --}}
                <div class="space-y-6 min-w-0">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            {{ __('Welcome, :name', ['name' => $user->name]) }}
                        </h1>
                        <p class="mt-1 text-sm text-bucha-muted">{{ __('Meat traceability and compliance overview for your workspace.') }}</p>
                    </div>

                    {{-- Key metrics: unified stat tiles --}}
                    <section aria-labelledby="dashboard-key-metrics-heading">
                        <div class="mb-3">
                            <h2 id="dashboard-key-metrics-heading" class="text-sm font-semibold text-slate-900">
                                {{ __('Key metrics') }}
                            </h2>
                            <p class="mt-0.5 text-xs text-bucha-muted">{{ __('Counts across your workspace') }}</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 sm:gap-2.5">
                            <x-kpi-card stat title="{{ __('Businesses') }}" :value="$kpis['businesses']" :href="route('businesses.hub')">
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </x-slot>
                            </x-kpi-card>
                            <x-kpi-card stat title="{{ __('Facilities') }}" :value="$kpis['facilities']">
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </x-slot>
                            </x-kpi-card>
                            <x-kpi-card stat title="{{ __('Inspectors') }}" :value="$kpis['inspectors']" :href="route('inspectors.hub')">
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </x-slot>
                            </x-kpi-card>
                            <x-kpi-card
                                stat
                                title="{{ __('Slaughter plans') }}"
                                :value="$kpis['slaughter_plans']"
                                :subtitle="$kpis['slaughter_plans_approved'] . ' ' . __('approved')"
                                :href="route('slaughter-plans.hub')"
                            >
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </x-slot>
                            </x-kpi-card>
                            <x-kpi-card
                                stat
                                title="{{ __('Certificates') }}"
                                :value="$kpis['certificates']"
                                :subtitle="$kpis['certificates_active'] . ' ' . __('active')"
                                :href="route('certificates.index')"
                            >
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </x-slot>
                            </x-kpi-card>
                            <x-kpi-card
                                stat
                                title="{{ __('Executions') }}"
                                :value="$kpis['slaughter_executions']"
                                :subtitle="$kpis['executions_completed'] . ' ' . __('completed')"
                                :href="route('slaughter-executions.hub')"
                            >
                                <x-slot name="icon">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </x-slot>
                            </x-kpi-card>
                        </div>
                    </section>

                    {{-- Hero metric cards (BuchaPro style) --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <a href="{{ route('transport-trips.hub') }}" class="group rounded-bucha bg-bucha-card border border-slate-200/80 p-4 sm:p-5 shadow-bucha hover:shadow-bucha-md transition-shadow">
                            <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Logistics flow') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-slate-900">{{ number_format($logisticsTotal) }}</p>
                            <p class="mt-1 text-xs text-bucha-muted">{{ __('Trips + deliveries') }}</p>
                        </a>
                        <a href="{{ route('batches.index') }}" class="group rounded-bucha bg-bucha-card border border-slate-200/80 p-4 sm:p-5 shadow-bucha hover:shadow-bucha-md transition-shadow">
                            <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Production units') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-slate-900">{{ number_format($inventoryUnits) }}</p>
                            <p class="mt-1 text-xs text-bucha-muted">{{ __('Batches') }}</p>
                        </a>
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 p-4 sm:p-5 shadow-bucha">
                            <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Compliance score') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-bucha-primary">{{ $complianceSummary['grade'] }}</p>
                            <p class="mt-1 text-xs text-bucha-muted">{{ $complianceSummary['label'] }}</p>
                        </div>
                        <a href="{{ route('slaughter-plans.hub') }}" class="group rounded-bucha bg-bucha-card border border-slate-200/80 p-4 sm:p-5 shadow-bucha hover:shadow-bucha-md transition-shadow">
                            <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted">{{ __('Needs attention') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-bucha-primary">{{ number_format($incidents) }}</p>
                            <p class="mt-1 text-xs text-bucha-muted">{{ __('Plans & follow-ups') }}</p>
                        </a>
                    </div>

                    {{-- Compliance + quick status (main column, was sidebar) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-5 sm:p-6 text-center sm:text-left">
                            <p class="text-xs font-semibold uppercase tracking-wider text-bucha-muted mb-3">{{ __('Compliance') }}</p>
                            <div class="relative mx-auto sm:mx-0 w-28 h-28 flex items-center justify-center">
                                <svg class="w-full h-full -rotate-90 text-slate-100" viewBox="0 0 36 36" aria-hidden="true">
                                    <path class="text-slate-100" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    @php $dashPct = min(100, max(0, (int) ($complianceSummary['score'] ?? 0))); @endphp
                                    <path class="text-bucha-primary" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none" stroke-dasharray="{{ $dashPct }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold text-slate-900">{{ $complianceSummary['score'] }}</span>
                                    <span class="text-xs font-semibold text-bucha-primary">{{ $complianceSummary['grade'] }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-bucha-muted mt-2 max-w-xs mx-auto sm:mx-0">{{ $complianceSummary['label'] }}</p>
                        </div>
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-4 sm:p-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-bucha-muted mb-3">{{ __('Quick status') }}</h3>
                            <ul class="space-y-3 text-sm">
                                <li class="flex justify-between gap-2">
                                    <span class="text-slate-600">{{ __('Active certificates') }}</span>
                                    <span class="font-semibold text-bucha-success tabular-nums">{{ $kpis['certificates_active'] ?? 0 }}</span>
                                </li>
                                <li class="flex justify-between gap-2">
                                    <span class="text-slate-600">{{ __('Pending plans') }}</span>
                                    <span class="font-semibold tabular-nums {{ ($complianceSummary['pending_plans'] ?? 0) > 0 ? 'text-bucha-warning' : 'text-slate-700' }}">{{ $complianceSummary['pending_plans'] ?? 0 }}</span>
                                </li>
                                <li class="flex justify-between gap-2">
                                    <span class="text-slate-600">{{ __('Transport trips') }}</span>
                                    <span class="font-semibold text-slate-900 tabular-nums">{{ $kpis['transport_trips'] ?? 0 }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Map placeholder + chart row --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-slate-800">{{ __('Operations overview') }}</h2>
                                <span class="text-xs text-bucha-muted">{{ __('Rwanda') }}</span>
                            </div>
                            <div class="relative h-56 sm:h-64 bg-gradient-to-br from-sky-100/90 to-sky-50 flex items-center justify-center">
                                <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 60% 40%, #A11D1E 2px, transparent 2px); background-size: 24px 24px;"></div>
                                <div class="relative z-10 flex flex-col items-center gap-2 text-center px-4">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-bucha-primary text-white shadow-lg">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                    </span>
                                    <p class="text-sm font-medium text-slate-700">{{ __('Facilities & coverage') }}</p>
                                    <p class="text-xs text-bucha-muted">{{ __(':count facilities linked to your businesses', ['count' => $kpis['facilities'] ?? 0]) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-4 sm:p-5">
                            <h2 class="text-sm font-semibold text-slate-800 mb-1">{{ __('Certificate activity') }}</h2>
                            <p class="text-xs text-bucha-muted mb-4">{{ __('Last 6 months') }}</p>
                            <div class="h-52 sm:h-56">
                                <canvas id="chart-certificates" aria-label="{{ __('Certificates issued by month') }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Charts row --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-4 sm:p-5">
                            <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ __('Slaughter plans by month') }}</h3>
                            <div class="h-56">
                                <canvas id="chart-slaughter-plans" aria-label="{{ __('Slaughter plans by month') }}"></canvas>
                            </div>
                        </div>
                        <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-4 sm:p-5">
                            <h3 class="text-sm font-semibold text-slate-800 mb-4">{{ __('Batches & executions') }}</h3>
                            <div class="h-56">
                                <canvas id="chart-batches-executions" aria-label="{{ __('Batches and executions by month') }}"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-bucha bg-bucha-card border border-slate-200/80 shadow-bucha p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ __('Configure your workspace') }}</p>
                            <p class="text-sm text-bucha-muted mt-0.5">{{ __('Manage businesses, facilities, and traceability.') }}</p>
                        </div>
                        <a href="{{ route('businesses.hub') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-sm font-semibold shadow-bucha transition-colors shrink-0">
                            {{ __('Manage businesses') }}
                        </a>
                    </div>
                </div>

                @if($showAsideAlerts)
                    <aside class="space-y-4 xl:space-y-5">
                        <div class="rounded-bucha border border-bucha-primary/20 bg-bucha-primary/5 p-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-bucha-primary mb-2">{{ __('Alerts') }}</h3>
                            @if(($complianceSummary['pending_plans'] ?? 0) > 0)
                                <p class="text-sm text-slate-700">{{ __(':count slaughter plan(s) still in planned status.', ['count' => $complianceSummary['pending_plans']]) }}</p>
                            @endif
                            @if($incidents > 0 && ($complianceSummary['pending_plans'] ?? 0) === 0)
                                <p class="text-sm text-slate-700">{{ __('Review operations and compliance items in your modules.') }}</p>
                            @endif
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>

    <script>
        window.dashboardCharts = @json($charts);
    </script>
    @push('scripts')
        @vite('resources/js/dashboard-charts.js')
    @endpush
</x-app-layout>
