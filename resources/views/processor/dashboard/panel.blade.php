@php
    $badgeVariants = [
        'healthy' => 'success',
        'on_track' => 'info',
        'warning' => 'warning',
        'action_needed' => 'danger',
        'green' => 'success',
        'amber' => 'warning',
        'blue' => 'info',
        'red' => 'danger',
        'slate' => 'neutral',
    ];
    $dotVariants = [
        'red' => 'danger',
        'amber' => 'warning',
        'blue' => 'info',
    ];
    $headerBadge = $ops['headerBadge'] ?? null;
    $left = $ops['leftPanel'] ?? [];
    $right = $ops['rightPanel'] ?? [];
    $charts = $ops['charts'] ?? [];
@endphp

<div class="proc-dash__header">
    <div>
        <p class="proc-dash__title">{{ $panelTitle ?? __('Processor dashboard') }}</p>
        @if (! empty($panelMeta))
            <p class="proc-dash__meta">{{ $panelMeta }}</p>
        @endif
    </div>
    @if ($headerBadge)
        <span class="proc-dash__badge proc-dash__badge--{{ $headerBadge['variant'] ?? 'neutral' }}">
            {{ $headerBadge['label'] }}
        </span>
    @endif
</div>

<section class="proc-dash__kpis" aria-label="{{ __('Key performance indicators') }}">
    @foreach ($ops['kpiCards'] as $card)
        @php
            $rawValue = $card['value'];
            $displayValue = is_int($rawValue) || (is_string($rawValue) && ctype_digit($rawValue))
                ? number_format((int) $rawValue)
                : (string) $rawValue;
            $valueClass = strlen($displayValue) > 10 ? ' proc-dash__kpi-value--compact' : '';
        @endphp
        <article class="proc-dash__kpi">
            <span class="proc-dash__kpi-label">{{ $card['label'] }}</span>
            <span class="proc-dash__kpi-value{{ $valueClass }}">{{ $displayValue }}</span>
            @if (! empty($card['change']))
                <span class="proc-dash__kpi-delta proc-dash__kpi-delta--{{ $card['deltaTone'] ?? 'info' }}" title="{{ $card['change'] }}">{{ $card['change'] }}</span>
            @endif
        </article>
    @endforeach
</section>

@if (! empty($ops['insight']))
    <div class="proc-dash__insight" role="note">
        <i class="ti ti-bulb" aria-hidden="true"></i>
        <p style="margin:0;">{{ $ops['insight'] }}</p>
    </div>
@endif

@if (count($charts) > 0)
    <section class="proc-dash__charts" aria-label="{{ __('Charts') }}">
        @foreach ($charts as $chart)
            <div class="proc-dash__chart-card">
                <p class="proc-dash__chart-title">{{ $chart['title'] }}</p>
                @if (! empty($chart['legend']))
                    <div class="proc-dash__chart-legend">
                        @foreach ($chart['legend'] as $item)
                            <span class="proc-dash__legend-item">
                                <span class="proc-dash__legend-swatch" style="background: {{ $item['color'] }}"></span>
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
                <div class="proc-dash__chart-wrap" style="position: relative; height: {{ (int) ($chart['height'] ?? 160) }}px">
                    <canvas
                        id="{{ $chart['id'] }}"
                        role="img"
                        aria-label="{{ $chart['ariaLabel'] ?? $chart['title'] }}"
                    >{{ $chart['ariaLabel'] ?? $chart['title'] }}</canvas>
                </div>
            </div>
        @endforeach
    </section>
@endif

<section class="proc-dash__cols">
    <div class="proc-dash__card">
        <h3 class="proc-dash__card-title">{{ $left['title'] ?? '' }}</h3>
        @if (! empty($left['subtitle']))
            <p class="proc-dash__card-sub">{{ $left['subtitle'] }}</p>
        @endif

        @if (($left['type'] ?? '') === 'cold_rooms_hex')
            @foreach ($left['items'] ?? [] as $room)
                <div class="proc-dash__cold-bar">
                    <div class="proc-dash__cold-head">
                        <span class="proc-dash__cold-name">{{ $room['name'] }}</span>
                        <span class="proc-dash__cold-temp">{{ $room['temperature'] ?? __('No reading') }}</span>
                    </div>
                    <div class="proc-dash__cold-track">
                        <div class="proc-dash__cold-fill" style="width: {{ (int) $room['progress'] }}%; background: {{ $room['barColor'] }}"></div>
                    </div>
                </div>
            @endforeach
            <p class="proc-dash__card-sub" style="margin-top:8px;margin-bottom:0;">{{ __('Threshold ≤4°C') }}</p>
        @else
            @foreach ($left['items'] ?? [] as $item)
                @php
                    $href = isset($item['route']) ? route($item['route'], $item['routeParams'] ?? []) : null;
                @endphp
                @if ($href)
                    <a href="{{ $href }}" class="proc-dash__row">
                @else
                    <div class="proc-dash__row">
                @endif

                @if (($left['type'] ?? '') === 'module_rows')
                    <i class="ti ti-{{ $item['icon'] }} proc-dash__row-icon" aria-hidden="true"></i>
                    <div class="proc-dash__row-body">
                        <p class="proc-dash__row-primary">{{ $item['label'] }}</p>
                        <p class="proc-dash__row-secondary">{{ $item['sub'] }}</p>
                    </div>
                    <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'info' }}">{{ $item['badge'] }}</span>
                @elseif (($left['type'] ?? '') === 'pipeline')
                    <i class="ti ti-{{ $item['icon'] }} proc-dash__row-icon" aria-hidden="true"></i>
                    <div class="proc-dash__row-body">
                        <p class="proc-dash__row-primary">{{ $item['label'] }}</p>
                    </div>
                    <span class="proc-dash__badge-pill proc-dash__badge-pill--neutral">{{ $item['count'] }}</span>
                @elseif (($left['type'] ?? '') === 'invoices')
                    <div class="proc-dash__row-body">
                        <p class="proc-dash__row-primary">{{ $item['id'] }}</p>
                        <p class="proc-dash__row-secondary">{{ $item['client'] }} · {{ $item['amount'] }}</p>
                    </div>
                    <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
                @elseif (in_array($left['type'] ?? '', ['batches', 'trips'], true))
                    <div class="proc-dash__row-body">
                        <p class="proc-dash__row-primary">{{ $item['id'] ?? $item['destination'] }}</p>
                        <p class="proc-dash__row-secondary">{{ $item['meta'] }}</p>
                    </div>
                    <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
                @endif

                @if ($href)</a>@else</div>@endif
            @endforeach
        @endif
    </div>

    <div class="proc-dash__card">
        <h3 class="proc-dash__card-title">{{ $right['title'] ?? '' }}</h3>
        @if (! empty($right['subtitle']))
            <p class="proc-dash__card-sub">{{ $right['subtitle'] }}</p>
        @endif

        @foreach ($right['items'] ?? [] as $item)
            @php
                $href = isset($item['route']) && $item['route']
                    ? route($item['route'], $item['routeParams'] ?? [])
                    : null;
            @endphp
            @if ($href)
                <a href="{{ $href }}" class="proc-dash__row">
            @else
                <div class="proc-dash__row">
            @endif

            @if (in_array($right['type'] ?? '', ['alerts', 'compliance_issues'], true))
                <span class="proc-dash__dot proc-dash__dot--{{ $dotVariants[$item['dotTone']] ?? 'info' }}"></span>
                <div class="proc-dash__row-body">
                    <p class="proc-dash__row-primary">{{ $item['message'] }}</p>
                    <p class="proc-dash__row-secondary">
                        @if (! empty($item['reference']))
                            {{ $item['reference'] }}
                        @elseif (! empty($item['timestamp']))
                            {{ $item['timestamp']->diffForHumans() }}
                        @else
                            {{ __('Just now') }}
                        @endif
                    </p>
                </div>
            @elseif (($right['type'] ?? '') === 'inspectors')
                <div class="proc-dash__row-body">
                    <p class="proc-dash__row-primary">{{ $item['name'] }}</p>
                    <p class="proc-dash__row-secondary">{{ $item['facility'] }}</p>
                </div>
                <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
            @elseif (($right['type'] ?? '') === 'inspection_queue')
                <div class="proc-dash__row-body">
                    <p class="proc-dash__row-primary">{{ $item['label'] }}</p>
                </div>
                <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
            @elseif (($right['type'] ?? '') === 'deliveries')
                <div class="proc-dash__row-body">
                    <p class="proc-dash__row-primary">{{ $item['id'] }}</p>
                    <p class="proc-dash__row-secondary">{{ $item['meta'] }}</p>
                </div>
                <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
            @elseif (($right['type'] ?? '') === 'payables')
                <div class="proc-dash__row-body">
                    <p class="proc-dash__row-primary">{{ $item['supplier'] }}</p>
                    <p class="proc-dash__row-secondary">{{ $item['meta'] }}</p>
                </div>
                <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'neutral' }}">{{ $item['badge'] }}</span>
            @endif

            @if ($href)</a>@else</div>@endif
        @endforeach
    </div>
</section>

<section class="proc-dash__actions">
    <h3 class="proc-dash__actions-title">{{ __('Quick actions') }}</h3>
    <p class="proc-dash__actions-sub">{{ __('Jump directly into core workflows') }}</p>
    <div class="proc-dash__actions-grid">
        @foreach ($ops['quickActions'] as $action)
            @if ($action['url'])
                <a href="{{ $action['url'] }}" class="proc-dash__action">
                    <i class="ti ti-{{ $action['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $action['label'] }}</span>
                </a>
            @else
                <div class="proc-dash__action proc-dash__action--disabled" aria-disabled="true">
                    <i class="ti ti-{{ $action['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $action['label'] }}</span>
                </div>
            @endif
        @endforeach
    </div>
</section>
