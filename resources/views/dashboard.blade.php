<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Dashboard') }}</span>
    </x-slot>

    @php
        $ops = $operationsDashboard ?? null;
        $roleKey = $ops['roleKey'] ?? ($role ?? '');
    @endphp

    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.5.0/dist/tabler-icons.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
        <script>
            window.buchaChartColors = @json(config('bucha.chart'));
            window.processorDashboardActiveRole = @json($roleKey ?: null);
            window.processorDashboardCharts = @json(
                collect([$roleKey => $ops ?? []])
                    ->filter(fn ($dashboard, $key) => filled($key) && is_array($dashboard))
                    ->mapWithKeys(fn ($dashboard, $key) => [$key => $dashboard['charts'] ?? []])
            );
        </script>
        @vite('resources/js/processor-dashboard.js')
    @endpush

    <div class="proc-dash py-2">
        <h2 class="sr-only">{{ __('Processor dashboard') }}</h2>

        @if ($ops === null)
            <div class="proc-dash__card">
                <p style="margin:0;font-size:12px;color:var(--color-text-secondary);">
                    {{ __('No dashboard data is available for your current business context yet. Select an active business and ensure your role is assigned.') }}
                </p>
            </div>
        @else
            @include('processor.dashboard.panel', [
                'ops' => $ops,
                'panelTitle' => __('Dashboard'),
                'panelMeta' => __('Business: :business', [
                    'business' => $activeBusiness?->business_name ?? __('No active business selected'),
                ]),
            ])
        @endif
    </div>
</x-app-layout>
