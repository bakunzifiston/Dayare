<x-app-layout>
    @php
        $periodOptions = [
            'all' => __('All time'),
            'month' => __('This month'),
            'year' => __('This year'),
            'day' => __('Today'),
        ];
        $activePeriod = $filters['period'] ?? 'all';
    @endphp

    <div class="rica-supply-chain rica-traceability proc-dash -mx-4 -mt-4 sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
        <div class="rica-sc-page">
            <header class="rica-sc-header">
                <div class="rica-sc-header__copy">
                    <h1 class="rica-sc-header__title">{{ __('Traceability overview') }}</h1>
                </div>

                <form method="get" action="{{ route('rica.traceability') }}" class="rica-sc-toolbar">
                    <label class="rica-sc-select">
                        <span class="sr-only">{{ __('Period') }}</span>
                        <select name="period" onchange="this.form.requestSubmit()">
                            @foreach ($periodOptions as $periodKey => $periodLabel)
                                <option value="{{ $periodKey }}" @selected($activePeriod === $periodKey)>{{ $periodLabel }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if ($featuredBatch)
                        <input type="hidden" name="batch_id" value="{{ $featuredBatch['batch_id'] }}">
                    @endif

                    <a href="{{ route('rica.reports') }}" class="rica-sc-export" title="{{ __('Export') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Export') }}
                    </a>
                </form>
            </header>

            <section class="rica-trace-panel rica-trace-search" aria-label="{{ __('Batch search') }}">
                <form method="get" action="{{ route('rica.traceability') }}" class="rica-trace-search__form">
                    <input type="hidden" name="period" value="{{ $activePeriod }}">
                    <div class="rica-trace-search__row">
                        <div class="rica-trace-search__bar">
                            <input
                                type="search"
                                name="q"
                                value="{{ $searchQuery }}"
                                placeholder="{{ __('Search Animal ID, Ear Tag, Batch ID, Certificate No, or QR Code') }}"
                                class="rica-trace-search__input"
                                aria-label="{{ __('Search traceability records') }}"
                            >
                            <button type="submit" class="rica-trace-search__submit" aria-label="{{ __('Search') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </div>
                        <div class="rica-trace-search__types" role="group" aria-label="{{ __('Search type') }}">
                            @foreach ($searchTypes as $typeKey => $typeLabel)
                                <label class="rica-trace-search__type">
                                    <input type="radio" name="search_type" value="{{ $typeKey }}" @checked($searchType === $typeKey)>
                                    <span>{{ $typeLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>

                @if ($searchResults !== [])
                    <div class="rica-trace-search__results">
                        @foreach ($searchResults as $result)
                            <a
                                href="{{ route('rica.traceability', ['period' => $activePeriod, 'batch_id' => $result['batch_id'], 'q' => $searchQuery, 'search_type' => $searchType]) }}"
                                class="rica-trace-search__result @if (($featuredBatch['batch_id'] ?? null) === $result['batch_id']) is-active @endif"
                            >
                                <span class="rica-trace-search__result-code">{{ $result['batch_code'] }}</span>
                                <span class="rica-trace-search__result-meta">{{ number_format($result['completion_percent'], 0) }}%</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rica-trace-panel rica-trace-journey" aria-label="{{ __('Farm to destination journey') }}">
                <h2 class="rica-trace-panel__title">{{ __('Farm to destination journey') }}</h2>
                <div class="rica-trace-journey__track">
                    @foreach ($journeySteps as $index => $step)
                        @php
                            $completed = (bool) ($journeyProgress[$step['key']] ?? false);
                            $nextKey = $journeySteps[$index + 1]['key'] ?? null;
                            $connectorDone = $completed && $nextKey && ($journeyProgress[$nextKey] ?? false);
                        @endphp
                        <div class="rica-trace-journey__step @if ($completed) is-done @else is-pending @endif">
                            <div class="rica-trace-journey__node">
                                @include('layouts.partials.sidebar-icon', ['icon' => $step['glyph']])
                            </div>
                            <p class="rica-trace-journey__label">{{ $step['title'] }}</p>
                        </div>
                        @if (! $loop->last)
                            <div class="rica-trace-journey__connector @if ($connectorDone || $completed) is-done @endif" aria-hidden="true"></div>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="rica-trace-bottom" aria-label="{{ __('Traceability details') }}">
                <article class="rica-trace-panel rica-trace-timeline-card">
                    <h2 class="rica-trace-panel__title">{{ __('Traceability timeline') }}</h2>
                    <p class="rica-trace-panel__subtitle">
                        @if ($featuredBatch)
                            {{ __('Batch :code', ['code' => $featuredBatch['batch_code']]) }}
                        @else
                            {{ __('Select a batch to view its journey') }}
                        @endif
                    </p>
                    <ol class="rica-trace-timeline">
                        @forelse ($timeline as $entry)
                            <li class="rica-trace-timeline__item @if ($entry['completed']) is-done @endif">
                                <span class="rica-trace-timeline__bullet" aria-hidden="true"></span>
                                <div class="rica-trace-timeline__content">
                                    <p class="rica-trace-timeline__title">{{ $entry['title'] }}</p>
                                    <p class="rica-trace-timeline__detail">{{ $entry['detail'] }}</p>
                                    @if ($entry['at'])
                                        <p class="rica-trace-timeline__time">{{ $entry['at'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="rica-trace-timeline__empty">{{ __('No timeline data for this period.') }}</li>
                        @endforelse
                    </ol>
                </article>

                <article class="rica-trace-panel rica-trace-alerts-card">
                    <div class="rica-trace-panel__title-row">
                        <h2 class="rica-trace-panel__title">{{ __('Live alerts') }}</h2>
                        @if (count($alerts) > 0)
                            <span class="rica-trace-alerts__badge">{{ count($alerts) }}</span>
                        @endif
                    </div>
                    <ul class="rica-trace-alerts">
                        @forelse ($alerts as $alert)
                            <li class="rica-trace-alerts__item rica-trace-alerts__item--{{ $alert['severity'] }}">
                                <p class="rica-trace-alerts__title">{{ $alert['title'] }}</p>
                                <p class="rica-trace-alerts__message">{{ $alert['message'] }}</p>
                            </li>
                        @empty
                            <li class="rica-trace-alerts__empty">{{ __('No traceability alerts for this period.') }}</li>
                        @endforelse
                    </ul>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
