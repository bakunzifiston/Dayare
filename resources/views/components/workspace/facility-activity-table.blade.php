@props([
    'rows',
    'title',
    'subtitle',
    'facilityLabel' => __('Facility'),
    'businessLabel' => __('Business'),
    'emptyMessage' => __('No facility activity for this period.'),
    'footerRoute' => null,
    'footerRouteParams' => [],
    'footerLabel' => null,
    'facilityRoute' => null,
])

@php
    $totalReceived = $rows->sum('animals_received');
    $totalSlaughtered = $rows->sum('animals_slaughtered');
@endphp

<section class="proc-dash__table-section" aria-label="{{ $title }}">
    <div class="proc-dash__table-head">
        <div>
            <h3 class="proc-dash__card-title">{{ $title }}</h3>
            @if (! empty($subtitle))
                <p class="proc-dash__card-sub">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="proc-dash__table-summary">
            <span class="proc-dash__table-summary-item">
                {{ __('Received') }}:
                <strong>{{ number_format($totalReceived) }}</strong>
            </span>
            <span class="proc-dash__table-summary-item">
                {{ __('Slaughtered') }}:
                <strong>{{ number_format($totalSlaughtered) }}</strong>
            </span>
        </div>
        @if ($footerRoute)
            <a href="{{ route($footerRoute, $footerRouteParams) }}" class="proc-dash__card-link proc-dash__card-link--head">
                {{ $footerLabel ?? __('View all') }}
            </a>
        @endif
    </div>

    @if ($rows->isEmpty())
        <div class="proc-dash__table-empty">{{ $emptyMessage }}</div>
    @else
        <div class="proc-dash__table-wrap">
            <table class="proc-dash__table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ $facilityLabel }}</th>
                        <th>{{ $businessLabel }}</th>
                        <th class="proc-dash__table-th--num">{{ __('Received') }}</th>
                        <th class="proc-dash__table-th--num">{{ __('Slaughtered') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr>
                            <td class="proc-dash__table-rank">{{ $index + 1 }}</td>
                            <td>
                                @if ($facilityRoute)
                                    <a href="{{ route($facilityRoute, ['facility' => $row['id']]) }}" class="proc-dash__table-link">
                                        {{ $row['facility_name'] }}
                                    </a>
                                @else
                                    {{ $row['facility_name'] }}
                                @endif
                            </td>
                            <td class="proc-dash__table-muted">{{ $row['business_name'] }}</td>
                            <td class="proc-dash__table-num">
                                <span @class([
                                    'proc-dash__table-stat',
                                    'proc-dash__table-stat--received' => $row['animals_received'] > 0,
                                    'proc-dash__table-stat--zero' => $row['animals_received'] === 0,
                                ])>{{ number_format($row['animals_received']) }}</span>
                            </td>
                            <td class="proc-dash__table-num">
                                <span @class([
                                    'proc-dash__table-stat',
                                    'proc-dash__table-stat--slaughtered' => $row['animals_slaughtered'] > 0,
                                    'proc-dash__table-stat--zero' => $row['animals_slaughtered'] === 0,
                                ])>{{ number_format($row['animals_slaughtered']) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
