<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Finance Dashboard') }}</span>
    </x-slot>

    @php
        $period = (string) ($kpiPeriod ?? 'all');
        $kpiCards = $kpiCards ?? [];
        $charts = $charts ?? [];
        $quickLinks = $quickLinks ?? [];
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.processorDashboardActiveRole = 'accountant';
            window.processorDashboardCharts = {
                accountant: @json($charts)
            };
        </script>
        @vite('resources/js/processor-dashboard.js')
    @endpush

    <div class="proc-dash py-2">
        <div class="proc-dash__header">
            <div>
                <p class="proc-dash__title">{{ __('Finance overview') }}</p>
                <p class="proc-dash__meta">
                    {{ __('Business: :business', ['business' => $activeBusiness?->business_name ?? __('No active business selected')]) }}
                </p>
            </div>
            <span class="proc-dash__badge proc-dash__badge--finance">{{ __('Finance') }}</span>
        </div>

        <form method="get" action="{{ route('finance.dashboard') }}" class="hub-period-filter proc-dash__period-filter">
            <div class="inline-flex flex-wrap rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="{{ __('Finance KPI time range') }}">
                @foreach (['all' => __('All time'), 'day' => __('Day'), 'month' => __('Month'), 'year' => __('Year')] as $q => $title)
                    <a
                        href="{{ route('finance.dashboard', ['kpi_period' => $q]) }}"
                        @class([
                            'px-3 py-1.5 text-xs font-medium rounded-md transition',
                            'bg-bucha-primary text-white shadow-sm' => $period === $q,
                            'text-slate-600 hover:text-slate-900 hover:bg-white' => $period !== $q,
                        ])
                    >{{ $title }}</a>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ __('Period: :label', ['label' => $kpiPeriodLabel ?? __('All time')]) }}</p>
        </form>

        <section class="profile-kpi-grid proc-dash__kpi-grid profile-kpi-grid--finance" aria-label="{{ __('Key performance indicators') }}">
            @foreach ($kpiCards as $card)
                @if (! empty($card['href']))
                    <a href="{{ $card['href'] }}" class="block rounded-bucha focus:outline-none focus-visible:ring-2 focus-visible:ring-bucha-primary/40">
                        <x-entity.kpi-stat
                            :label="$card['label']"
                            :value="$card['value']"
                            :hint="$card['hint'] ?? null"
                            :accent="(bool) ($card['accent'] ?? false)"
                        >
                            <x-slot:icon>
                                @include('processor.partials.dashboard-kpi-icon', ['icon' => $card['icon'] ?? 'currency-dollar'])
                            </x-slot:icon>
                        </x-entity.kpi-stat>
                    </a>
                @else
                    <x-entity.kpi-stat
                        :label="$card['label']"
                        :value="$card['value']"
                        :hint="$card['hint'] ?? null"
                        :accent="(bool) ($card['accent'] ?? false)"
                    >
                        <x-slot:icon>
                            @include('processor.partials.dashboard-kpi-icon', ['icon' => $card['icon'] ?? 'currency-dollar'])
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                @endif
            @endforeach
        </section>

        @if (count($charts) > 0)
            <x-workspace.chart-grid :charts="$charts" />
        @endif

        @if (count($quickLinks) > 0)
            <section class="proc-dash__card mt-4" aria-label="{{ __('Quick links') }}">
                <h3 class="proc-dash__card-title">{{ __('Finance modules') }}</h3>
                <p class="proc-dash__card-sub">{{ __('Jump to invoices, payables, and cost tools.') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($quickLinks as $link)
                        <a href="{{ route($link['route']) }}" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-bucha-primary/30 hover:text-bucha-primary">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
