@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.movement.hub', 'active' => request()->routeIs('farmer.movement.hub')],
        ['label' => __('Permits'), 'route' => 'farmer.movement.permits.index', 'active' => request()->routeIs('farmer.movement.permits.*')],
        ['label' => __('Movement animals'), 'route' => 'farmer.movement.animals.index', 'active' => request()->routeIs('farmer.movement.animals.*')],
        ['label' => __('History & logs'), 'route' => 'farmer.movement.logs.index', 'active' => request()->routeIs('farmer.movement.logs.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-bucha-primary text-white' => $item['active'], 'text-slate-600 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
    @endforeach
</nav>
