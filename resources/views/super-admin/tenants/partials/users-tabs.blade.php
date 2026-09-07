@php
    $environmentQuery = array_filter([
        'tenant_environment' => $tenantEnvironmentFilter ?? request('tenant_environment'),
    ], fn ($value) => filled($value));

    $overviewQuery = array_filter([
        ...$environmentQuery,
        'workspace' => $workspaceType ?? request('workspace'),
    ], fn ($value) => filled($value));

    $directoryQuery = array_filter([
        ...$environmentQuery,
        'workspace' => $workspaceType ?? request('workspace'),
    ], fn ($value) => filled($value));

    $tabs = [
        ['label' => __('Overview'), 'route' => 'super-admin.tenants.overview', 'query' => $overviewQuery],
        ['label' => __('Directory'), 'route' => 'super-admin.tenants.index', 'query' => $directoryQuery],
    ];
@endphp

<nav class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 w-fit" aria-label="{{ __('Users sections') }}">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route'])
            || ($tab['route'] === 'super-admin.tenants.index' && request()->routeIs('super-admin.tenants.show')); @endphp
        <a href="{{ route($tab['route'], $tab['query']) }}"
           @class([
               'inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold',
               'bg-white text-slate-900 shadow-sm' => $active,
               'text-slate-600 hover:text-slate-900' => ! $active,
           ])>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
