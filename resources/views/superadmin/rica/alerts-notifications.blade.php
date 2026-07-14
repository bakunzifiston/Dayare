<x-app-layout>
    @php
        $severityLabels = [
            'critical' => __('Critical'),
            'warning' => __('Warning'),
            'info' => __('Info'),
        ];
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Alerts & notifications') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.alerts-notifications') }}" class="rica-sc-toolbar">
                    @if ($selectedCategory !== 'all')
                        <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    @endif
                    @if ($selectedSeverity !== 'all')
                        <input type="hidden" name="severity" value="{{ $selectedSeverity }}">
                    @endif

                    <label class="rica-sc-select">
                        <span class="sr-only">{{ __('District') }}</span>
                        <select name="district_id" onchange="this.form.requestSubmit()">
                            <option value="all" @selected($selectedDistrictId === null)>{{ __('All districts') }}</option>
                            @foreach ($districtOptions as $districtId => $districtName)
                                <option value="{{ $districtId }}" @selected((int) $selectedDistrictId === (int) $districtId)>{{ $districtName }}</option>
                            @endforeach
                        </select>
                    </label>

                    <a href="{{ route('rica.settings') }}" class="rica-sc-export rica-sc-export--muted" title="{{ __('Notification settings') }}">
                        @include('layouts.partials.sidebar-icon', ['icon' => 'settings'])
                        {{ __('Settings') }}
                    </a>
                </form>
            </header>

            <section class="rica-sc-kpi-grid" aria-label="{{ __('Alert summary') }}">
                @foreach ([
                    ['key' => 'total', 'label' => __('Open alerts'), 'glyph' => 'clipboard-list', 'tone' => ''],
                    ['key' => 'critical', 'label' => __('Critical'), 'glyph' => 'alert', 'tone' => 'critical'],
                    ['key' => 'warning', 'label' => __('Warning'), 'glyph' => 'alert', 'tone' => 'warning'],
                    ['key' => 'info', 'label' => __('Informational'), 'glyph' => 'shield', 'tone' => 'info'],
                ] as $card)
                    <article @class([
                        'rica-sc-kpi-card',
                        'rica-sc-kpi-card--compact',
                        'rica-sc-kpi-card--alert-'.$card['tone'] => $card['tone'] !== '',
                    ])>
                        <div class="rica-sc-kpi-card__icon" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => $card['glyph']])
                        </div>
                        <div class="rica-sc-kpi-card__content">
                            <p class="rica-sc-kpi-card__label">{{ $card['label'] }}</p>
                            <p class="rica-sc-kpi-card__value">{{ number_format($kpis[$card['key']]) }}</p>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="rica-sc-alert-filters" aria-label="{{ __('Filter alerts') }}">
                <div class="rica-sc-alert-filters__group">
                    <p class="rica-sc-alert-filters__label">{{ __('Category') }}</p>
                    <div class="rica-sc-alert-chips">
                        @foreach ($categories as $category)
                            <a
                                href="{{ route('rica.alerts-notifications', array_filter([
                                    'district_id' => $selectedDistrictId,
                                    'category' => $category['key'] === 'all' ? null : $category['key'],
                                    'severity' => $selectedSeverity === 'all' ? null : $selectedSeverity,
                                ])) }}"
                                class="rica-sc-alert-chip {{ $selectedCategory === $category['key'] ? 'is-active' : '' }}"
                            >
                                {{ $category['label'] }}
                                <span class="rica-sc-alert-chip__count">{{ number_format($category['count']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rica-sc-alert-filters__group">
                    <p class="rica-sc-alert-filters__label">{{ __('Severity') }}</p>
                    <div class="rica-sc-alert-chips">
                        @foreach ([
                            'all' => __('All severities'),
                            'critical' => __('Critical'),
                            'warning' => __('Warning'),
                            'info' => __('Info'),
                        ] as $severityKey => $severityLabel)
                            <a
                                href="{{ route('rica.alerts-notifications', array_filter([
                                    'district_id' => $selectedDistrictId,
                                    'category' => $selectedCategory === 'all' ? null : $selectedCategory,
                                    'severity' => $severityKey === 'all' ? null : $severityKey,
                                ])) }}"
                                class="rica-sc-alert-chip rica-sc-alert-chip--{{ $severityKey !== 'all' ? $severityKey : 'neutral' }} {{ $selectedSeverity === $severityKey ? 'is-active' : '' }}"
                            >
                                {{ $severityLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rica-sc-charts-row rica-sc-tables-row" aria-label="{{ __('Alert inbox') }}">
                <article class="proc-dash__chart-card rica-sc-alert-inbox">
                    <div class="rica-sc-alert-inbox__head">
                        <h2 class="proc-dash__chart-title">{{ __('Alert inbox') }}</h2>
                        <p class="rica-sc-alert-inbox__meta">
                            {{ trans_choice(':count alert|:count alerts', $filteredCount, ['count' => number_format($filteredCount)]) }}
                            @if ($filteredCount !== $totalAlerts)
                                <span class="text-slate-400">· {{ __(':total total', ['total' => number_format($totalAlerts)]) }}</span>
                            @endif
                        </p>
                    </div>

                    @if ($alerts === [])
                        <div class="rica-sc-empty rica-sc-alert-inbox__empty">
                            <p class="font-medium text-emerald-800">{{ __('No open alerts for this view') }}</p>
                            <p class="mt-1 text-xs text-emerald-700">{{ __('All monitored checks are clear for the selected filters.') }}</p>
                        </div>
                    @else
                        <div class="rica-sc-table-wrap">
                            <table class="rica-sc-table rica-sc-table--alerts">
                                <thead>
                                    <tr>
                                        <th>{{ __('Severity') }}</th>
                                        <th>{{ __('Alert') }}</th>
                                        <th>{{ __('Facility') }}</th>
                                        <th>{{ __('District') }}</th>
                                        <th>{{ __('When') }}</th>
                                        <th class="text-right">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($alerts as $alert)
                                        <tr>
                                            <td>
                                                <span class="rica-sc-alert-badge rica-sc-alert-badge--{{ $alert['severity'] }}">
                                                    {{ $severityLabels[$alert['severity']] ?? ucfirst($alert['severity']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="rica-sc-alert-inbox__title">{{ $alert['title'] }}</p>
                                                <p class="rica-sc-alert-inbox__message">{{ $alert['message'] }}</p>
                                            </td>
                                            <td>{{ $alert['facility_name'] ?? '—' }}</td>
                                            <td>{{ $alert['district_name'] ?? '—' }}</td>
                                            <td class="tabular-nums whitespace-nowrap">{{ $alert['occurred_label'] }}</td>
                                            <td class="text-right">
                                                @if ($alert['href'])
                                                    <a href="{{ $alert['href'] }}" class="rica-sc-table-link">{{ __('Review') }} →</a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
