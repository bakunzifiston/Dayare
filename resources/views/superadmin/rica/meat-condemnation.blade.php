<x-app-layout>
    @php
        $periodOptions = [
            'all' => __('All time'),
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
        ];
        $activePeriod = $filters['period'] ?? 'month';
        $organChart = collect($chartSpecs)->firstWhere('id', 'rica-cond-organ-donut');
        $reasonChart = collect($chartSpecs)->firstWhere('id', 'rica-cond-reasons-bar');
        $speciesChart = collect($chartSpecs)->firstWhere('id', 'rica-cond-species-bar');
        $trendChart = collect($chartSpecs)->firstWhere('id', 'rica-cond-trend-line');
        $overviewCharts = array_values(array_filter([
            $organChart,
            $reasonChart,
            $speciesChart,
        ]));
        $organTotalKg = number_format(array_sum($organChart['data'] ?? []), 0);
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Meat condemnation') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.meat-condemnation') }}" class="rica-sc-toolbar">
                    <label class="rica-sc-select">
                        <span class="sr-only">{{ __('Period') }}</span>
                        <select name="period" onchange="this.form.requestSubmit()">
                            @foreach ($periodOptions as $periodKey => $periodLabel)
                                <option value="{{ $periodKey }}" @selected($activePeriod === $periodKey)>{{ $periodLabel }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="rica-sc-select">
                        <span class="sr-only">{{ __('District') }}</span>
                        <select name="district_id" onchange="this.form.requestSubmit()">
                            <option value="all" @selected($selectedDistrictId === null)>{{ __('All districts') }}</option>
                            @foreach ($districtOptions as $districtId => $districtName)
                                <option value="{{ $districtId }}" @selected((int) $selectedDistrictId === (int) $districtId)>{{ $districtName }}</option>
                            @endforeach
                        </select>
                    </label>

                    <a href="{{ route('rica.reports') }}" class="rica-sc-export" title="{{ __('Export') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Export') }}
                    </a>
                </form>
            </header>

            <section class="rica-sc-kpi-grid" aria-label="{{ __('Key metrics') }}">
                @foreach ([
                    ['key' => 'rejected_meat_kg', 'label' => __('Rejected meat'), 'glyph' => 'trash', 'format' => 'kg'],
                    ['key' => 'rejection_rate', 'label' => __('Rejection rate'), 'glyph' => 'chart', 'format' => 'percent'],
                    ['key' => 'economic_loss', 'label' => __('Estimated loss'), 'glyph' => 'certificate', 'format' => 'loss'],
                    ['key' => 'rejection_cases', 'label' => __('Rejection cases'), 'glyph' => 'clipboard-list', 'format' => 'int'],
                ] as $card)
                    @php
                        $metric = $kpis[$card['key']];
                        $trend = $metric['trend'];
                        $value = match ($card['format']) {
                            'kg' => number_format($metric['value'], 0).' kg',
                            'percent' => number_format($metric['value'], 2).'%',
                            'loss' => $metric['formatted'] ?? number_format($metric['value'], 0),
                            default => number_format($metric['value']),
                        };
                        $trendPercent = (float) ($trend['percent'] ?? 0);
                        $showTrendPercent = $trend['direction'] !== 'neutral' && $trendPercent <= 200;
                        $isPointsTrend = str_contains((string) ($trend['label'] ?? ''), 'pp');
                    @endphp
                    <article class="rica-sc-kpi-card rica-sc-kpi-card--compact">
                        <div class="rica-sc-kpi-card__icon" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => $card['glyph']])
                        </div>
                        <div class="rica-sc-kpi-card__content">
                            <p class="rica-sc-kpi-card__label">{{ $card['label'] }}</p>
                            <p class="rica-sc-kpi-card__value">{{ $value }}</p>
                            <p class="rica-sc-kpi-card__trend rica-sc-kpi-card__trend--bad">
                                @if ($trend['direction'] === 'up')
                                    <span aria-hidden="true">↑</span>
                                @elseif ($trend['direction'] === 'down')
                                    <span aria-hidden="true">↓</span>
                                @else
                                    <span>{{ __('No change') }}</span>
                                @endif
                                @if ($showTrendPercent)
                                    {{ number_format($trendPercent, $isPointsTrend ? 2 : ($trendPercent >= 10 ? 0 : 1)) }}{{ $isPointsTrend ? ' pp' : '%' }} {{ $trend['label'] }}
                                @elseif ($trend['direction'] !== 'neutral')
                                    {{ $trend['label'] }}
                                @endif
                            </p>
                        </div>
                    </article>
                @endforeach
            </section>

            @if (count($overviewCharts) > 0)
                <section class="rica-sc-charts-row" aria-label="{{ __('Rejection breakdown') }}">
                    <x-workspace.chart-grid :charts="$overviewCharts" />
                </section>
            @endif

            @if ($trendChart)
                <section class="rica-sc-charts-row rica-sc-charts-row--district" aria-label="{{ __('Rejection trend') }}">
                    <x-workspace.chart-grid :charts="[$trendChart]" />
                </section>
            @endif

            <section class="rica-sc-charts-row rica-sc-tables-row" aria-label="{{ __('Facilities and loss') }}">
                <div class="rica-sc-tables rica-sc-tables--pair">
                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('Rejection by slaughterhouse') }}</h2>
                        @if ($slaughterhouseRows === [])
                            <div class="rica-sc-empty">{{ __('No slaughterhouse rejections for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap">
                                <table class="rica-sc-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Slaughterhouse') }}</th>
                                            <th class="text-right">{{ __('Rejected (kg)') }}</th>
                                            <th class="text-right">{{ __('Rate') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($slaughterhouseRows as $row)
                                            <tr>
                                                <td>{{ $row['facility_name'] }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['rejected_kg'], 0) }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['rejection_rate'], 2) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>

                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('Top reasons by economic loss') }}</h2>
                        @if ($economicLossRows === [])
                            <div class="rica-sc-empty">{{ __('No economic loss data for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap">
                                <table class="rica-sc-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Reason') }}</th>
                                            <th class="text-right">{{ __('Loss') }}</th>
                                            <th class="text-right">{{ __('Share') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($economicLossRows as $row)
                                            <tr>
                                                <td>{{ $row['reason'] }}</td>
                                                <td class="text-right tabular-nums">{{ $row['formatted_loss'] }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['share'], 1) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.ricaCondemnationChartSpecs = @json($chartSpecs);
            window.ricaCondemnationDonutCenter = @json($organTotalKg);
        </script>
        @vite(['resources/js/rica-condemnation-charts.js'])
    @endpush
</x-app-layout>
