<x-app-layout>
    @php
        $periodOptions = [
            'all' => __('All time'),
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
        ];
        $activePeriod = $filters['period'] ?? 'all';
        $lineChart = collect($chartSpecs)->firstWhere('id', 'rica-overview-slaughtered-line');
        $speciesChart = collect($chartSpecs)->firstWhere('id', 'rica-overview-species-donut');
        $slaughterSpeciesChart = collect($chartSpecs)->firstWhere('id', 'rica-overview-slaughter-species-trend');
        $overviewCharts = array_values(array_filter([
            $slaughterSpeciesChart,
            $lineChart,
            $speciesChart,
        ]));
        $totalReceived = number_format($kpis['animals_received']['value'], 0);
        $districtMapHasData = collect($districtMap ?? [])->contains(fn (array $district) => ($district['count'] ?? 0) > 0);
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ $pageTitle ?? __('National Meat Inspection Overview') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.dashboard') }}" class="rica-sc-toolbar">
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
                        <span class="sr-only">{{ __('Slaughterhouse') }}</span>
                        <select name="facility_id" onchange="this.form.requestSubmit()">
                            <option value="all" @selected($selectedFacilityId === null)>{{ __('All slaughterhouses') }}</option>
                            @foreach ($facilityOptions as $facilityId => $facilityName)
                                <option value="{{ $facilityId }}" @selected((int) $selectedFacilityId === (int) $facilityId)>{{ $facilityName }}</option>
                            @endforeach
                        </select>
                    </label>

                    <a href="{{ route('rica.reports') }}" class="rica-sc-export" title="{{ __('Export') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Export') }}
                    </a>
                </form>
            </header>

            <section class="rica-sc-kpi-grid" aria-label="{{ __('Executive KPIs') }}">
                @foreach ([
                    ['key' => 'animals_received', 'label' => __('Animals received'), 'glyph' => 'clipboard-list', 'format' => 'int'],
                    ['key' => 'active_pmis', 'label' => __('Total active inspectors'), 'glyph' => 'users', 'format' => 'int'],
                    ['key' => 'meat_rejected_kg', 'label' => __('Rejected meat'), 'glyph' => 'trash', 'format' => 'kg'],
                    ['key' => 'active_slaughterhouses', 'label' => __('Active slaughterhouses'), 'glyph' => 'building', 'format' => 'int'],
                ] as $card)
                    @php
                        $metric = $kpis[$card['key']];
                        $trend = $metric['trend'];
                        $value = $card['format'] === 'kg'
                            ? number_format($metric['value'], 0).' kg'
                            : number_format($metric['value']);
                        $trendClass = match ($trend['sentiment'] ?? 'neutral') {
                            'good' => 'rica-sc-kpi-card__trend--good',
                            'bad' => 'rica-sc-kpi-card__trend--bad',
                            default => 'rica-sc-kpi-card__trend--neutral',
                        };
                        $trendPercent = (float) ($trend['percent'] ?? 0);
                        $showTrendPercent = $trend['direction'] !== 'neutral' && $trendPercent <= 200;
                    @endphp
                    <article class="rica-sc-kpi-card rica-sc-kpi-card--compact">
                        <div class="rica-sc-kpi-card__icon" aria-hidden="true">
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
                                    {{ number_format($trendPercent, $trendPercent >= 10 ? 0 : 1) }}% {{ $trend['label'] }}
                                @elseif ($trend['direction'] !== 'neutral')
                                    {{ $trend['label'] }}
                                @endif
                            </p>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="rica-sc-module-summary" aria-label="{{ __('RICA module summary') }}">
                <div class="rica-sc-kpi-grid rica-sc-kpi-grid--three">
                    @foreach ($moduleSummaries as $index => $module)
                        <a href="{{ $module['href'] }}" class="rica-sc-kpi-card rica-sc-kpi-card--link rica-sc-kpi-card--compact">
                            <div class="rica-sc-kpi-card__icon {{ $index % 2 === 1 ? 'rica-sc-kpi-card__icon--alt' : '' }}" aria-hidden="true">
                                @include('layouts.partials.sidebar-icon', ['icon' => $module['glyph']])
                            </div>
                            <div class="rica-sc-kpi-card__content">
                                <p class="rica-sc-kpi-card__label">{{ $module['title'] }}</p>
                                <p class="rica-sc-kpi-card__value">{{ $module['metric_value'] }}</p>
                                <p class="rica-sc-kpi-card__meta">{{ $module['metric_label'] }}</p>
                                <p class="rica-sc-kpi-card__trend rica-sc-kpi-card__trend--link">{{ __('Open') }} →</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            @if (count($overviewCharts) > 0)
                <section class="rica-sc-charts-row" aria-label="{{ __('Inspection analytics') }}">
                    <x-workspace.chart-grid :charts="$overviewCharts" />
                </section>
            @endif

            <section class="rica-sc-charts-row rica-sc-charts-row--district" aria-label="{{ __('Animals by district') }}">
                <article class="proc-dash__chart-card rica-sc-map-card">
                    <h2 class="proc-dash__chart-title">{{ __('Animals by district') }}</h2>
                    @if (! $districtMapHasData)
                        <div class="rica-sc-empty">{{ __('No animal intake by district for this period.') }}</div>
                    @else
                        <div class="rica-sc-map">
                            <div class="rica-sc-map__grid">
                                @foreach ($districtMap as $district)
                                    <div
                                        class="rica-sc-map__district @if ($district['count'] <= 0) rica-sc-map__district--empty @endif"
                                        style="--intensity: {{ $district['intensity'] }};"
                                        title="{{ $district['name'] }}: {{ number_format($district['count']) }} {{ __('animals') }}"
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

            <section class="rica-sc-charts-row rica-sc-tables-row" aria-label="{{ __('Slaughterhouse activity') }}">
                <div class="rica-sc-tables rica-sc-tables--pair">
                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('Animals received by slaughterhouse') }}</h2>
                        @if ($animalsReceivedRows === [])
                            <div class="rica-sc-empty">{{ __('No animal intake for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap rica-sc-table-wrap--compact">
                                <table class="rica-sc-table rica-sc-table--compact">
                                    <thead>
                                        <tr>
                                            <th class="rica-sc-table__rank">#</th>
                                            <th>{{ __('Facility') }}</th>
                                            <th class="text-right">{{ __('Animals') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($animalsReceivedRows as $index => $row)
                                            <tr>
                                                <td class="rica-sc-table__rank tabular-nums">{{ $index + 1 }}</td>
                                                <td>
                                                    <span class="rica-sc-table__name" title="{{ $row['facility_name'] }}">{{ $row['facility_name'] }}</span>
                                                </td>
                                                <td class="text-right tabular-nums rica-sc-table__value">{{ number_format($row['animals']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>

                    <article class="proc-dash__chart-card">
                        <h2 class="proc-dash__chart-title">{{ __('Animals slaughtered by slaughterhouse') }}</h2>
                        @if ($animalsSlaughteredRows === [])
                            <div class="rica-sc-empty">{{ __('No slaughter data for this period.') }}</div>
                        @else
                            <div class="rica-sc-table-wrap rica-sc-table-wrap--compact">
                                <table class="rica-sc-table rica-sc-table--compact">
                                    <thead>
                                        <tr>
                                            <th class="rica-sc-table__rank">#</th>
                                            <th>{{ __('Facility') }}</th>
                                            <th class="text-right">{{ __('Animals') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($animalsSlaughteredRows as $index => $row)
                                            <tr>
                                                <td class="rica-sc-table__rank tabular-nums">{{ $index + 1 }}</td>
                                                <td>
                                                    <span class="rica-sc-table__name" title="{{ $row['facility_name'] }}">{{ $row['facility_name'] }}</span>
                                                </td>
                                                <td class="text-right tabular-nums rica-sc-table__value">{{ number_format($row['animals']) }}</td>
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
            window.ricaOverviewChartSpecs = @json($chartSpecs);
            window.ricaOverviewDonutCenter = @json($totalReceived);
        </script>
        @vite(['resources/js/rica-overview-charts.js'])
    @endpush
</x-app-layout>
