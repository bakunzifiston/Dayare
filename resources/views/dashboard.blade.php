<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Dashboard') }}</span>
    </x-slot>

    @php
        $roleLabels = [
            'org_admin' => __('Org Admin'),
            'operations_manager' => __('Operations Manager'),
            'compliance_officer' => __('Compliance Officer'),
            'inspector' => __('Inspector'),
            'transport_manager' => __('Transport Manager'),
        ];
    @endphp

    <div class="py-4 lg:py-6">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    {{ __('Welcome, :name', ['name' => $user->name]) }}
                </h1>
                <p class="mt-1 text-sm text-bucha-muted">
                    {{ __('Business: :business • Role: :role', ['business' => $activeBusiness?->business_name ?? __('No active business selected'), 'role' => $roleLabels[$role ?? ''] ?? __('Unassigned')]) }}
                </p>
            </div>

            @if (empty($metrics) && empty($alerts) && empty($quickActions))
                <div class="rounded-bucha bg-white border border-slate-200 p-6">
                    <p class="text-sm text-slate-700">
                        {{ __('No dashboard data is available for your current business context yet. Select an active business and ensure your role is assigned.') }}
                    </p>
                </div>
            @endif

            @if (! empty($metrics))
                <section>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Key Metrics') }}</h2>
                    <p class="text-xs text-bucha-muted mt-0.5">{{ __('5–7 role-specific KPIs scoped to this business.') }}</p>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach ($metrics as $metric)
                            <div class="rounded-bucha bg-white border border-slate-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-bucha-muted">{{ $metric['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $metric['value'] }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $metric['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! empty($alerts))
                <section>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Alerts') }}</h2>
                    <p class="text-xs text-bucha-muted mt-0.5">{{ __('Each alert links to a fixable workflow.') }}</p>
                    <div class="mt-3 grid grid-cols-1 lg:grid-cols-3 gap-3">
                        @foreach ($alerts as $alert)
                            <div class="rounded-bucha bg-white border border-rose-200 p-4">
                                <p class="text-sm font-semibold text-rose-700">{{ $alert['title'] }}</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $alert['count'] }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $alert['description'] }}</p>
                                <a href="{{ route($alert['route']) }}" class="mt-3 inline-flex items-center text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                    {{ __('Fix now') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! empty($quickActions))
                <section>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Quick Actions') }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($quickActions as $action)
                            <a href="{{ $action['url'] }}" class="inline-flex items-center px-4 py-2 rounded-bucha bg-bucha-primary hover:bg-bucha-burgundy text-white text-xs font-semibold">
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
