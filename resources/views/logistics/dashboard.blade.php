@php
    $companyQuery = $selectedCompanyId ? ['company_id' => $selectedCompanyId] : [];
    $a = $logisticsAnalytics;
    $fmtMoney = static fn (float $n): string => number_format($n, 0, '.', ' ');
    $fmtPct = static fn (?float $n): string => $n === null ? '—' : number_format($n, 1).'%';
    $fmtHours = static fn (?float $h): string => $h === null ? '—' : number_format($h, 2).' '.__('hrs');
@endphp

@component('layouts.logistics', [
    'pageTitle' => __('Dashboard'),
    'pageSubtitle' => __('Operational KPIs, trends, and resource insights for the selected company.'),
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-8">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-700">{{ __('Welcome, :name', ['name' => $user->name]) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Use one company context and move through modules without stale state.') }}</p>
        </div>

        @if (! $selectedCompanyId)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Select a logistics company from the workspace switcher to load dashboard analytics.') }}
            </div>
        @else
            {{-- KPI cards --}}
            <div class="space-y-6">
                <section aria-labelledby="logistics-kpi-orders">
                    <h2 id="logistics-kpi-orders" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Orders') }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 md:grid-cols-3">
                        <x-kpi-card stat title="{{ __('Total orders') }}" :value="$a['kpis']['orders_total']" :href="route('logistics.orders.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('In progress') }}" :value="$a['kpis']['orders_in_progress']" :href="route('logistics.orders.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Completed') }}" :value="$a['kpis']['orders_completed']" :href="route('logistics.orders.index', $companyQuery)" />
                    </div>
                </section>

                <section aria-labelledby="logistics-kpi-trips">
                    <h2 id="logistics-kpi-trips" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Trips') }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 md:grid-cols-3">
                        <x-kpi-card stat title="{{ __('Active trips') }}" :subtitle="__('Loaded or in transit')" :value="$a['kpis']['trips_active']" :href="route('logistics.trips.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Completed trips') }}" :value="$a['kpis']['trips_completed']" :href="route('logistics.trips.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Delayed trips') }}" :value="$a['kpis']['trips_delayed']" :href="route('logistics.trips.index', $companyQuery)" />
                    </div>
                </section>

                <section aria-labelledby="logistics-kpi-vehicles">
                    <h2 id="logistics-kpi-vehicles" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Vehicles') }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 lg:grid-cols-4">
                        <x-kpi-card stat title="{{ __('Total vehicles') }}" :value="$a['kpis']['vehicles_total']" :href="route('logistics.vehicles.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Active (on trips)') }}" :value="$a['kpis']['vehicles_active']" :href="route('logistics.trips.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Idle') }}" :value="$a['kpis']['vehicles_idle']" :href="route('logistics.vehicles.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Under maintenance') }}" :value="$a['kpis']['vehicles_maintenance']" :href="route('logistics.vehicles.index', $companyQuery)" />
                    </div>
                </section>

                <section aria-labelledby="logistics-kpi-drivers">
                    <h2 id="logistics-kpi-drivers" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Drivers') }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 md:grid-cols-3">
                        <x-kpi-card stat title="{{ __('Total drivers') }}" :value="$a['kpis']['drivers_total']" :href="route('logistics.drivers.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Active (on trips)') }}" :value="$a['kpis']['drivers_active']" :href="route('logistics.trips.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Available') }}" :value="$a['kpis']['drivers_available']" :href="route('logistics.drivers.index', $companyQuery)" />
                    </div>
                </section>

                <section aria-labelledby="logistics-kpi-financial">
                    <h2 id="logistics-kpi-financial" class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Financial') }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 md:grid-cols-2">
                        <x-kpi-card stat title="{{ __('Total revenue (paid)') }}" :value="$fmtMoney($a['kpis']['revenue_paid'])" :href="route('logistics.billing.index', $companyQuery)" />
                        <x-kpi-card stat title="{{ __('Pending payments') }}" :value="$fmtMoney($a['kpis']['revenue_pending'])" :href="route('logistics.billing.index', $companyQuery)" />
                    </div>
                </section>
            </div>

            {{-- Efficiency --}}
            <section aria-labelledby="logistics-efficiency-heading" class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                <h2 id="logistics-efficiency-heading" class="text-sm font-semibold text-slate-900">{{ __('Efficiency') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('Utilization uses vehicles and drivers on trips that are loaded or in transit.') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <x-kpi-card inline :title="__('Vehicle utilization')" :value="$fmtPct($a['efficiency']['vehicle_utilization_pct'])" />
                    <x-kpi-card inline :title="__('Driver utilization')" :value="$fmtPct($a['efficiency']['driver_utilization_pct'])" />
                    <x-kpi-card inline :title="__('Avg. trip duration')" :value="$fmtHours($a['efficiency']['avg_trip_duration_hours'])" :subtitle="__('Completed trips with actual times')" />
                    <x-kpi-card inline :title="__('On-time delivery')" :value="$fmtPct($a['efficiency']['on_time_delivery_pct'])" :subtitle="__('Actual vs planned arrival')" />
                </div>
            </section>

            {{-- Trends --}}
            <section aria-labelledby="logistics-trends-heading">
                <div class="mb-3">
                    <h2 id="logistics-trends-heading" class="text-sm font-semibold text-slate-900">{{ __('Trends') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Last :days days', ['days' => $a['trend_days']]) }}</p>
                </div>
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Orders trend') }}</p>
                        <div class="relative mt-3 h-56 w-full">
                            <canvas id="chart-orders-trend" aria-label="{{ __('Orders created over time') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Trips by status') }}</p>
                        <div class="relative mt-3 h-56 w-full">
                            <canvas id="chart-trips-trend" aria-label="{{ __('Trips by status over time') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Revenue (paid)') }}</p>
                        <div class="relative mt-3 h-56 w-full">
                            <canvas id="chart-revenue-trend" aria-label="{{ __('Paid invoice totals over time') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Vehicle utilization') }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ __('Daily overlap of loaded / in-transit trips vs fleet size.') }}</p>
                        <div class="relative mt-3 h-56 w-full">
                            <canvas id="chart-vehicle-utilization-trend" aria-label="{{ __('Active vs idle vehicles over time') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm xl:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Driver activity') }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ __('Drivers on loaded / in-transit trips vs available.') }}</p>
                        <div class="relative mt-3 h-56 w-full max-w-4xl">
                            <canvas id="chart-driver-activity-trend" aria-label="{{ __('Active vs available drivers over time') }}"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Resource insights --}}
            <section aria-labelledby="logistics-insights-heading" class="space-y-4">
                <div>
                    <h2 id="logistics-insights-heading" class="text-sm font-semibold text-slate-900">{{ __('Resource insights') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Fleet and crew workload signals') }}</p>
                </div>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Top vehicles by trips') }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">{{ __('Plate') }}</th>
                                        <th class="px-4 py-2 font-medium text-right">{{ __('Trips') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($a['insights']['top_vehicles'] as $row)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-slate-900">{{ $row->plate_number }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums text-slate-700">{{ number_format($row->trip_count) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No trip data yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Idle vehicles') }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ __('Not assigned to a loaded or in-transit trip.') }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">{{ __('Plate') }}</th>
                                        <th class="px-4 py-2 font-medium">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($a['insights']['idle_vehicles'] as $row)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-slate-900">{{ $row->plate_number }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ $row->status }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No idle vehicles, or fleet is empty.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Most active drivers') }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">{{ __('Driver') }}</th>
                                        <th class="px-4 py-2 font-medium text-right">{{ __('Trips') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($a['insights']['top_drivers'] as $row)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-slate-900">{{ $row->name }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums text-slate-700">{{ number_format($row->trip_count) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No trip data yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Drivers with delays / issues') }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ __('Delayed trips or late completions vs planned arrival.') }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">{{ __('Driver') }}</th>
                                        <th class="px-4 py-2 font-medium text-right">{{ __('Delayed') }}</th>
                                        <th class="px-4 py-2 font-medium text-right">{{ __('Late') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($a['insights']['drivers_with_issues'] as $row)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-slate-900">{{ $row->name }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums text-slate-700">{{ number_format($row->delayed_trips) }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums text-slate-700">{{ number_format($row->late_completions) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No delay signals in current data.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            @push('scripts')
                @vite('resources/js/dashboard-charts.js')
                <script>
                    window.dashboardCharts = @json($a['dashboard_charts']);
                </script>
            @endpush
        @endif

        <div>
            <h2 class="mb-2 text-sm font-semibold text-slate-900">{{ __('Modules') }}</h2>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <a data-logistics-nav href="{{ route('logistics.company.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Company') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage profile') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.vehicles.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Vehicles') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage fleet') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.drivers.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Drivers') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage drivers') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.orders.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Orders') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage requests') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.planning.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Trip Planning') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Allocate assets') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.trips.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Active Trips') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Execute delivery') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.tracking.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Tracking') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Log trip movements') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.compliance.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Compliance') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Attach required documents') }}</p>
                </a>
                <a data-logistics-nav href="{{ route('logistics.billing.index', $companyQuery) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Billing') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Generate invoices') }}</p>
                </a>
            </div>
        </div>
    </div>
@endcomponent
