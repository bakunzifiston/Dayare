@props([
    'charts',
    'pair' => false,
])

@if (count($charts) > 0)
    <section @class(['proc-dash__charts', 'proc-dash__charts--pair' => $pair]) aria-label="{{ __('Charts') }}">
        @foreach ($charts as $chart)
            @php
                $chartType = $chart['type'] ?? 'bar';
                $isPie = $chartType === 'pie' || $chartType === 'donut';
                $isTrendLine = ! $isPie && $chartType === 'line';
                $isStackedBar = ! $isPie && ! empty($chart['stacked']);
                $showTrendLegend = ($isStackedBar || $isTrendLine) && ! empty($chart['legend']);
                if ($isPie) {
                    $chartDataTotal = array_sum($chart['data'] ?? []);
                } elseif (($isStackedBar || $isTrendLine) && ! empty($chart['datasets'])) {
                    $chartDataTotal = array_sum(array_map(
                        fn (array $dataset) => array_sum($dataset['data'] ?? []),
                        $chart['datasets'],
                    ));
                } else {
                    $chartDataTotal = null;
                }
                $pieLegend = $isPie
                    ? collect($chart['legend'] ?? [])->map(function (array $item, int $index) use ($chart, $chartDataTotal) {
                        $value = (int) ($chart['data'][$index] ?? 0);
                        $percent = $chartDataTotal > 0 ? (int) round($value / $chartDataTotal * 100) : 0;

                        return array_merge($item, [
                            'value' => $value,
                            'percent' => $percent,
                        ]);
                    })->filter(fn (array $item) => $item['value'] > 0)->values()->all()
                    : [];
            @endphp
            <div @class(['proc-dash__chart-card', 'proc-dash__chart-card--full' => ! empty($chart['fullWidth'])])>
                <p class="proc-dash__chart-title">{{ $chart['title'] }}</p>

                @if ($showTrendLegend)
                    <div class="proc-dash__chart-legend proc-dash__chart-legend--stacked">
                        @foreach ($chart['legend'] as $item)
                            <span class="proc-dash__legend-item">
                                <span class="proc-dash__legend-swatch" style="background: {{ $item['color'] }}"></span>
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($chartDataTotal === 0)
                    <div class="proc-dash__chart-empty">{{ $chart['emptyMessage'] ?? __('No data for this period.') }}</div>
                @elseif ($isPie)
                    <div class="proc-dash__chart-pie">
                        <div class="proc-dash__chart-pie-canvas" style="height: {{ (int) ($chart['height'] ?? 200) }}px">
                            <canvas
                                id="{{ $chart['id'] }}"
                                role="img"
                                aria-label="{{ $chart['ariaLabel'] ?? $chart['title'] }}"
                            >{{ $chart['ariaLabel'] ?? $chart['title'] }}</canvas>
                        </div>
                        <div class="proc-dash__chart-pie-legend" aria-hidden="true">
                            @foreach ($pieLegend as $item)
                                <div class="proc-dash__chart-pie-legend-item">
                                    <span class="proc-dash__chart-pie-legend-label">
                                        <span class="proc-dash__legend-swatch" style="background: {{ $item['color'] }}"></span>
                                        <span>{{ $item['label'] }}</span>
                                    </span>
                                    <span class="proc-dash__chart-pie-legend-value">
                                        {{ number_format($item['value']) }}
                                        <span class="proc-dash__chart-pie-legend-percent">({{ $item['percent'] }}%)</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div @class([
                        'proc-dash__chart-wrap',
                        'proc-dash__chart-wrap--line' => $isTrendLine,
                        'proc-dash__chart-wrap--bar' => ! $isTrendLine,
                    ]) style="height: {{ (int) ($chart['height'] ?? 200) }}px">
                        <canvas
                            id="{{ $chart['id'] }}"
                            role="img"
                            aria-label="{{ $chart['ariaLabel'] ?? $chart['title'] }}"
                        >{{ $chart['ariaLabel'] ?? $chart['title'] }}</canvas>
                    </div>
                @endif
            </div>
        @endforeach
    </section>
@endif
