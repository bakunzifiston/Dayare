<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
            {{ __('Your Dashboard') }}
        </h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            {{-- KPI cards --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Overview') }}</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <x-kpi-card title="{{ __('Businesses') }}" :value="$kpis['businesses']" :href="route('businesses.index')" color="blue" />
                        <x-kpi-card title="{{ __('Facilities') }}" :value="$kpis['facilities']" subtitle="{{ __('Across all businesses') }}" color="slate" />
                        <x-kpi-card title="{{ __('Inspectors') }}" :value="$kpis['inspectors']" :href="route('inspectors.index')" color="blue" />
                        <x-kpi-card title="{{ __('Slaughter plans') }}" :value="$kpis['slaughter_plans']" :subtitle="$kpis['slaughter_plans_approved'] . ' ' . __('approved')" :href="route('slaughter-plans.index')" color="green" />
                        <x-kpi-card title="{{ __('Batches') }}" :value="$kpis['batches']" :href="route('batches.index')" color="slate" />
                        <x-kpi-card title="{{ __('Certificates') }}" :value="$kpis['certificates']" :subtitle="$kpis['certificates_active'] . ' ' . __('active')" :href="route('certificates.index')" color="green" />
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <x-kpi-card title="{{ __('Slaughter executions') }}" :value="$kpis['slaughter_executions']" :subtitle="$kpis['executions_completed'] . ' ' . __('completed')" :href="route('slaughter-executions.index')" color="slate" />
                        <x-kpi-card title="{{ __('Transport trips') }}" :value="$kpis['transport_trips']" :href="route('transport-trips.index')" color="blue" />
                        <x-kpi-card title="{{ __('Delivery confirmations') }}" :value="$kpis['delivery_confirmations']" :href="route('delivery-confirmations.index')" color="green" />
                    </div>
                </div>
            </div>

            {{-- Charts & trends --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Charts & trends') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Last 6 months') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div class="lg:col-span-1">
                        <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                            <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Slaughter plans by month') }}</h3>
                            <div class="h-56">
                                <canvas id="chart-slaughter-plans" aria-label="{{ __('Slaughter plans by month') }}"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                            <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Certificates issued by month') }}</h3>
                            <div class="h-56">
                                <canvas id="chart-certificates" aria-label="{{ __('Certificates issued by month') }}"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="rounded-lg border border-slate-100 bg-slate-50/30 p-4">
                            <h3 class="text-sm font-medium text-slate-700 mb-3">{{ __('Batches & executions trend') }}</h3>
                            <div class="h-56">
                                <canvas id="chart-batches-executions" aria-label="{{ __('Batches and executions by month') }}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    window.dashboardCharts = @json($charts);
                </script>
            </div>

            {{-- Welcome --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="p-6 sm:p-8 text-slate-800">
                    <p class="text-lg font-medium text-slate-900 mb-1">
                        {{ __('Welcome, :name!', ['name' => $user->name]) }}
                    </p>
                    <p class="text-slate-600 mb-6">
                        {{ __("This is your personal dashboard. Only you can see this page and your data.") }}
                    </p>
                    <a href="{{ route('businesses.index') }}" class="inline-flex items-center px-4 py-2.5 bg-[#3B82F6] hover:bg-[#2563eb] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        {{ __('Manage Businesses & Facilities') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/dashboard-charts.js')
    @endpush
</x-app-layout>
