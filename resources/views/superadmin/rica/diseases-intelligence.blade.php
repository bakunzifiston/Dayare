<x-app-layout>
    @php
        $periodOptions = [
            'all' => __('All time'),
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
        ];
        $activePeriod = $filters['period'] ?? 'all';
        $topDiseasesChart = collect($chartSpecs)->firstWhere('id', 'rica-di-top-diseases-bar');
        $speciesChart = collect($chartSpecs)->firstWhere('id', 'rica-di-species-donut');
        $seasonalChart = collect($chartSpecs)->firstWhere('id', 'rica-di-seasonal-risk');
        $trendChart = collect($chartSpecs)->firstWhere('id', 'rica-di-trend-line');
        $overviewCharts = array_values(array_filter([
            $topDiseasesChart,
            $speciesChart,
            $seasonalChart,
        ]));
        $totalCases = number_format($kpis['disease_cases']['value'], 0);
        $districtMapHasData = collect($districtMap ?? [])->contains(fn (array $district) => ($district['count'] ?? 0) > 0);
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Disease intelligence') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.diseases-intelligence') }}" class="rica-sc-toolbar">
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

                    <label class="rica-sc-select">
                        <span class="sr-only">{{ __('Species') }}</span>
                        <select name="species" onchange="this.form.requestSubmit()">
                            @foreach ($speciesOptions as $speciesKey => $speciesLabel)
                                <option value="{{ $speciesKey }}" @selected(($selectedSpecies === null && $speciesKey === 'all') || $selectedSpecies === $speciesKey)>{{ $speciesLabel }}</option>
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
                    ['key' => 'unhealthy_animals', 'label' => __('Unhealthy animals'), 'glyph' => 'alert', 'countTrend' => false],
                    ['key' => 'disease_cases', 'label' => __('Disease cases'), 'glyph' => 'clipboard-list', 'countTrend' => false],
                    ['key' => 'diseases_detected', 'label' => __('Diseases detected'), 'glyph' => 'shield', 'countTrend' => true],
                    ['key' => 'districts_affected', 'label' => __('Districts affected'), 'glyph' => 'building', 'countTrend' => true],
                ] as $card)
                    @php
                        $metric = $kpis[$card['key']];
                        $trend = $metric['trend'];
                        $value = number_format($metric['value']);
                        $trendPercent = (float) ($trend['percent'] ?? 0);
                        $showTrendPercent = ! $card['countTrend'] && $trend['direction'] !== 'neutral' && $trendPercent <= 200;
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
                                @if ($card['countTrend'] && $trend['direction'] !== 'neutral')
                                    {{ number_format($trendPercent, 0) }} {{ $trend['label'] }}
                                @elseif ($showTrendPercent)
                                    {{ number_format($trendPercent, $trendPercent >= 10 ? 0 : 1) }}% {{ $trend['label'] }}
                                @elseif ($trend['direction'] !== 'neutral')
                                    {{ $trend['label'] }}
                                @endif
                            </p>
                        </div>
                    </article>
                @endforeach
            </section>

            @if (count($overviewCharts) > 0)
                <section class="rica-sc-charts-row" aria-label="{{ __('Disease breakdown') }}">
                    <x-workspace.chart-grid :charts="$overviewCharts" />
                </section>
            @endif

            @if ($trendChart)
                <section class="rica-sc-charts-row rica-sc-charts-row--district" aria-label="{{ __('Disease trend') }}">
                    <x-workspace.chart-grid :charts="[$trendChart]" />
                </section>
            @endif

            <section class="rica-sc-charts-row rica-sc-charts-row--district" aria-label="{{ __('Diseases by district') }}">
                <article class="proc-dash__chart-card rica-sc-map-card">
                    <h2 class="proc-dash__chart-title">{{ __('Diseases by district') }}</h2>
                    @if (! $districtMapHasData)
                        <div class="rica-sc-empty">{{ __('No disease cases by district for this period.') }}</div>
                    @else
                        <div class="rica-sc-map">
                            <div class="rica-sc-map__grid">
                                @foreach ($districtMap as $district)
                                    <div
                                        class="rica-sc-map__district @if ($district['count'] <= 0) rica-sc-map__district--empty @endif"
                                        style="--intensity: {{ $district['intensity'] }};"
                                        title="{{ $district['name'] }}: {{ number_format($district['count']) }} {{ __('cases') }}"
                                    >
                                        <span class="rica-sc-map__district-name">{{ $district['name'] }}</span>
                                        @if ($district['count'] > 0)
                                            <span class="rica-sc-map__district-value">{{ number_format($district['count']) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="rica-sc-map__legend" aria-hidden="true">
                                <span>{{ __('Low') }}</span>
                                <span class="rica-sc-map__legend-bar"></span>
                                <span>{{ __('High') }}</span>
                            </div>
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.ricaDiseaseChartSpecs = @json($chartSpecs);
            window.ricaDiseaseDonutCenter = @json($totalCases);
        </script>
        @vite(['resources/js/rica-disease-intelligence-charts.js'])
    @endpush
</x-app-layout>
