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
                $showLegend = ! $isPie && ! empty($chart['legend']);
                if ($isPie) {
                    $chartDataTotal = array_sum($chart['data'] ?? []);
                } elseif (! empty($chart['datasets'])) {
                    $chartDataTotal = array_sum(array_map(
                        fn (array $dataset) => array_sum($dataset['data'] ?? []),
                        $chart['datasets'],
                    ));
                } else {
                    $chartDataTotal = null;
                }
                $pieLegend = $isPie
                    ? collect($chart['legend'] ?? [])->map(function (array $item, int $index) use ($chart, $chartDataTotal) {
                        $value = (float) ($chart['data'][$index] ?? 0);
                        $percent = $chartDataTotal > 0 ? (int) round($value / $chartDataTotal * 100) : 0;

                        return array_merge($item, [
                            'value' => $value,
                            'percent' => $percent,
                        ]);
                    })->filter(fn (array $item) => $item['value'] > 0)->values()->all()
                    : [];
            @endphp
            <article @class(['proc-dash__chart-card', 'proc-dash__chart-card--full' => ! empty($chart['fullWidth'])])>
                <div class="proc-dash__chart-head">
                    <p class="proc-dash__chart-title">{{ $chart['title'] }}</p>
                    @if (! empty($chart['subtitle']))
                        <p class="proc-dash__chart-sub">{{ $chart['subtitle'] }}</p>
                    @endif
                </div>

                @if ($showLegend)
                    <div @class(['proc-dash__chart-legend', 'proc-dash__chart-legend--stacked' => $isStackedBar || $isTrendLine])>
                        @foreach ($chart['legend'] as $item)
                            <span class="proc-dash__legend-item">
                                <span class="proc-dash__legend-swatch" style="background: {{ $item['color'] }}"></span>
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($chartDataTotal === 0 || $chartDataTotal === 0.0)
                    <div class="proc-dash__chart-empty">{{ $chart['emptyMessage'] ?? __('No data for this period.') }}</div>
                @elseif ($isPie)
                    <div class="proc-dash__chart-pie">
                        <div class="proc-dash__chart-pie-canvas" style="height: {{ (int) ($chart['height'] ?? 220) }}px">
                            <canvas
                                id="{{ $chart['id'] }}"
                                role="img"
                                aria-label="{{ $chart['ariaLabel'] ?? $chart['title'] }}"
                            >{{ $chart['ariaLabel'] ?? $chart['title'] }}</canvas>
                        </div>
                        <div class="proc-dash__chart-pie-legend" aria-hidden="true">
                            @foreach ($pieLegend as $item)
                                <div class="proc-dash__chart-pie-legend-item">
                                    <div class="proc-dash__chart-pie-legend-row">
                                        <span class="proc-dash__chart-pie-legend-label">
                                            <span class="proc-dash__legend-swatch" style="background: {{ $item['color'] }}"></span>
                                            <span>{{ $item['label'] }}</span>
                                        </span>
                                        <span class="proc-dash__chart-pie-legend-value">
                                            {{ number_format($item['value'], $item['value'] == floor($item['value']) ? 0 : 1) }}
                                            <span class="proc-dash__chart-pie-legend-percent">{{ $item['percent'] }}%</span>
                                        </span>
                                    </div>
                                    <div class="proc-dash__chart-pie-legend-track">
                                        <span class="proc-dash__chart-pie-legend-fill" style="width: {{ min(100, (int) $item['percent']) }}%; background: {{ $item['color'] }}"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div @class([
                        'proc-dash__chart-wrap',
                        'proc-dash__chart-wrap--line' => $isTrendLine,
                        'proc-dash__chart-wrap--bar' => ! $isTrendLine,
                    ]) style="height: {{ (int) ($chart['height'] ?? 220) }}px">
                        <canvas
                            id="{{ $chart['id'] }}"
                            role="img"
                            aria-label="{{ $chart['ariaLabel'] ?? $chart['title'] }}"
                        >{{ $chart['ariaLabel'] ?? $chart['title'] }}</canvas>
                    </div>
                @endif
            </article>
        @endforeach
    </section>
@endif
