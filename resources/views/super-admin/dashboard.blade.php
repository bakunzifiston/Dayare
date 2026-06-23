<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('Super Admin') }}</span>
            <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
                {{ __('Platform overview') }}
            </h1>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <form method="get" action="{{ route('super-admin.dashboard') }}" class="hub-period-filter">
                <div class="hub-period-filter__bar">
                    <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Slaughter period') }}">
                        @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                            <label class="hub-period-filter__toggle">
                                <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                <span>{{ $periodLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="hub-period-filter__range">
                        <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                        <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                        <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                        <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                        <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                    </div>

                    <div class="hub-period-filter__actions">
                        <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                        @if ($filters['period'] !== 'all' || $filters['has_custom_range'])
                            <a href="{{ route('super-admin.dashboard', ['period' => 'all']) }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
                <p class="hub-period-filter__hint">{{ $filters['slaughter_label'] }} · {{ $filters['range_label'] }}</p>
            </form>

            <div class="profile-kpi-grid">
                <x-entity.kpi-stat :label="__('Total tenants')" :value="number_format($workspaceKpis['tenants'])" accent>
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Total businesses')" :value="number_format($workspaceKpis['businesses'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Total facilities')" :value="number_format($workspaceKpis['facilities'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Total users')" :value="number_format($workspaceKpis['users'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Active facilities')" :value="number_format($workspaceKpis['active_facilities'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
            </div>

            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Slaughter by species') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Completed slaughter head counts across live tenants.') }}</p>
                </div>

                <div class="profile-kpi-grid">
                <x-entity.kpi-stat :label="__('Cattle')" :value="number_format($speciesSlaughtered['cattle_slaughtered'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Goat')" :value="number_format($speciesSlaughtered['goat_slaughtered'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                <x-entity.kpi-stat :label="__('Sheep')" :value="number_format($speciesSlaughtered['sheep_slaughtered'])">
                    <x-slot:icon>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 9.5c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zm11 0c0-1.5 1.5-3 3.5-3s3.5 1.5 3.5 3-1.5 3-3.5 3-3.5-1.5-3.5-3zM2 19c1.5-3 4.5-5 10-5s8.5 2 10 5"/></svg>
                    </x-slot:icon>
                </x-entity.kpi-stat>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-slate-700 mb-1">{{ __('Species animal intake') }}</h3>
                        <p class="text-xs text-slate-500 mb-4">{{ __('Head counts by cattle, goat, and sheep over the selected period.') }}</p>
                        <div class="h-64">
                            <canvas id="chart-species-animal-intake-trend" aria-label="{{ __('Species animal intake') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-slate-700 mb-1">{{ __('Species slaughtered') }}</h3>
                        <p class="text-xs text-slate-500 mb-4">{{ __('Cattle, goat, and sheep in the selected period.') }}</p>
                        @php
                            $speciesPieLabels = $charts['species_slaughter_pie']['labels'] ?? [];
                        @endphp
                        @if (count($speciesPieLabels) === 0)
                            <p class="text-sm text-slate-500 py-8 text-center">{{ __('No slaughter data for this period.') }}</p>
                        @else
                            <div class="h-64">
                                <canvas id="chart-species-slaughter-pie" aria-label="{{ __('Species slaughtered') }}"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Slaughter by facility') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Animals slaughtered per facility for the selected period.') }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-600">
                            {{ __(':count facilities', ['count' => number_format($facilitySlaughterRows->count())]) }}
                        </p>
                        <p class="text-sm font-medium text-slate-800 tabular-nums">
                            {{ __('Total') }}:
                            <span class="text-bucha-primary">{{ number_format($facilitySlaughterRows->sum('animals_slaughtered')) }}</span>
                            {{ __('animals') }}
                        </p>
                    </div>

                    @if ($facilitySlaughterRows->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">{{ __('No facilities found.') }}</div>
                    @else
                        <div class="overflow-x-auto max-h-[28rem]">
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-3 w-12">#</th>
                                        <th class="px-6 py-3">{{ __('Facility') }}</th>
                                        <th class="px-6 py-3">{{ __('Business') }}</th>
                                        <th class="px-6 py-3 text-right">{{ __('Animals slaughtered') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($facilitySlaughterRows as $index => $row)
                                        <tr @class([
                                            'transition-colors',
                                            'hover:bg-slate-50/70' => $row['animals_slaughtered'] > 0,
                                            'text-slate-400' => $row['animals_slaughtered'] === 0,
                                        ])>
                                            <td class="px-6 py-3.5 text-slate-400 tabular-nums">{{ $index + 1 }}</td>
                                            <td class="px-6 py-3.5">
                                                <p @class([
                                                    'font-medium',
                                                    'text-slate-900' => $row['animals_slaughtered'] > 0,
                                                    'text-slate-500' => $row['animals_slaughtered'] === 0,
                                                ])>{{ $row['facility_name'] }}</p>
                                            </td>
                                            <td class="px-6 py-3.5 text-slate-600">{{ $row['business_name'] }}</td>
                                            <td class="px-6 py-3.5 text-right tabular-nums">
                                                @if ($row['animals_slaughtered'] > 0)
                                                    <span class="inline-flex items-center rounded-full bg-bucha-primary/10 px-2.5 py-0.5 text-xs font-semibold text-bucha-primary">
                                                        {{ number_format($row['animals_slaughtered']) }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400">0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Administrative compliance --}}
            @php $adminWithIssues = collect($administrativeAlerts)->filter(fn ($a) => ($a['count'] ?? 0) > 0); @endphp
            @if ($adminWithIssues->isNotEmpty())
                <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Administrative compliance') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Licenses, contracts, and authorization expiry alerts.') }}</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($adminWithIssues as $alert)
                            <x-super-admin.compliance-alert-card
                                :label="$alert['label']"
                                :description="$alert['description']"
                                :count="$alert['count']"
                                :severity="$alert['severity']"
                                :icon="$alert['icon']"
                                :href="null"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>window.dashboardCharts = @json($charts);</script>
        @vite('resources/js/dashboard-charts.js')
    @endpush
</x-app-layout>
