<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Dashboard') }}</span>
    </x-slot>

    @php
        $roleLabels = [
            'org_admin' => __('Org admin'),
            'operations_manager' => __('Operations manager'),
            'compliance_officer' => __('Compliance officer'),
            'inspector' => __('Inspector'),
            'transport_manager' => __('Transport manager'),
            'accountant' => __('Accountant'),
        ];
        $roleTitles = [
            'org_admin' => __('Executive overview'),
            'operations_manager' => __('Operations command center'),
            'compliance_officer' => __('Compliance command center'),
            'inspector' => __('Inspection workspace'),
            'transport_manager' => __('Logistics command center'),
            'accountant' => __('Finance command center'),
        ];
        $ops = $operationsDashboard ?? null;
        $roleKey = $ops['roleKey'] ?? ($role ?? '');
        $allDashboards = $allRoleDashboards ?? null;
        $showTabs = is_array($allDashboards) && count($allDashboards) > 1;
    @endphp

    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.5.0/dist/tabler-icons.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
        @vite('resources/js/processor-dashboard.js')
        <script>
            window.processorDashboardCharts = @json(
                collect($showTabs ? $allDashboards : [($ops['roleKey'] ?? $roleKey) => $ops])
                    ->filter()
                    ->mapWithKeys(fn ($dashboard, $key) => [$key => $dashboard['charts'] ?? []])
            );
        </script>
    @endpush

    <div class="proc-dash py-2">
        <h2 class="sr-only">{{ $roleTitles[$roleKey] ?? __('Processor dashboard') }}</h2>

        @if ($ops === null)
            <div class="proc-dash__card">
                <p style="margin:0;font-size:12px;color:var(--color-text-secondary);">
                    {{ __('No dashboard data is available for your current business context yet. Select an active business and ensure your role is assigned.') }}
                </p>
            </div>
        @elseif ($showTabs)
            <nav class="proc-dash__tabs" aria-label="{{ __('Role views') }}">
                @foreach ($allDashboards as $tabRole => $tabDashboard)
                    <button
                        type="button"
                        class="proc-dash__tab {{ $tabRole === $roleKey ? 'active' : '' }}"
                        data-role="{{ $tabRole }}"
                        onclick="window.procDashSw('{{ $tabRole }}')"
                    >
                        {{ $roleLabels[$tabRole] ?? $tabRole }}
                    </button>
                @endforeach
            </nav>

            @foreach ($allDashboards as $tabRole => $tabDashboard)
                <div
                    class="proc-dash__role-panel"
                    data-role="{{ $tabRole }}"
                    @if ($tabRole !== $roleKey) hidden @endif
                >
                    @include('processor.dashboard.panel', [
                        'ops' => $tabDashboard,
                        'panelTitle' => $roleTitles[$tabRole] ?? '',
                        'panelMeta' => __('Business: :business', ['business' => $activeBusiness?->business_name ?? __('No active business')]),
                    ])
                </div>
            @endforeach
        @else
            @include('processor.dashboard.panel', [
                'ops' => $ops,
                'panelTitle' => $roleTitles[$roleKey] ?? __('Processor dashboard'),
                'panelMeta' => __('Business: :business · Role: :role', [
                    'business' => $activeBusiness?->business_name ?? __('No active business selected'),
                    'role' => $roleLabels[$role ?? ''] ?? __('Unassigned'),
                ]),
            ])
        @endif
    </div>
</x-app-layout>
