<x-app-layout>
    @php
        $periodOptions = [
            'all' => __('All time'),
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
        ];
        $activePeriod = $filters['period'] ?? 'all';
        $statusChart = collect($chartSpecs)->firstWhere('id', 'rica-cp-status-donut');
        $complianceChart = collect($chartSpecs)->firstWhere('id', 'rica-cp-compliance-line');
        $submissionChart = collect($chartSpecs)->firstWhere('id', 'rica-cp-submission-line');
        $overviewCharts = array_values(array_filter([
            $statusChart,
            $complianceChart,
            $submissionChart,
        ]));
        $totalReports = number_format($kpis['reports_submitted']['value'], 0);

        $sparklinePath = function (array $values): string {
            $values = array_values($values);
            if ($values === []) {
                return '';
            }

            $width = 56;
            $height = 20;
            $min = min($values);
            $max = max($values);
            $range = max(0.1, $max - $min);
            $count = count($values);
            $points = [];

            foreach ($values as $index => $value) {
                $x = $count === 1 ? $width / 2 : ($index / ($count - 1)) * $width;
                $y = $height - (($value - $min) / $range) * $height;
                $points[] = round($x, 1).','.round($y, 1);
            }

            return implode(' ', $points);
        };
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Compliance performance') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.compliance-performance') }}" class="rica-sc-toolbar">
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

                    <a href="{{ route('rica.monthly-reports.index') }}" class="rica-sc-export" title="{{ __('Export') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Export') }}
                    </a>
                </form>
            </header>

            <section class="rica-sc-kpi-grid rica-sc-kpi-grid--five" aria-label="{{ __('Key metrics') }}">
                @foreach ([
                    ['key' => 'active_pmis', 'label' => __('Active PMIs'), 'glyph' => 'users', 'format' => 'int', 'positiveIsGood' => true],
                    ['key' => 'reports_submitted', 'label' => __('Reports submitted'), 'glyph' => 'clipboard-list', 'format' => 'int', 'positiveIsGood' => true],
                    ['key' => 'submission_rate', 'label' => __('Submission rate'), 'glyph' => 'chart', 'format' => 'percent', 'positiveIsGood' => true],
                    ['key' => 'avg_compliance_score', 'label' => __('Avg compliance'), 'glyph' => 'shield', 'format' => 'percent', 'positiveIsGood' => true],
                    ['key' => 'avg_rejection_rate', 'label' => __('Avg rejection'), 'glyph' => 'alert', 'format' => 'percent', 'positiveIsGood' => false],
                ] as $index => $card)
                    @php
                        $metric = $kpis[$card['key']];
                        $trend = $metric['trend'];
                        $value = $card['format'] === 'percent'
                            ? number_format($metric['value'], 1).'%'
                            : number_format($metric['value']);
                        $trendPercent = (float) ($trend['percent'] ?? 0);
                        $isPointsTrend = str_contains((string) ($trend['label'] ?? ''), 'pp');
                        $showTrendPercent = $trend['direction'] !== 'neutral' && $trendPercent <= 200;
                        $trendClass = match (true) {
                            $trend['direction'] === 'neutral' => 'rica-sc-kpi-card__trend--neutral',
                            $card['positiveIsGood'] && $trend['direction'] === 'up', ! $card['positiveIsGood'] && $trend['direction'] === 'down' => 'rica-sc-kpi-card__trend--good',
                            default => 'rica-sc-kpi-card__trend--bad',
                        };
                    @endphp
                    <article class="rica-sc-kpi-card rica-sc-kpi-card--compact">
                        <div class="rica-sc-kpi-card__icon {{ $index % 2 === 1 ? 'rica-sc-kpi-card__icon--alt' : '' }}" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => $card['glyph']])
                        </div>
                        <div class="rica-sc-kpi-card__content">
                            <p class="rica-sc-kpi-card__label">{{ $card['label'] }}</p>
                            <p class="rica-sc-kpi-card__value">{{ $value }}</p>
                            <p class="rica-sc-kpi-card__trend {{ $trendClass }}">
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

            <section class="rica-sc-charts-row rica-sc-tables-row" aria-label="{{ __('Performance rankings') }}">
                <div class="rica-sc-tables rica-sc-tables--pair">
                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('Slaughterhouse ranking') }}</h2>
                        @if ($slaughterhouseRows === [])
                            <div class="rica-sc-empty">{{ __('No slaughterhouse performance data for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap">
                                <table class="rica-sc-table rica-sc-table--ranking">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Rank') }}</th>
                                            <th>{{ __('Name') }}</th>
                                            <th class="text-right">{{ __('Reports') }}</th>
                                            <th class="text-right">{{ __('Compliance') }}</th>
                                            <th class="text-right">{{ __('Rejection') }}</th>
                                            <th class="text-right">{{ __('Trend') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($slaughterhouseRows as $row)
                                            <tr>
                                                <td class="tabular-nums">{{ $row['rank'] }}</td>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['reports']) }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['compliance_score'], 1) }}%</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['rejection_rate'], 1) }}%</td>
                                                <td class="text-right">
                                                    <svg class="rica-sc-sparkline rica-sc-sparkline--{{ $row['sparkline']['direction'] }}" viewBox="0 0 56 20" aria-hidden="true">
                                                        <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $sparklinePath($row['sparkline']['values']) }}" />
                                                    </svg>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>

                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('PMI ranking') }}</h2>
                        @if ($pmiRows === [])
                            <div class="rica-sc-empty">{{ __('No PMI performance data for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap">
                                <table class="rica-sc-table rica-sc-table--ranking">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Rank') }}</th>
                                            <th>{{ __('Name') }}</th>
                                            <th class="text-right">{{ __('Reports') }}</th>
                                            <th class="text-right">{{ __('Compliance') }}</th>
                                            <th class="text-right">{{ __('Rejection') }}</th>
                                            <th class="text-right">{{ __('Trend') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pmiRows as $row)
                                            <tr>
                                                <td class="tabular-nums">{{ $row['rank'] }}</td>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['reports']) }}</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['compliance_score'], 1) }}%</td>
                                                <td class="text-right tabular-nums">{{ number_format($row['rejection_rate'], 1) }}%</td>
                                                <td class="text-right">
                                                    <svg class="rica-sc-sparkline rica-sc-sparkline--{{ $row['sparkline']['direction'] }}" viewBox="0 0 56 20" aria-hidden="true">
                                                        <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $sparklinePath($row['sparkline']['values']) }}" />
                                                    </svg>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                </div>
            </section>

            @if (count($overviewCharts) > 0)
                <section class="rica-sc-charts-row" aria-label="{{ __('Compliance trends') }}">
                    <x-workspace.chart-grid :charts="$overviewCharts" />
                </section>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.ricaComplianceChartSpecs = @json($chartSpecs);
            window.ricaComplianceDonutCenter = @json($totalReports);
        </script>
        @vite(['resources/js/rica-compliance-performance-charts.js'])
    @endpush
</x-app-layout>
