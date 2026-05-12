<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Certificates & traceability') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.animal-certificates.partials.nav')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-kpi-card stat :title="__('Total certificates')" :value="$metrics['total']" color="blue" />
            <x-kpi-card stat :title="__('Active certificates')" :value="$metrics['active']" color="bucha-success" :href="route('farmer.certificates.animal-certificates.index')" />
            <x-kpi-card stat :title="__('Expired certificates')" :value="$metrics['expired']" color="amber" />
            <x-kpi-card stat :title="__('Verification count')" :value="$metrics['verifications']" color="slate" />
            <x-kpi-card stat :title="__('Ownership transfers')" :value="$metrics['transfers']" color="bucha" :href="route('farmer.certificates.ownership-transfers.index')" />
        </div>
        <section class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent verifications') }}</h3>
            @forelse ($metrics['recentVerifications'] as $log)
                <div class="mt-3 flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-2 text-sm">
                    <span>{{ $log->certificate?->certificate_number }} · {{ $log->certificate?->animal?->animal_code }}</span>
                    <span class="text-slate-500">{{ $log->action_date?->toDateTimeString() }}</span>
                </div>
            @empty
                <p class="mt-2 text-sm text-slate-500">{{ __('No public verifications logged yet.') }}</p>
            @endforelse
        </section>
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['certificate_trend' => __('Certificate trends'), 'verification_trend' => __('Verification trends'), 'transfer_trend' => __('Ownership transfers'), 'expiry_tracking' => __('Expiry tracking')] as $chartId => $title)
                <section class="rounded-bucha border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                    <div class="mt-4 h-56"><canvas id="chart-{{ str_replace('_', '-', $chartId) }}" aria-label="{{ $title }}"></canvas></div>
                </section>
            @endforeach
        </div>
    </div>
    @vite('resources/js/dashboard-charts.js')
    <script>window.dashboardCharts = @json($charts);</script>
</x-app-layout>
