<x-app-layout>
    @php
        $sortLink = function (string $column) {
            $direction = request('sort') === $column && request('direction', 'asc') === 'asc' ? 'desc' : 'asc';

            return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => 1]);
        };
        $sortIndicator = function (string $column) {
            if (request('sort', 'facility_name') !== $column) {
                return '';
            }

            return request('direction', 'asc') === 'asc' ? ' ↑' : ' ↓';
        };
    @endphp

    <div class="rica-supply-chain proc-dash rica-sc-hub -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Reports') }}</h1>
                </div>

                <div class="rica-sc-toolbar">
                    <a href="{{ route('rica.monthly-reports.index') }}" class="rica-sc-action">
                        {{ __('Monthly reports') }}
                    </a>
                    <a href="{{ route('rica.reports.export', request()->query()) }}" class="rica-sc-export" title="{{ __('Export CSV') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Export') }}
                    </a>
                </div>
            </header>

            <section class="rica-sc-filters" aria-label="{{ __('Report filters') }}">
                <form method="GET" action="{{ route('rica.reports') }}" class="rica-sc-filters__form">
                    <label class="rica-sc-field">
                        <span class="rica-sc-field__label">{{ __('Date from') }}</span>
                        <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="rica-sc-input">
                    </label>
                    <label class="rica-sc-field">
                        <span class="rica-sc-field__label">{{ __('Date to') }}</span>
                        <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="rica-sc-input">
                    </label>
                    <label class="rica-sc-field">
                        <span class="rica-sc-field__label">{{ __('Operator') }}</span>
                        <select name="business_id" class="rica-sc-input">
                            <option value="">{{ __('All operators') }}</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}" @selected((string) request('business_id') === (string) $business->id)>{{ $business->business_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="rica-sc-field rica-sc-field--grow">
                        <span class="rica-sc-field__label">{{ __('Facility search') }}</span>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Slaughterhouse name…') }}" class="rica-sc-input">
                    </label>
                    <div class="rica-sc-field">
                        <span class="rica-sc-field__label">{{ __('Date grouping') }}</span>
                        <div class="rica-sc-toggle" role="group" aria-label="{{ __('Date grouping') }}">
                            <label class="rica-sc-toggle__option @if ($dateBasis === 'slaughter') is-active @endif">
                                <input type="radio" name="date_basis" value="slaughter" class="sr-only" @checked($dateBasis === 'slaughter')>
                                {{ __('Slaughter date') }}
                            </label>
                            <label class="rica-sc-toggle__option @if ($dateBasis === 'record') is-active @endif">
                                <input type="radio" name="date_basis" value="record" class="sr-only" @checked($dateBasis === 'record')>
                                {{ __('Record date') }}
                            </label>
                        </div>
                    </div>
                    <div class="rica-sc-filters__actions">
                        <button type="submit" class="rica-sc-export">{{ __('Apply') }}</button>
                        <a href="{{ route('rica.reports') }}" class="rica-sc-action">{{ __('Clear') }}</a>
                    </div>
                </form>
            </section>

            <section class="rica-sc-kpi-grid" aria-label="{{ __('Report totals') }}">
                @foreach ([
                    ['label' => __('Animals slaughtered'), 'value' => number_format($totals['animals']), 'glyph' => 'clipboard-list'],
                    ['label' => __('Meat yield'), 'value' => number_format($totals['total_meat_kg'], 0).' kg', 'glyph' => 'weight'],
                    ['label' => __('Condemned'), 'value' => number_format($totals['condemned']), 'glyph' => 'trash'],
                    ['label' => __('Certificates'), 'value' => number_format($totals['certificates']), 'glyph' => 'certificate'],
                ] as $card)
                    <article class="rica-sc-kpi-card rica-sc-kpi-card--compact">
                        <div class="rica-sc-kpi-card__icon" aria-hidden="true">
                            @include('layouts.partials.sidebar-icon', ['icon' => $card['glyph']])
                        </div>
                        <div class="rica-sc-kpi-card__content">
                            <p class="rica-sc-kpi-card__label">{{ $card['label'] }}</p>
                            <p class="rica-sc-kpi-card__value">{{ $card['value'] }}</p>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="rica-sc-charts-row rica-sc-tables-row" aria-label="{{ __('Facility report') }}">
                <article class="proc-dash__chart-card rica-sc-report-table-card">
                    <div class="rica-sc-table-wrap rica-sc-table-wrap--report">
                        <table class="rica-sc-table rica-sc-table--report">
                            <thead>
                                <tr>
                                    <th><a href="{{ $sortLink('facility_name') }}" class="rica-sc-table__sort">{{ __('Facility') }}{{ $sortIndicator('facility_name') }}</a></th>
                                    <th><a href="{{ $sortLink('operator') }}" class="rica-sc-table__sort">{{ __('Operator') }}{{ $sortIndicator('operator') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('animals') }}" class="rica-sc-table__sort">{{ __('Animals') }}{{ $sortIndicator('animals') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('total_meat_kg') }}" class="rica-sc-table__sort">{{ __('Meat (kg)') }}{{ $sortIndicator('total_meat_kg') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('condemned') }}" class="rica-sc-table__sort">{{ __('Condemned') }}{{ $sortIndicator('condemned') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('certificates') }}" class="rica-sc-table__sort">{{ __('Certificates') }}{{ $sortIndicator('certificates') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('awaiting_certificate') }}" class="rica-sc-table__sort">{{ __('Released, no cert.') }}{{ $sortIndicator('awaiting_certificate') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('avg_cold_room_days') }}" class="rica-sc-table__sort">{{ __('Avg cold room days') }}{{ $sortIndicator('avg_cold_room_days') }}</a></th>
                                    <th class="text-right"><a href="{{ $sortLink('temperature_violations') }}" class="rica-sc-table__sort">{{ __('Temp violations') }}{{ $sortIndicator('temperature_violations') }}</a></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reportRows as $row)
                                    @php
                                        $detailUrl = route('rica.slaughterhouses.show', [
                                            'facility' => $row['facility']->id,
                                            'date_from' => $dateFrom->toDateString(),
                                            'date_to' => $dateTo->toDateString(),
                                        ]);
                                    @endphp
                                    <tr class="rica-sc-table__row-link" onclick="window.location='{{ $detailUrl }}'">
                                        <td>{{ $row['facility']->facility_name }}</td>
                                        <td>{{ $row['facility']->business->business_name ?? '—' }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['animals']) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['total_meat_kg'], 2) }}</td>
                                        <td class="text-right tabular-nums @if ($row['condemned'] > 0) rica-sc-table__warn @endif">{{ number_format($row['condemned']) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($row['certificates']) }}</td>
                                        <td class="text-right tabular-nums @if ($row['awaiting_certificate'] > 0) rica-sc-table__caution @endif">{{ number_format($row['awaiting_certificate']) }}</td>
                                        <td class="text-right tabular-nums">{{ $row['avg_cold_room_days'] !== null ? number_format($row['avg_cold_room_days'], 1) : '—' }}</td>
                                        <td class="text-right tabular-nums @if ($row['temperature_violations'] > 0) rica-sc-table__warn @endif">{{ number_format($row['temperature_violations']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="rica-sc-table__empty">
                                            {{ __('No slaughterhouses match your filters. Try adjusting the date range or search term.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($reportRows->count() > 0)
                                <tfoot>
                                    <tr class="rica-sc-table__totals">
                                        <td colspan="2">{{ __('Totals') }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['animals']) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['total_meat_kg'], 2) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['condemned']) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['certificates']) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['awaiting_certificate']) }}</td>
                                        <td class="text-right tabular-nums">{{ $totals['avg_cold_room_days'] !== null ? number_format($totals['avg_cold_room_days'], 1) : '—' }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($totals['temperature_violations']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                    @if ($reportRows->hasPages())
                        <div class="rica-sc-table-pagination">{{ $reportRows->links() }}</div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
