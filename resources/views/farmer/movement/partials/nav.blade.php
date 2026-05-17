@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.movement.hub', 'active' => request()->routeIs('farmer.movement.hub')],
        ['label' => __('Permit requests'), 'route' => 'farmer.movement.requests.index', 'active' => request()->routeIs('farmer.movement.requests.*')],
        ['label' => __('Permits'), 'route' => 'farmer.movement.permits.index', 'active' => request()->routeIs('farmer.movement.permits.*')],
        ['label' => __('Movement history'), 'route' => 'farmer.movement.history.index', 'active' => request()->routeIs('farmer.movement.history.*')],
        ['label' => __('Public verification'), 'route' => 'farmer.movement.verification', 'active' => request()->routeIs('verify.permit.*') || request()->routeIs('farmer.movement.verification')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-bucha-primary text-white' => $item['active'], 'text-slate-600 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
    @endforeach
</nav>
