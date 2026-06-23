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

@if (! empty($ops['showPeriodFilter']) && ! empty($ops['filters']))
    @php $filters = $ops['filters']; @endphp
    <form method="get" action="{{ route('dashboard') }}" class="hub-period-filter proc-dash__period-filter">
        <div class="hub-period-filter__bar">
            <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Execution period') }}">
                @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                    <label class="hub-period-filter__toggle">
                        <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                        <span>{{ $periodLabel }}</span>
                    </label>
                @endforeach
            </div>

            <div class="hub-period-filter__range">
                <label for="dash_filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                <input id="dash_filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                <label for="dash_filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                <input id="dash_filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
            </div>

            <div class="hub-period-filter__actions">
                <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                @if ($filters['is_filtered'])
                    <a href="{{ route('dashboard', in_array($ops['roleKey'] ?? '', [\App\Models\BusinessUser::ROLE_INSPECTOR, \App\Models\BusinessUser::ROLE_OPERATIONS_MANAGER, \App\Models\BusinessUser::ROLE_ACCOUNTANT, \App\Models\BusinessUser::ROLE_TRANSPORT_MANAGER], true) ? ['period' => 'all'] : []) }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                @endif
            </div>
        </div>
        <p class="hub-period-filter__hint">
            @if (($ops['roleKey'] ?? '') === \App\Models\BusinessUser::ROLE_INSPECTOR)
                {{ $filters['period_hint'] ?? __('Inspections') }} · {{ $filters['range_label'] }}
            @elseif (($ops['roleKey'] ?? '') === \App\Models\BusinessUser::ROLE_OPERATIONS_MANAGER)
                {{ __('Operations') }} · {{ $filters['range_label'] }}
            @elseif (($ops['roleKey'] ?? '') === \App\Models\BusinessUser::ROLE_ACCOUNTANT)
                {{ __('Finance') }} · {{ $filters['range_label'] }}
            @elseif (($ops['roleKey'] ?? '') === \App\Models\BusinessUser::ROLE_TRANSPORT_MANAGER)
                {{ __('Transport') }} · {{ $filters['range_label'] }}
            @else
                {{ __('Slaughter executions') }} · {{ $filters['range_label'] }}
            @endif
        </p>
    </form>
@endif

<section class="profile-kpi-grid proc-dash__kpi-grid" aria-label="{{ __('Key performance indicators') }}">
    @php
        $kpiIconDefaults = ['box', 'certificate', 'truck', 'alert-triangle', 'currency-dollar'];
    @endphp
    @foreach ($ops['kpiCards'] as $index => $card)
        @php
            $rawValue = $card['value'];
            $displayValue = is_int($rawValue) || (is_string($rawValue) && ctype_digit($rawValue))
                ? number_format((int) $rawValue)
                : (string) $rawValue;
            $kpiIcon = $card['icon'] ?? $kpiIconDefaults[$index % count($kpiIconDefaults)];
            $accent = in_array($card['deltaTone'] ?? '', ['warning', 'negative'], true)
                || in_array($card['iconTone'] ?? '', ['red', 'orange'], true);
        @endphp
        <x-entity.kpi-stat
            :label="$card['label']"
            :value="$displayValue"
            :hint="$card['change'] ?? null"
            :accent="$accent"
        >
            <x-slot:icon>
                @include('processor.partials.dashboard-kpi-icon', ['icon' => $kpiIcon])
            </x-slot:icon>
        </x-entity.kpi-stat>
    @endforeach
</section>

@if (count($charts) > 0)
    <section class="proc-dash__charts" aria-label="{{ __('Charts') }}">
        @foreach ($charts as $chart)
            <div @class(['proc-dash__chart-card', 'proc-dash__chart-card--full' => ! empty($chart['fullWidth'])])>
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
                @php
                    if (($chart['type'] ?? '') === 'pie') {
                        $chartDataTotal = array_sum($chart['data'] ?? []);
                    } elseif (! empty($chart['stacked']) && ! empty($chart['datasets'])) {
                        $chartDataTotal = array_sum(array_map(
                            fn (array $dataset) => array_sum($dataset['data'] ?? []),
                            $chart['datasets'],
                        ));
                    } else {
                        $chartDataTotal = null;
                    }
                @endphp
                @if ($chartDataTotal === 0)
                    <div class="proc-dash__chart-empty">{{ $chart['emptyMessage'] ?? __('No inspection activity for this period.') }}</div>
                @else
                <div class="proc-dash__chart-wrap" style="position: relative; height: {{ (int) ($chart['height'] ?? 160) }}px">
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

@php $workTable = $ops['workTable'] ?? null; @endphp

@if ($workTable)
    <section class="proc-dash__table-section" aria-label="{{ $workTable['title'] ?? '' }}">
        <div class="proc-dash__table-head">
            <div>
                <h3 class="proc-dash__card-title">{{ $workTable['title'] }}</h3>
                @if (! empty($workTable['subtitle']))
                    <p class="proc-dash__card-sub">{{ $workTable['subtitle'] }}</p>
                @endif
            </div>
            @if (! empty($workTable['footerRoute']))
                <a href="{{ route($workTable['footerRoute'], $workTable['footerRouteParams'] ?? []) }}" class="proc-dash__card-link proc-dash__card-link--head">
                    {{ $workTable['footerLabel'] ?? __('View all') }}
                </a>
            @endif
        </div>

        @if (empty($workTable['rows']))
            <div class="proc-dash__table-empty">{{ $workTable['emptyMessage'] ?? __('No batches in this period.') }}</div>
        @else
            <div class="proc-dash__table-wrap">
                <table class="proc-dash__table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $workTable['headers']['primary'] ?? __('Batch') }}</th>
                            <th>{{ $workTable['headers']['secondary'] ?? __('Species') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ $workTable['headers']['updated'] ?? __('Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workTable['rows'] as $index => $row)
                            <tr>
                                <td class="proc-dash__table-rank">{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route($row['route'], $row['route_params'] ?? []) }}" class="proc-dash__table-link">
                                        {{ $row['id'] }}
                                    </a>
                                </td>
                                <td>{{ $row['species'] }}</td>
                                <td>
                                    <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$row['status_tone']] ?? 'info' }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td class="proc-dash__table-muted">{{ $row['updated_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@elseif (! empty($left['title']) || ! empty($right['title']))
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
            @if (empty($left['items']))
                <p class="proc-dash__card-sub" style="margin-bottom:0;">{{ $left['empty'] ?? __('No records yet.') }}</p>
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
            @if (! empty($left['footerRoute']))
                <a href="{{ route($left['footerRoute'], $left['footerRouteParams'] ?? []) }}" class="proc-dash__card-link">{{ $left['footerLabel'] ?? __('View all') }}</a>
            @endif
        @endif
    </div>

    <div class="proc-dash__card">
        <h3 class="proc-dash__card-title">{{ $right['title'] ?? '' }}</h3>
        @if (! empty($right['subtitle']))
            <p class="proc-dash__card-sub">{{ $right['subtitle'] }}</p>
        @endif

        @if (($right['type'] ?? '') === 'module_rows')
            @if (empty($right['items']))
                <p class="proc-dash__card-sub" style="margin-bottom:0;">{{ $right['empty'] ?? __('No records yet.') }}</p>
            @else
                @foreach ($right['items'] ?? [] as $item)
                    @php
                        $href = isset($item['route']) ? route($item['route'], $item['routeParams'] ?? []) : null;
                    @endphp
                    @if ($href)
                        <a href="{{ $href }}" class="proc-dash__row">
                    @else
                        <div class="proc-dash__row">
                    @endif
                        <i class="ti ti-{{ $item['icon'] }} proc-dash__row-icon" aria-hidden="true"></i>
                        <div class="proc-dash__row-body">
                            <p class="proc-dash__row-primary">{{ $item['label'] }}</p>
                            <p class="proc-dash__row-secondary">{{ $item['sub'] }}</p>
                        </div>
                        <span class="proc-dash__badge-pill proc-dash__badge-pill--{{ $badgeVariants[$item['badgeTone']] ?? 'info' }}">{{ $item['badge'] }}</span>
                    @if ($href)</a>@else</div>@endif
                @endforeach
            @endif
            @if (! empty($right['footerRoute']))
                <a href="{{ route($right['footerRoute'], $right['footerRouteParams'] ?? []) }}" class="proc-dash__card-link">{{ $right['footerLabel'] ?? __('View all') }}</a>
            @endif
        @else
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
        @endif
    </div>
</section>
@endif
