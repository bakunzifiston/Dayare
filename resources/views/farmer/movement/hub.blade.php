<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement management') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-kpi-card stat :title="__('Requests today')" :value="$metrics['requests_submitted_today']" color="blue" :href="route('farmer.movement.requests.index')" />
            <x-kpi-card stat :title="__('Pending reviews')" :value="$metrics['pending_reviews']" color="amber" :href="route('farmer.movement.requests.index', ['status' => 'submitted'])" />
            <x-kpi-card stat :title="__('Approved requests')" :value="$metrics['approved_requests']" color="slate" />
            <x-kpi-card stat :title="__('Active permits')" :value="$metrics['active_permits']" color="bucha-success" :href="route('farmer.movement.permits.index')" />
            <x-kpi-card stat :title="__('Expiring soon')" :value="$metrics['permits_expiring_soon']" color="amber" />
            <x-kpi-card stat :title="__('Completed movements')" :value="$metrics['completed_movements']" color="sky" :href="route('farmer.movement.history.index')" />
            <x-kpi-card stat :title="__('Animals moved (month)')" :value="$metrics['animals_moved_this_month']" color="blue" />
            <x-kpi-card stat :title="__('Verifications today')" :value="$metrics['verification_searches_today']" color="slate" :href="route('verify.permit.lookup')" />
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
