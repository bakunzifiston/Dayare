<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Dashboard') }}</span>
    </x-slot>

    @php
        $roleLabels = [
            'org_admin' => __('Org Admin'),
            'operations_manager' => __('Operations Manager'),
            'compliance_officer' => __('Compliance Officer'),
            'inspector' => __('Inspector'),
            'transport_manager' => __('Transport Manager'),
        ];
        $trendLabels = [];
        $trendValues = [];
        foreach (($metrics ?? []) as $metric) {
            if (count($trendLabels) >= 6) {
                break;
            }
            $label = (string) ($metric['label'] ?? '');
            $rawValue = (string) ($metric['value'] ?? '0');
            $numeric = preg_replace('/[^0-9.\-]/', '', $rawValue) ?? '0';
            $trendLabels[] = $label !== '' ? $label : __('Metric');
            $trendValues[] = is_numeric($numeric) ? (float) $numeric : 0;
        }
    @endphp

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-6">
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            {{ __('Welcome, :name', ['name' => $user->name]) }}
                        </h1>
                        <p class="mt-1 text-sm text-bucha-muted">
                            {{ __('Business: :business • Role: :role', ['business' => $activeBusiness?->business_name ?? __('No active business selected'), 'role' => $roleLabels[$role ?? ''] ?? __('Unassigned')]) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-full sm:w-72">
                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"></path>
                                </svg>
                            </span>
                            <input type="text" placeholder="{{ __('Search dashboard') }}" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-300 focus:ring-0">
                        </div>
                    </div>
                </div>
            </section>

            @if (empty($metrics) && empty($alerts) && empty($quickActions))
                <div class="rounded-bucha bg-white border border-slate-200 p-6">
                    <p class="text-sm text-slate-700">
                        {{ __('No dashboard data is available for your current business context yet. Select an active business and ensure your role is assigned.') }}
                    </p>
                </div>
            @endif

            @if (! empty($metrics))
                <section class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        @foreach ($metrics as $metric)
                            <div class="rounded-bucha bg-white border border-slate-200 px-5 py-4 min-h-[132px] flex flex-col justify-between">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-bucha-muted">{{ $metric['label'] }}</p>
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-bucha-primary ring-1 ring-red-100">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16V8m5 8V5m5 11v-6"/>
                                        </svg>
                                    </span>
                                </div>
                                <p class="text-3xl font-bold leading-none text-slate-900">{{ $metric['value'] }}</p>
                                <p class="text-xs text-bucha-primary/80">{{ $metric['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <div class="xl:col-span-8 space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="rounded-bucha bg-white border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                                <h2 class="text-sm font-semibold text-slate-900">{{ __('Operations Map') }}</h2>
                                <p class="text-xs text-bucha-muted mt-0.5">{{ __('Facility and movement snapshot for active operations.') }}</p>
                            </div>
                            <div class="p-4">
                                <div class="relative h-60 w-full rounded-lg border border-slate-200 bg-slate-50 overflow-hidden">
                                    <svg viewBox="0 0 500 260" class="absolute inset-0 h-full w-full" fill="none" aria-label="{{ __('Operations map') }}">
                                        <path d="M32 144C76 84 136 52 206 62C281 72 339 54 405 84C436 98 460 126 468 158C442 190 406 216 350 222C294 228 238 208 178 212C120 216 76 206 36 178L32 144Z" fill="#e5e7eb"/>
                                        <path d="M84 154C114 122 148 106 188 110C229 114 260 102 296 118C329 132 360 144 402 152" stroke="#cbd5e1" stroke-width="8" stroke-linecap="round"/>
                                        <circle cx="146" cy="128" r="8" fill="#a11d1e"/>
                                        <circle cx="246" cy="140" r="8" fill="#a11d1e"/>
                                        <circle cx="332" cy="122" r="8" fill="#a11d1e"/>
                                        <circle cx="394" cy="160" r="8" fill="#a11d1e"/>
                                        <path d="M146 128L246 140L332 122L394 160" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4 5"/>
                                    </svg>
                                    <div class="absolute bottom-3 left-3 rounded-md border border-slate-200 bg-white/90 px-2 py-1 text-[11px] text-slate-600">
                                        {{ __(':facilities facilities • :routes active route(s)', ['facilities' => $mapSummary['facilities'] ?? 0, 'routes' => $mapSummary['active_routes'] ?? 0]) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-bucha bg-white border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                                <h2 class="text-sm font-semibold text-slate-900">{{ __('KPI Trend') }}</h2>
                                <p class="text-xs text-bucha-muted mt-0.5">{{ __('Current KPI distribution trend.') }}</p>
                            </div>
                            <div class="p-4">
                                <div class="relative h-60 w-full">
                                    <canvas id="chart-processor-kpi-trend" aria-label="{{ __('Processor KPI trend chart') }}"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (! empty($quickActions))
                        <div class="rounded-bucha bg-white border border-slate-200 p-4">
                            <h2 class="text-sm font-semibold text-slate-900">{{ __('Quick Actions') }}</h2>
                            <p class="text-xs text-bucha-muted mt-0.5">{{ __('Jump directly into core workflows.') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($quickActions as $action)
                                    <a href="{{ $action['url'] }}" class="inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-xs font-semibold">
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($alerts))
                        <div class="rounded-bucha bg-white border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                                <h2 class="text-sm font-semibold text-slate-900">{{ __('Alert Feed') }}</h2>
                                <p class="text-xs text-bucha-muted mt-0.5">{{ __('Each alert links to a fixable workflow.') }}</p>
                            </div>
                            <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-3">
                                @foreach ($alerts as $alert)
                                    <div class="rounded-lg bg-white border border-rose-200 p-4">
                                        <p class="text-sm font-semibold text-rose-700">{{ $alert['title'] }}</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $alert['count'] }}</p>
                                        <p class="mt-1 text-xs text-slate-600">{{ $alert['description'] }}</p>
                                        <a href="{{ route($alert['route']) }}" class="mt-3 inline-flex items-center text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                            {{ __('Fix now') }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="xl:col-span-4">
                    <div class="rounded-bucha bg-white border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70">
                            <h2 class="text-sm font-semibold text-slate-900">{{ __('Dashboard Status') }}</h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold text-slate-700">{{ __('Active Business') }}</p>
                                <p class="text-sm text-slate-900 mt-1">{{ $activeBusiness?->business_name ?? __('Not selected') }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold text-slate-700">{{ __('Current Role') }}</p>
                                <p class="text-sm text-slate-900 mt-1">{{ $roleLabels[$role ?? ''] ?? __('Unassigned') }}</p>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                <p class="text-xs font-semibold text-emerald-900">{{ __('Workspace') }}</p>
                                <p class="text-sm text-emerald-900 mt-1">{{ __('Processor') }}</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>

    @if (! empty($trendLabels))
        @push('scripts')
            @vite('resources/js/dashboard-charts.js')
            <script>
                window.dashboardCharts = {
                    processor_kpi_trend: {
                        type: 'line',
                        labels: @json($trendLabels),
                        datasets: [
                            {
                                label: @json(__('KPI value')),
                                data: @json($trendValues),
                                fill: true,
                            }
                        ],
                        yTickPrecision: 0
                    }
                };
            </script>
        @endpush
    @endif
</x-app-layout>
