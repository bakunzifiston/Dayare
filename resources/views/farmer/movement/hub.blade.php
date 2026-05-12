<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement permits') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-kpi-card stat :title="__('Total permits')" :value="$metrics['total_permits']" color="blue" :href="route('farmer.movement.permits.index')" />
            <x-kpi-card stat :title="__('Active permits')" :value="$metrics['active_permits']" color="slate" />
            <x-kpi-card stat :title="__('Approved permits')" :value="$metrics['approved_permits']" color="bucha-success" />
            <x-kpi-card stat :title="__('Animals in transit')" :value="$metrics['animals_in_transit']" color="sky" :href="route('farmer.movement.animals.index')" />
            <x-kpi-card stat :title="__('Rejected permits')" :value="$metrics['rejected_permits']" color="amber" />
            <x-kpi-card stat :title="__('Expired permits')" :value="$metrics['expired_permits']" color="bucha" />
        </div>
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['movement_trend' => __('Movement trends'), 'approval_trend' => __('Permit approvals'), 'destination_analytics' => __('Permit types'), 'veterinary_analytics' => __('Veterinary clearance')] as $chartId => $title)
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
