<x-app-layout>
    @php
        $periodOptions = [
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
            'all' => __('All time'),
        ];
        $activePeriod = $filters['period'] ?? 'all';
        $donutChart = collect($chartSpecs)->firstWhere('id', 'rica-sc-destination-donut');
        $usageChart = collect($chartSpecs)->firstWhere('id', 'rica-sc-certificate-usage');
        $certDestChart = collect($chartSpecs)->firstWhere('id', 'rica-sc-certificates-destination');
        $demandChart = collect($chartSpecs)->firstWhere('id', 'rica-sc-demand-supply');
        $totalDelivered = number_format($kpis['meat_delivered_kg']['value'], 0);
    @endphp

    <div class="rica-supply-chain proc-dash -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <a href="{{ route('rica.dashboard') }}" class="rica-sc-back">{{ __('← RICA') }}</a>

            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Supply chain & distribution dashboard') }}</h1>
                    <p class="rica-sc-header__subtitle">{{ __('Downstream distribution overview') }}</p>
                </div>

                <form method="get" action="{{ route('rica.supply-chain') }}" class="rica-sc-toolbar">
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
                    ['key' => 'meat_delivered_kg', 'label' => __('Meat delivered (kg)'), 'glyph' => 'weight', 'format' => 'decimal'],
                    ['key' => 'certificates_issued', 'label' => __('Certificates issued'), 'glyph' => 'certificate', 'format' => 'int'],
                    ['key' => 'destinations_served', 'label' => __('Destinations served'), 'glyph' => 'truck', 'format' => 'int'],
                    ['key' => 'compliance_rate', 'label' => __('Compliance rate'), 'glyph' => 'shield', 'format' => 'percent'],
                ] as $card)
                    @php
                        $metric = $kpis[$card['key']];
                        $trend = $metric['trend'];
                        $value = $card['format'] === 'decimal'
                            ? number_format($metric['value'], 0)
                            : ($card['format'] === 'percent'
                                ? number_format($metric['value'], 1).'%'
                                : number_format($metric['value']));
                    @endphp
                    <article class="rica-sc-kpi-card">
                        <div class="rica-sc-kpi-card__icon" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => $card['glyph']])
                        </div>
                        <div class="rica-sc-kpi-card__content">
                            <p class="rica-sc-kpi-card__label">{{ $card['label'] }}</p>
                            <p class="rica-sc-kpi-card__value">{{ $value }}</p>
                            <p class="rica-sc-kpi-card__trend">
                                @if ($trend['direction'] === 'up')
                                    <span aria-hidden="true">↑</span>
                                @elseif ($trend['direction'] === 'down')
                                    <span aria-hidden="true">↓</span>
                                @endif
                                {{ $trend['percent'] }}% {{ $trend['label'] }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="rica-sc-row" aria-label="{{ __('Distribution flow') }}">
                <article class="proc-dash__chart-card rica-sc-flow-card">
                    <h2 class="proc-dash__chart-title rica-sc-flow__title">{{ __('Meat flow (kg)') }}</h2>
                    <p class="rica-sc-flow__subtitle">{{ __('Slaughterhouses to destinations') }}</p>
                    <div id="rica-sc-flow-chart" class="rica-sc-flow">
                        @if (empty($flowData['links']))
                            <div class="rica-sc-empty">{{ __('No meat flow data for this period.') }}</div>
                        @endif
                    </div>
                    <div class="rica-sc-flow__legend" aria-hidden="true">
                        <span>{{ __('Lower flow') }}</span>
                        <span class="rica-sc-flow__legend-bar"></span>
                        <span>{{ __('Higher flow') }}</span>
                    </div>
                </article>

                @if ($donutChart)
                    <div class="rica-sc-chart-slot">
                        <x-workspace.chart-grid :charts="[$donutChart]" />
                    </div>
                @endif
            </section>

            <section class="rica-sc-row" aria-label="{{ __('Certificate analytics') }}">
                @if ($usageChart)
                    <div class="rica-sc-chart-slot">
                        <x-workspace.chart-grid :charts="[$usageChart]" />
                    </div>
                @endif
                @if ($certDestChart)
                    <div class="rica-sc-chart-slot">
                        <x-workspace.chart-grid :charts="[$certDestChart]" />
                    </div>
                @endif
            </section>

            <section class="rica-sc-row" aria-label="{{ __('Demand and geography') }}">
                @if ($demandChart)
                    <div class="rica-sc-chart-slot rica-sc-chart-slot--wide">
                        <x-workspace.chart-grid :charts="[$demandChart]" />
                    </div>
                @endif

                <article class="proc-dash__chart-card rica-sc-map-card">
                    <h2 class="proc-dash__chart-title">{{ __('Rwanda destinations map') }}</h2>
                    <div class="rica-sc-map">
                        <div class="rica-sc-map__grid">
                            @foreach ($districtMap as $district)
                                <div
                                    class="rica-sc-map__district @if ($district['kg'] <= 0) rica-sc-map__district--empty @endif"
                                    style="--intensity: {{ $district['intensity'] }};"
                                    title="{{ $district['name'] }}: {{ number_format($district['kg'], 0) }} kg"
                                >
                                    <span class="rica-sc-map__district-name">{{ $district['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="rica-sc-map__legend" aria-hidden="true">
                            <span>{{ __('Low') }}</span>
                            <span class="rica-sc-map__legend-bar"></span>
                            <span>{{ __('High') }}</span>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.ricaSupplyChainChartSpecs = @json($chartSpecs);
            window.ricaSupplyChainFlowData = @json($flowData);
            window.ricaSupplyChainDonutCenter = @json($totalDelivered);
            window.ricaSupplyChainFlowLabels = {
                origins: @json(__('Slaughterhouses')),
                destinations: @json(__('Destinations')),
            };
        </script>
        @vite(['resources/js/rica-supply-chain-charts.js'])
    @endpush
</x-app-layout>
