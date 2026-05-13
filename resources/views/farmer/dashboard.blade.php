@php
    $fmtMoney = static fn (float|int|null $amount): string => number_format((float) ($amount ?? 0), 0);
    $fmtNum = static fn (float|int|null $value, int $decimals = 0): string => number_format((float) ($value ?? 0), $decimals);
    $priorityClasses = [
        'high' => 'border-red-200 bg-red-50/80 text-red-900',
        'medium' => 'border-amber-200 bg-amber-50/80 text-amber-950',
        'low' => 'border-slate-200 bg-slate-50/90 text-slate-800',
    ];
    $dashboardKpiSections = [
        [
            'sectionId' => 'farmer-kpi-operational',
            'title' => __('Operational KPIs'),
            'grid' => 'lg:grid-cols-4',
            'cards' => [
                ['glyph' => 'building', 'title' => __('Total farms'), 'value' => $operational['total_farms'], 'color' => 'blue', 'href' => route('farmer.farms.index')],
                ['glyph' => 'box', 'title' => __('Livestock groups'), 'value' => $operational['livestock_groups'], 'color' => 'slate', 'href' => route('farmer.livestock.index')],
                ['glyph' => 'tag', 'title' => __('Active animals'), 'value' => $operational['active_animals'], 'color' => 'bucha-success', 'href' => route('farmer.animals.index')],
                ['glyph' => 'truck', 'title' => __('Animals in transit'), 'value' => $operational['animals_in_transit'], 'color' => 'amber', 'href' => route('farmer.movement.animals.index')],
                ['glyph' => 'check', 'title' => __('Animals sold'), 'value' => $operational['animals_sold'], 'color' => 'blue', 'href' => route('farmer.sales.animals.index')],
                ['glyph' => 'certificate', 'title' => __('Active certificates'), 'value' => $operational['active_certificates'], 'color' => 'bucha-success', 'href' => route('farmer.certificates.animal-certificates.index')],
                ['glyph' => 'clipboard-list', 'title' => __('Active movement permits'), 'value' => $operational['active_movement_permits'], 'color' => 'slate', 'href' => route('farmer.movement.permits.index')],
                ['glyph' => 'users', 'title' => __('Total buyers'), 'value' => $operational['total_buyers'], 'color' => 'blue', 'href' => route('farmer.sales.buyers.index')],
            ],
        ],
        [
            'sectionId' => 'farmer-kpi-health',
            'title' => __('Health & compliance KPIs'),
            'grid' => 'lg:grid-cols-4',
            'cards' => [
                ['glyph' => 'shield', 'title' => __('Healthy animals'), 'value' => $healthCompliance['healthy_animals'], 'color' => 'bucha-success', 'href' => route('farmer.health.hub')],
                ['glyph' => 'alert', 'title' => __('Sick animals'), 'value' => $healthCompliance['sick_animals'], 'color' => 'bucha', 'href' => route('farmer.health.diseases.index')],
                ['glyph' => 'shield', 'title' => __('Quarantined animals'), 'value' => $healthCompliance['quarantined_animals'], 'color' => 'amber', 'href' => route('farmer.health.hub')],
                ['glyph' => 'intake', 'title' => __('Under treatment'), 'value' => $healthCompliance['under_treatment'], 'color' => 'amber', 'href' => route('farmer.health.treatments.index')],
                ['glyph' => 'calendar', 'title' => __('Vaccinations due'), 'value' => $healthCompliance['vaccinations_due'], 'color' => 'blue', 'href' => route('farmer.health.vaccinations.index')],
                ['glyph' => 'calendar', 'title' => __('Overdue vaccinations'), 'value' => $healthCompliance['overdue_vaccinations'], 'color' => 'bucha', 'href' => route('farmer.health.vaccinations.index')],
                ['glyph' => 'certificate', 'title' => __('Expired certificates'), 'value' => $healthCompliance['expired_certificates'], 'color' => 'bucha', 'href' => route('farmer.certificates.animal-certificates.index')],
                ['glyph' => 'clipboard', 'title' => __('Pending vet approvals'), 'value' => $healthCompliance['pending_vet_approvals'], 'color' => 'amber', 'href' => route('farmer.movement.hub')],
                ['glyph' => 'alert', 'title' => __('Mortality count'), 'value' => $healthCompliance['mortality_count'], 'color' => 'slate', 'href' => route('farmer.health.mortalities.index')],
            ],
        ],
        [
            'sectionId' => 'farmer-kpi-feeding',
            'title' => __('Feeding & inventory KPIs'),
            'grid' => 'lg:grid-cols-3 xl:grid-cols-6',
            'cards' => [
                ['glyph' => 'box', 'title' => __('Total feed inventory'), 'value' => $fmtNum($feedingInventory['total_stock'], 1), 'color' => 'blue', 'href' => route('farmer.feeding.inventory.index')],
                ['glyph' => 'alert', 'title' => __('Low feed stock alerts'), 'value' => $feedingInventory['low_stock_alerts'], 'color' => 'amber', 'href' => route('farmer.feeding.inventory.index')],
                ['glyph' => 'clock', 'title' => __('Feed expiring soon'), 'value' => $feedingInventory['feed_expiring_soon'], 'color' => 'amber', 'href' => route('farmer.feeding.inventory.index')],
                ['glyph' => 'chart', 'title' => __('Daily feed consumption'), 'value' => $fmtNum($feedingInventory['daily_usage'], 1), 'color' => 'slate', 'href' => route('farmer.feeding.records.index')],
                ['glyph' => 'currency', 'title' => __('Feed cost this month'), 'value' => $fmtMoney($feedingInventory['feed_cost_month']), 'color' => 'bucha', 'href' => route('farmer.feeding.hub')],
                ['glyph' => 'trending', 'title' => __('Most consumed feed'), 'value' => $feedingInventory['most_used_feed'] ?: '—', 'color' => 'bucha-success', 'href' => route('farmer.feeding.feed-types.index')],
            ],
        ],
        [
            'sectionId' => 'farmer-kpi-growth',
            'title' => __('Growth & weight KPIs'),
            'grid' => 'lg:grid-cols-3 xl:grid-cols-5',
            'cards' => [
                ['glyph' => 'weight', 'title' => __('Average animal weight'), 'value' => $growth['average_weight'] !== null ? $fmtNum($growth['average_weight'], 1).' '.__('kg') : __('Not tracked'), 'color' => 'blue', 'href' => route('farmer.animals.index')],
                ['glyph' => 'trending', 'title' => __('Average daily weight gain'), 'value' => $growth['average_daily_gain'] !== null ? $fmtNum($growth['average_daily_gain'], 2).' '.__('kg/day') : __('Not tracked'), 'color' => 'slate', 'href' => route('farmer.animals.index')],
                ['glyph' => 'check', 'title' => __('Market-ready animals'), 'value' => $growth['market_ready_animals'], 'color' => 'bucha-success', 'href' => route('farmer.animals.index')],
                ['glyph' => 'alert', 'title' => __('Below growth target'), 'value' => $growth['below_growth_target'], 'color' => 'amber', 'href' => route('farmer.animals.index')],
                ['glyph' => 'trending', 'title' => __('Fastest growing group'), 'value' => $growth['fastest_growing_group'], 'color' => 'blue', 'href' => route('farmer.livestock.index')],
            ],
        ],
        [
            'sectionId' => 'farmer-kpi-sales',
            'title' => __('Sales & financial KPIs'),
            'grid' => 'lg:grid-cols-4',
            'cards' => [
                ['glyph' => 'currency', 'title' => __('Total revenue'), 'value' => $fmtMoney($financial['revenue']), 'color' => 'bucha', 'href' => route('farmer.sales.hub')],
                ['glyph' => 'chart', 'title' => __('Monthly sales'), 'value' => $fmtMoney($financial['monthly_revenue']), 'color' => 'blue', 'href' => route('farmer.sales.records.index')],
                ['glyph' => 'tag', 'title' => __('Animals sold this month'), 'value' => $financial['animals_sold_month'], 'color' => 'bucha-success', 'href' => route('farmer.sales.animals.index')],
                ['glyph' => 'clock', 'title' => __('Pending payments'), 'value' => $financial['pending_payments'], 'color' => 'amber', 'href' => route('farmer.sales.payments.index')],
                ['glyph' => 'currency', 'title' => __('Outstanding balances'), 'value' => $fmtMoney($financial['outstanding_balance']), 'color' => 'amber', 'href' => route('farmer.sales.payments.index')],
                ['glyph' => 'users', 'title' => __('Top buyer'), 'value' => $financial['top_buyer'] ?: '—', 'color' => 'slate', 'href' => route('farmer.sales.buyers.index')],
                ['glyph' => 'currency', 'title' => __('Average sale price'), 'value' => $fmtMoney($financial['average_sale_price']), 'color' => 'blue', 'href' => route('farmer.sales.records.index')],
            ],
        ],
        [
            'sectionId' => 'farmer-kpi-traceability',
            'title' => __('Traceability KPIs'),
            'grid' => 'lg:grid-cols-4',
            'cards' => [
                ['glyph' => 'check', 'title' => __('Verified animals'), 'value' => $traceability['verified_animals'], 'color' => 'bucha-success', 'href' => route('farmer.certificates.logs.index')],
                ['glyph' => 'qrcode', 'title' => __('QR verification scans'), 'value' => $traceability['qr_verification_scans'], 'color' => 'blue', 'href' => route('farmer.certificates.logs.index')],
                ['glyph' => 'shield', 'title' => __('Public verification requests'), 'value' => $traceability['public_verification_requests'], 'color' => 'slate', 'href' => route('farmer.certificates.logs.index')],
                ['glyph' => 'certificate', 'title' => __('Active animal passports'), 'value' => $traceability['active_passports'], 'color' => 'bucha-success', 'href' => route('farmer.certificates.hub')],
                ['glyph' => 'chart', 'title' => __('Traceability compliance rate'), 'value' => $traceability['compliance_rate'].'%', 'color' => 'blue', 'href' => route('farmer.certificates.hub')],
                ['glyph' => 'certificate', 'title' => __('Verified certificates'), 'value' => $traceability['verified_certificates'], 'color' => 'slate', 'href' => route('farmer.certificates.animal-certificates.index')],
                ['glyph' => 'clipboard-list', 'title' => __('Verified movement permits'), 'value' => $traceability['verified_movement_permits'], 'color' => 'amber', 'href' => route('farmer.movement.logs.index')],
            ],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Operations command center') }}</span>
    </x-slot>

    <div class="space-y-8 max-w-[1600px]">
        <section class="rounded-bucha border border-slate-200/80 bg-white px-4 py-4 sm:px-6 sm:py-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        {{ __('Welcome, :name', ['name' => $user->name]) }}
                    </h1>
                    <p class="mt-1 text-sm text-bucha-muted">
                        {{ __('Monitor livestock operations, health, feeding, sales, and traceability from one workspace.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('farmer.animals.index') }}" class="inline-flex items-center gap-2 rounded-bucha border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'tag'])
                        {{ __('Animals') }}
                    </a>
                    <a href="{{ route('farmer.health.hub') }}" class="inline-flex items-center gap-2 rounded-bucha border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'shield'])
                        {{ __('Health') }}
                    </a>
                    <a href="{{ route('farmer.feeding.hub') }}" class="inline-flex items-center gap-2 rounded-bucha border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'box'])
                        {{ __('Feeding') }}
                    </a>
                    <a href="{{ route('farmer.sales.hub') }}" class="inline-flex items-center gap-2 rounded-bucha bg-bucha-primary px-3 py-2 text-sm font-semibold text-white transition hover:opacity-95">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'currency'])
                        {{ __('Sales') }}
                    </a>
                </div>
            </div>
        </section>

        @foreach ($dashboardKpiSections as $kpiSection)
            @include('farmer.dashboard.partials.kpi-section', $kpiSection)
        @endforeach

        <section aria-labelledby="farmer-charts">
            <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 id="farmer-charts" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Charts & analytics') }}</h2>
                    <p class="text-sm text-slate-600">{{ __('Lifecycle, sales, health, feeding, and traceability trends for operational decisions.') }}</p>
                </div>
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ([
                    'animal_lifecycle_trend' => __('Animal lifecycle trends'),
                    'sales_revenue_trend' => __('Sales revenue trends'),
                    'health_vaccination_trend' => __('Health vaccination trends'),
                    'health_mortality_trend' => __('Mortality trends'),
                    'feeding_usage_trend' => __('Feed consumption trends'),
                    'feeding_cost_trend' => __('Feed cost trends'),
                    'traceability_verification_trend' => __('Traceability verification trends'),
                    'movement_approval_trend' => __('Movement permit approvals'),
                ] as $chartId => $title)
                    <section class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <div class="mt-4 h-56">
                            <canvas id="chart-{{ str_replace('_', '-', $chartId) }}" aria-label="{{ $title }}"></canvas>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="farmer-alerts" class="rounded-bucha border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </span>
                    <div>
                        <h2 id="farmer-alerts" class="text-sm font-semibold text-slate-900">{{ __('Alerts & action center') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Prioritized operational follow-ups across health, movement, feeding, sales, and certificates.') }}</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                    {{ trans_choice(':count open alert|:count open alerts', count($alerts), ['count' => count($alerts)]) }}
                </span>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($alerts as $alert)
                    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide {{ $priorityClasses[$alert['priority']] ?? $priorityClasses['low'] }}">
                                    {{ ucfirst($alert['priority']) }}
                                </span>
                                <p class="text-sm font-semibold text-slate-900">{{ $alert['title'] }}</p>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $alert['detail'] }}</p>
                        </div>
                        <a href="{{ $alert['href'] }}" class="inline-flex shrink-0 items-center justify-center rounded-bucha border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-bucha-primary transition hover:border-bucha-primary/30 hover:bg-slate-50">
                            {{ $alert['action'] }}
                        </a>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-slate-500 sm:px-5">{{ __('No urgent actions right now. Keep monitoring recent activity below.') }}</p>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="farmer-activity" class="rounded-bucha border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bucha-primary/10 text-bucha-primary" aria-hidden="true">
                    @include('layouts.partials.sidebar-icon', ['icon' => 'clock'])
                </span>
                <div>
                    <h2 id="farmer-activity" class="text-sm font-semibold text-slate-900">{{ __('Recent activities') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Latest registrations, certificates, movements, sales, vaccinations, verifications, and buyer updates.') }}</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($activities as $activity)
                    <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $activity['title'] }}</p>
                            <p class="truncate text-sm text-slate-600">{{ $activity['detail'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 text-xs text-slate-500">
                            <time datetime="{{ $activity['at']?->toIso8601String() }}">{{ $activity['at']?->diffForHumans() }}</time>
                            <a href="{{ $activity['href'] }}" class="font-medium text-bucha-primary hover:underline">{{ __('Open') }}</a>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-slate-500 sm:px-5">{{ __('No recent activity yet. Operations will appear here as you register animals, issue certificates, and complete sales.') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    @vite('resources/js/dashboard-charts.js')
    <script>window.dashboardCharts = @json($charts);</script>
</x-app-layout>
