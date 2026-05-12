<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Livestock sales') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.sales.partials.nav')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-kpi-card stat :title="__('Total sales')" :value="$metrics['total_sales']" color="blue" :href="route('farmer.sales.records.index')" />
            <x-kpi-card stat :title="__('Revenue')" :value="number_format($metrics['revenue'], 2)" color="bucha" />
            <x-kpi-card stat :title="__('Animals sold')" :value="$metrics['animals_sold']" color="bucha-success" :href="route('farmer.sales.animals.index')" />
            <x-kpi-card stat :title="__('Pending payments')" :value="$metrics['pending_payments']" color="amber" :href="route('farmer.sales.payments.index')" />
            <x-kpi-card stat :title="__('Completed sales')" :value="$metrics['completed_sales']" color="slate" />
            <x-kpi-card stat :title="__('Top buyer')" :value="$metrics['top_buyer'] ?: '—'" color="blue" :href="route('farmer.sales.buyers.index')" />
        </div>
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['revenue_trend' => __('Revenue trends'), 'sales_by_type' => __('Sales by type'), 'payment_methods' => __('Payment analytics'), 'buyer_analytics' => __('Buyer analytics')] as $chartId => $title)
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
