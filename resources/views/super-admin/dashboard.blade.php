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
        <div class="max-w-7xl mx-auto space-y-8">
            {{-- 1. KPI Cards --}}
            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Dashboard KPIs') }}</h2>

                {{-- Platform metrics --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-3 border-b border-slate-100">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Platform metrics') }}</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <x-kpi-card title="{{ __('Total businesses') }}" :value="$platformKpis['businesses']" :href="route('businesses.index')" color="blue" />
                            <x-kpi-card title="{{ __('Total facilities') }}" :value="$platformKpis['facilities']" color="slate" />
                            <x-kpi-card title="{{ __('Total users') }}" :value="$platformKpis['users']" color="blue" />
                            <x-kpi-card title="{{ __('Total inspectors') }}" :value="$platformKpis['inspectors']" :href="route('inspectors.index')" color="slate" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- All users & All businesses (with users and facilities) --}}
            <section class="space-y-6">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Users & businesses on the platform') }}</h2>

                {{-- All users --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700">{{ __('All users registered') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Every user (tenant) on the platform.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($allUsers->isEmpty())
                            <div class="p-6 text-sm text-slate-500">{{ __('No users yet.') }}</div>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-3">{{ __('Name') }}</th>
                                        <th class="px-6 py-3">{{ __('Email') }}</th>
                                        <th class="px-6 py-3">{{ __('Businesses') }}</th>
                                        <th class="px-6 py-3">{{ __('Registered') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($allUsers as $u)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-6 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                                            <td class="px-6 py-3 text-slate-600">{{ $u->email }}</td>
                                            <td class="px-6 py-3 tabular-nums">{{ $u->businesses_count }}</td>
                                            <td class="px-6 py-3 text-slate-500">{{ $u->created_at?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>

                {{-- All businesses with owner and facilities --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700">{{ __('All businesses') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Each business with its owner (user) and facilities.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        @if ($allBusinesses->isEmpty())
                            <div class="p-6 text-sm text-slate-500">{{ __('No businesses yet.') }}</div>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-3">{{ __('Business') }}</th>
                                        <th class="px-6 py-3">{{ __('Owner (user)') }}</th>
                                        <th class="px-6 py-3">{{ __('Facilities') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($allBusinesses as $b)
                                        <tr class="hover:bg-slate-50/50 align-top">
                                            <td class="px-6 py-3">
                                                <span class="font-medium text-slate-900">{{ $b->business_name ?? '—' }}</span>
                                                @if ($b->registration_number)
                                                    <span class="block text-xs text-slate-500">{{ $b->registration_number }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3">
                                                @if ($b->user)
                                                    <span class="font-medium text-slate-900">{{ $b->user->name }}</span>
                                                    <span class="block text-xs text-slate-500">{{ $b->user->email }}</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3">
                                                @if ($b->facilities->isEmpty())
                                                    <span class="text-slate-400">{{ __('None') }}</span>
                                                @else
                                                    <ul class="space-y-1">
                                                        @foreach ($b->facilities as $f)
                                                            <li class="text-slate-700">{{ $f->facility_name ?? $f->facility_type ?? __('Facility') }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 2. Compliance monitoring --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Compliance monitoring') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Alerts for compliance issues across the platform.') }}</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @if ($compliance['facilities_expired_license'] > 0)
                            <div class="rounded-lg border border-red-200 bg-red-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-red-900">{{ __('Facilities with expired licenses') }}</p>
                                <p class="mt-1 text-2xl font-bold text-red-700">{{ $compliance['facilities_expired_license'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['inspectors_expired_authorization'] > 0)
                            <div class="rounded-lg border border-red-200 bg-red-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-red-900">{{ __('Inspectors with expired authorization') }}</p>
                                <p class="mt-1 text-2xl font-bold text-red-700">{{ $compliance['inspectors_expired_authorization'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['employees_expired_contracts'] > 0)
                            <div class="rounded-lg border border-red-200 bg-red-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-red-900">{{ __('Employees with expired contracts') }}</p>
                                <p class="mt-1 text-2xl font-bold text-red-700">{{ $compliance['employees_expired_contracts'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['supplier_contracts_expiring_soon'] > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-amber-900">{{ __('Supplier contracts expiring soon') }}</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $compliance['supplier_contracts_expiring_soon'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['sessions_without_ante_mortem'] > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-amber-900">{{ __('Slaughter sessions without Ante-Mortem') }}</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $compliance['sessions_without_ante_mortem'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['batches_without_post_mortem'] > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-amber-900">{{ __('Batches without Post-Mortem') }}</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $compliance['batches_without_post_mortem'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['batches_without_certificate'] > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-amber-900">{{ __('Batches without certificates') }}</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $compliance['batches_without_certificate'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['temperature_violations'] > 0)
                            <div class="rounded-lg border border-red-200 bg-red-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-red-900">{{ __('Temperature violations in warehouse') }}</p>
                                <p class="mt-1 text-2xl font-bold text-red-700">{{ $compliance['temperature_violations'] }}</p>
                            </div>
                        @endif
                        @if ($compliance['batches_stored_beyond_time'] > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-sm font-medium text-amber-900">{{ __('Batches stored beyond allowed time') }}</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $compliance['batches_stored_beyond_time'] }}</p>
                            </div>
                        @endif
                    </div>
                    @if (array_sum($compliance) === 0)
                        <p class="text-sm text-slate-500">{{ __('No compliance alerts at this time.') }}</p>
                    @endif
                </div>
            </section>

            {{-- 2b. System health --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('System health') }}</h2>
                </div>
                <div class="p-6 flex flex-wrap gap-4 items-center">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 px-4 py-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-medium text-emerald-900">{{ __('Platform operational') }}</span>
                    </div>
                    <span class="text-xs text-slate-500">{{ config('app.name') }} · {{ config('app.env') }}</span>
                </div>
            </section>

            {{-- 3. Operational insights (charts) --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Operational insights') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Platform analytics.') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Slaughter activity – animals slaughtered per day') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-slaughter-activity" aria-label="{{ __('Slaughter activity') }}"></canvas>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Species distribution') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-species-distribution" aria-label="{{ __('Species distribution') }}"></canvas>
                        </div>
                    </div>
                    <div class="lg:col-span-2 rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Demand vs supply') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-demand-vs-supply" aria-label="{{ __('Demand vs supply') }}"></canvas>
                        </div>
                    </div>
                    <div class="lg:col-span-2 rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Deliveries by region') }}</h3>
                        <div class="h-56">
                            <canvas id="chart-deliveries-by-region" aria-label="{{ __('Deliveries by region') }}"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 4. CRM insights --}}
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('CRM insights') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Supplier and customer analytics.') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-slate-700">{{ __('Supplier performance') }}</h3>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Top suppliers by volume') }}</p>
                            @if (!empty($crmInsights['top_suppliers']))
                                <ul class="divide-y divide-slate-100 text-sm">
                                    @foreach (array_slice($crmInsights['top_suppliers'], 0, 5) as $s)
                                        <li class="py-2 flex justify-between">
                                            <span class="font-medium text-slate-900">{{ $s['name'] }}</span>
                                            <span class="tabular-nums text-slate-600">{{ number_format($s['volume']) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500">{{ __('No supplier data yet.') }}</p>
                            @endif
                        </div>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">{{ __('Supplier rejection rate') }}</p>
                            <p class="text-2xl font-bold text-slate-900">{{ $crmInsights['supplier_rejection_rate']['rate'] }}%</p>
                            <p class="text-xs text-slate-500">{{ $crmInsights['supplier_rejection_rate']['rejected'] }} / {{ $crmInsights['supplier_rejection_rate']['total'] }} {{ __('intakes') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-slate-700">{{ __('Customer activity') }}</h3>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Top customers by purchase volume') }}</p>
                            @if (!empty($crmInsights['top_customers']))
                                <ul class="divide-y divide-slate-100 text-sm">
                                    @foreach (array_slice($crmInsights['top_customers'], 0, 5) as $c)
                                        <li class="py-2 flex justify-between">
                                            <span class="font-medium text-slate-900">{{ $c['name'] }}</span>
                                            <span class="tabular-nums text-slate-600">{{ number_format($c['volume'], 1) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500">{{ __('No customer delivery data yet.') }}</p>
                            @endif
                        </div>
                        <div class="rounded-lg border border-slate-100 p-4">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">{{ __('Customer demand trends') }}</p>
                            <div class="h-40">
                                <canvas id="chart-demand-trends" aria-label="{{ __('Demand trends') }}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    @push('scripts')
        <script>window.dashboardCharts = @json($charts);</script>
        @vite('resources/js/dashboard-charts.js')
    @endpush
</x-app-layout>
