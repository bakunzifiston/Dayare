@php
    $business = $business ?? $businesses->first();
    $charts = $charts ?? ['today' => [], 'overview' => []];
    $activeCharts = array_values($charts['today'] ?? []);
    $filters = $filters ?? [
        'period' => 'all',
        'from_input' => '',
        'to_input' => '',
        'range_label' => __('All time'),
        'all_time' => true,
    ];
    $filterQuery = array_filter([
        'period' => ($filters['period'] ?? 'all') === 'all' ? null : ($filters['period'] ?? null),
        'from' => $filters['from_input'] ?? null,
        'to' => $filters['to_input'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<x-app-layout>
    @if ($business && $today)
        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
            <script>
                window.buchaChartColors = @json(config('bucha.chart'));
                window.processorDashboardActiveRole = 'butcher';
                window.processorDashboardCharts = {
                    butcher: @json($activeCharts)
                };
            </script>
            @vite('resources/js/processor-dashboard.js')
        @endpush
    @endif

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($business && $today)
                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-4 sm:px-5 shadow-sm space-y-4">
                    <div>
                        <p class="text-lg font-semibold text-slate-900">
                            {{ $greeting }}, {{ $business->business_name }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ $today_date }}</p>
                    </div>

                    <form method="get" action="{{ route('butcher.dashboard') }}" class="hub-period-filter !border-0 !bg-transparent !p-0 !shadow-none">
                        <div class="hub-period-filter__bar">
                            <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Date range') }}">
                                @foreach ([
                                    'all' => __('All time'),
                                    'today' => __('Today'),
                                    '7d' => __('7 days'),
                                    '30d' => __('30 days'),
                                    'month' => __('This month'),
                                ] as $periodKey => $periodLabel)
                                    <a
                                        href="{{ route('butcher.dashboard', array_filter([
                                            'period' => $periodKey === 'all' ? null : $periodKey,
                                        ])) }}"
                                        @class([
                                            'inline-flex rounded-md px-3 py-1.5 text-xs font-medium transition',
                                            'bg-bucha-primary text-white shadow-sm' => ($filters['period'] ?? 'all') === $periodKey,
                                            'text-slate-600 hover:text-slate-900 hover:bg-white' => ($filters['period'] ?? 'all') !== $periodKey,
                                        ])
                                    >{{ $periodLabel }}</a>
                                @endforeach
                            </div>

                            <div class="hub-period-filter__range">
                                <label for="dash_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                                <input id="dash_from" type="date" name="from" value="{{ $filters['from_input'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                                <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                                <label for="dash_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                                <input id="dash_to" type="date" name="to" value="{{ $filters['to_input'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                            </div>

                            <div class="hub-period-filter__actions">
                                <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                                <a href="{{ route('butcher.dashboard') }}" class="hub-period-filter__clear">{{ __('Reset') }}</a>
                            </div>
                        </div>
                        <p class="hub-period-filter__hint">{{ __('Showing') }} · {{ $filters['range_label'] }}</p>
                    </form>
                </div>

                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Performance') }}</h3>
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <x-butcher.kpi-card
                            :label="__('Sales')"
                            :value="$today['sales_count']['value']"
                            :trend="$today['sales_count']['trend']"
                            :trend-text="$today['sales_count']['trend_text']"
                            tone="bucha"
                            icon="ti ti-receipt"
                        />
                        <x-butcher.kpi-card
                            :label="__('Revenue')"
                            :value="$today['revenue']['value']"
                            :trend="$today['revenue']['trend']"
                            :trend-text="$today['revenue']['trend_text']"
                            tone="emerald"
                            icon="ti ti-cash"
                        />
                        <x-butcher.kpi-card
                            :label="__('Receiving')"
                            :value="$today['receiving']['value']"
                            :subtext="$today['receiving']['subtext']"
                            tone="sky"
                            icon="ti ti-truck-delivery"
                        />
                        <x-butcher.kpi-card
                            :label="__('Open orders')"
                            :value="$today['open_orders']['value']"
                            :subtext="$today['open_orders']['subtext']"
                            tone="indigo"
                            icon="ti ti-clipboard-list"
                        />
                        <x-butcher.kpi-card
                            :label="__('Expiring soon')"
                            :value="$today['expiring_soon']['value']"
                            :subtext="$today['expiring_soon']['subtext']"
                            :color="$today['expiring_soon']['color']"
                            icon="ti ti-clock-exclamation"
                        />
                        <x-butcher.kpi-card
                            :label="__('Temp breaches')"
                            :value="$today['temperature_breaches']['value']"
                            :subtext="$today['temperature_breaches']['subtext']"
                            :color="$today['temperature_breaches']['color']"
                            icon="ti ti-temperature"
                        />
                        <x-butcher.kpi-card
                            :label="__('Hygiene log')"
                            :value="$today['hygiene_log']['value']"
                            :subtext="$today['hygiene_log']['subtext']"
                            :color="$today['hygiene_log']['color'] ?? null"
                            tone="teal"
                            icon="ti ti-clipboard-check"
                        />
                        <x-butcher.kpi-card
                            :label="__('Credit outstanding')"
                            :value="$today['credit_outstanding']['value']"
                            :subtext="$today['credit_outstanding']['subtext']"
                            :color="$today['credit_outstanding']['color']"
                            icon="ti ti-credit-card"
                        />
                    </div>
                </section>

                @if (count($activeCharts) > 0)
                    <section aria-label="{{ __('Analytics') }}">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Analytics') }}</h3>
                        <x-workspace.chart-grid :charts="$activeCharts" pair />
                    </section>
                @endif

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-stretch">
                    <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm h-full">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Active alerts') }}</h3>
                        <ul class="mt-4 space-y-2 max-h-[22rem] overflow-y-auto">
                            @forelse ($alerts as $alert)
                                @php
                                    $border = match ($alert['level']) {
                                        'danger' => 'border-l-red-500',
                                        'warning' => 'border-l-amber-500',
                                        default => 'border-l-blue-500',
                                    };
                                    $dot = match ($alert['level']) {
                                        'danger' => 'bg-red-500',
                                        'warning' => 'bg-amber-500',
                                        default => 'bg-blue-500',
                                    };
                                @endphp
                                <li class="flex items-start gap-3 rounded-lg border border-slate-100 border-l-4 {{ $border }} bg-slate-50/80 px-3 py-2.5 text-sm text-slate-700">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                                    <span>{{ $alert['message'] }}</span>
                                </li>
                            @empty
                                <li class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-500">
                                    {{ __('No active alerts.') }}
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden h-full flex flex-col">
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent sales') }}</h3>
                            <span class="text-xs text-slate-500">
                                {{ __('Kg sold') }}: {{ $today['kg_sold']['value'] }} · {{ __('Avg') }}: {{ $today['avg_sale_value']['value'] }}
                            </span>
                        </div>
                        <div class="overflow-x-auto max-h-[22rem] overflow-y-auto flex-1">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/80 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                                        <th class="px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                                        <th class="hidden xl:table-cell px-4 py-3 sm:px-5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Item') }}</th>
                                        <th class="px-4 py-3 sm:px-5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Amount') }}</th>
                                        <th class="px-4 py-3 sm:px-5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Time') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($recent_sales as $sale)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $sale['number'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-700">{{ $sale['customer'] }}</td>
                                            <td class="hidden xl:table-cell max-w-[10rem] truncate px-4 py-3 sm:px-5 text-slate-600" title="{{ $sale['item'] }}">{{ $sale['item'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right tabular-nums font-medium text-slate-900">{{ number_format($sale['amount'], 0) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-right tabular-nums text-slate-600">{{ $sale['time'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">{{ __('No sales recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @else
                <div class="rounded-xl border border-slate-200/80 bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-slate-600">{{ __('No butcher business is linked to this account yet.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
