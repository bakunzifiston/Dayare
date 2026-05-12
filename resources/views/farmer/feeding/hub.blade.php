<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Feeding & nutrition') }}</h2></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.feeding.partials.nav')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-kpi-card stat :title="__('Total feed stock')" :value="$metrics['total_stock']" color="blue" />
            <x-kpi-card stat :title="__('Low stock alerts')" :value="$metrics['low_stock_alerts']" color="amber" :href="route('farmer.feeding.inventory.index')" />
            <x-kpi-card stat :title="__('Daily feed usage')" :value="$metrics['daily_usage']" color="slate" />
            <x-kpi-card stat :title="__('Feed cost today')" :value="$metrics['feed_cost_today']" color="bucha" />
            <x-kpi-card stat :title="__('Most used feed')" :value="$metrics['most_used_feed'] ?: '—'" color="bucha-success" />
            <x-kpi-card stat :title="__('Feed wastage (30d)')" :value="$metrics['feed_wastage']" color="bucha" />
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-bucha border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-sm font-semibold text-amber-900">{{ __('Low stock alerts') }}</h3>
                @forelse ($lowStock as $batch)
                    <div class="mt-3 rounded-lg border border-amber-100 bg-white px-3 py-2 text-sm">
                        <p class="font-medium text-slate-900">{{ $batch->feedType?->feed_name }}</p>
                        <p class="text-slate-600">{{ $batch->inventory_code }} · {{ number_format((float) $batch->quantity_remaining, 2) }} {{ $batch->feedType?->unit }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-amber-800">{{ __('No low stock batches.') }}</p>
                @endforelse
            </section>
            <section class="rounded-bucha border border-red-200 bg-red-50 p-4">
                <h3 class="text-sm font-semibold text-red-900">{{ __('Expiry alerts') }}</h3>
                @forelse ($expiring as $batch)
                    <div class="mt-3 rounded-lg border border-red-100 bg-white px-3 py-2 text-sm">
                        <p class="font-medium text-slate-900">{{ $batch->feedType?->feed_name }}</p>
                        <p class="text-slate-600">{{ $batch->expiry_date?->toDateString() }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-red-800">{{ __('No batches expiring in the next 14 days.') }}</p>
                @endforelse
            </section>
        </div>
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['feed_usage_trend' => __('Feed usage trends'), 'inventory_trend' => __('Inventory trends'), 'feed_cost_trend' => __('Feed cost analysis'), 'consumption_by_livestock' => __('Consumption by livestock')] as $chartId => $title)
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
