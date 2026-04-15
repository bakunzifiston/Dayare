@php
    $companyQuery = !empty($selectedCompanyId) ? ['company_id' => $selectedCompanyId] : [];
    $items = [
        ['label' => __('Dashboard'), 'route' => 'logistics.dashboard.index', 'icon' => 'dashboard', 'routeIs' => 'logistics.dashboard.*'],
        ['label' => __('Company'), 'route' => 'logistics.company.index', 'icon' => 'building', 'routeIs' => 'logistics.company.*'],
        ['label' => __('Assets'), 'route' => 'logistics.assets.index', 'icon' => 'truck', 'routeIs' => 'logistics.assets.*'],
        ['label' => __('Orders'), 'route' => 'logistics.orders.index', 'icon' => 'clipboard-list', 'routeIs' => 'logistics.orders.*'],
        ['label' => __('Trip Planning'), 'route' => 'logistics.planning.index', 'icon' => 'calendar', 'routeIs' => 'logistics.planning.*'],
        ['label' => __('Active Trips'), 'route' => 'logistics.trips.index', 'icon' => 'play', 'routeIs' => 'logistics.trips.*'],
        ['label' => __('Tracking'), 'route' => 'logistics.tracking.index', 'icon' => 'clipboard', 'routeIs' => 'logistics.tracking.*'],
        ['label' => __('Compliance'), 'route' => 'logistics.compliance.index', 'icon' => 'shield', 'routeIs' => 'logistics.compliance.*'],
        ['label' => __('Billing'), 'route' => 'logistics.billing.index', 'icon' => 'box', 'routeIs' => 'logistics.billing.*'],
    ];
@endphp

<aside class="w-72 shrink-0 rounded-bucha border border-slate-200 bg-white p-3 shadow-sm">
    <div class="mb-4 px-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Logistics Workspace') }}</p>
        <p class="mt-1 text-sm text-slate-600">{{ __('Unified operations and dispatch control.') }}</p>
    </div>
    <nav class="space-y-1">
        @foreach ($items as $item)
            @php($isActive = request()->routeIs($item['routeIs']))
            <a
                data-logistics-nav
                href="{{ route($item['route'], $companyQuery) }}"
                class="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold' : 'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50' }}"
            >
                <span class="{{ $isActive ? 'text-indigo-600' : 'text-slate-500' }}">
                    @include('layouts.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
