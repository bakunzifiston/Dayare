<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ $business->business_name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('VIBE business profile, analytics, and performance.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('super-admin.vibe-programme.index') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Back') }}
                </a>
                <a href="{{ route('super-admin.vibe-programme.export-business', $business) }}" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                    {{ __('Export CSV') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-5">
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Business profile') }}</h3>
                    <dl class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">{{ __('Registration number') }}</dt><dd class="font-medium text-slate-900">{{ $business->registration_number ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('VIBE ID') }}</dt><dd class="font-medium text-slate-900">{{ $business->vibe_unique_id ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Owner') }}</dt><dd class="font-medium text-slate-900">{{ $business->user?->name ?: (trim(($business->owner_first_name ?? '').' '.($business->owner_last_name ?? '')) ?: '—') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Owner email') }}</dt><dd class="font-medium text-slate-900">{{ $business->user?->email ?: ($business->owner_email ?: '—') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Sector') }}</dt><dd class="font-medium text-slate-900">{{ ucfirst((string) $business->type) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Status') }}</dt><dd class="font-medium text-slate-900">{{ ucfirst((string) ($business->status ?? '—')) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('District') }}</dt><dd class="font-medium text-slate-900">{{ $business->districtDivision?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Sector location') }}</dt><dd class="font-medium text-slate-900">{{ $business->sectorDivision?->name ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Enrollment') }}</h3>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div><dt class="text-xs text-slate-500">{{ __('Pathway status') }}</dt><dd class="font-medium text-slate-900">{{ ucfirst((string) ($business->pathway_status ?? '—')) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Commencement date') }}</dt><dd class="font-medium text-slate-900">{{ optional($business->vibe_commencement_date)->format('M d, Y') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Facilities enrolled') }}</dt><dd class="font-medium text-slate-900">{{ $analytics['kpis']['facilities'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('Data completeness') }}</dt><dd class="font-medium text-slate-900">{{ number_format($completeness['percent'], 1) }}%</dd></div>
                    </dl>
                </div>
            </section>

            <section class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Facilities') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $analytics['kpis']['facilities'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Intakes') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $analytics['kpis']['animal_intake_records'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Certificates') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $analytics['kpis']['certificates_issued'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Deliveries') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $analytics['kpis']['confirmed_deliveries'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Fulfillment') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($analytics['kpis']['demand_fulfillment_rate'], 1) }}%</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Compliance') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($analytics['kpis']['compliance_score'], 1) }}%</p>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('KPI progress trend') }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Last 6 months across intake, certification, and delivery activity.') }}</p>
                    <div class="mt-3 h-72">
                        <canvas id="chart-vibe-kpi-progress"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Turnover comparison') }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Before (baseline) vs after (estimated from 12-month intake value).') }}</p>
                    <div class="mt-3 grid grid-cols-1 gap-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Before') }}</p>
                            <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($analytics['turnover']['before'], 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('After (estimated)') }}</p>
                            <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($analytics['turnover']['after'], 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ __('Growth') }}</p>
                            <p class="mt-1 text-xl font-bold {{ $analytics['turnover']['growth_pct'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ number_format($analytics['turnover']['growth_pct'], 1) }}%
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Turnover trend') }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Estimated monthly turnover from intake value over the last 6 months.') }}</p>
                    <div class="mt-3 h-64">
                        <canvas id="chart-vibe-turnover-progress"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Data completeness checks') }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $completeness['completed'] }}/{{ $completeness['total'] }} {{ __('fields completed') }}</p>
                    <ul class="mt-3 space-y-2 text-xs">
                        @foreach ($completeness['checks'] as $field => $isDone)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600">{{ str_replace('_', ' ', ucfirst((string) $field)) }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $isDone ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $isDone ? __('Complete') : __('Missing') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Key insights') }}</h3>
                <ul class="mt-2 space-y-2 text-sm text-slate-700">
                    @foreach ($analytics['insights'] as $insight)
                        <li class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-1.5 w-1.5 rounded-full bg-bucha-primary"></span>
                            <span>{{ $insight }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.dashboardCharts = @json($analytics['trend']['charts']);
        </script>
        @vite(['resources/js/dashboard-charts.js'])
    @endpush
</x-app-layout>
