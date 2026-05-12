@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.feeding.hub', 'active' => request()->routeIs('farmer.feeding.hub')],
        ['label' => __('Feed types'), 'route' => 'farmer.feeding.feed-types.index', 'active' => request()->routeIs('farmer.feeding.feed-types.*')],
        ['label' => __('Inventory'), 'route' => 'farmer.feeding.inventory.index', 'active' => request()->routeIs('farmer.feeding.inventory.*')],
        ['label' => __('Feeding records'), 'route' => 'farmer.feeding.records.index', 'active' => request()->routeIs('farmer.feeding.records.*')],
        ['label' => __('Suppliers'), 'route' => 'farmer.feeding.suppliers.index', 'active' => request()->routeIs('farmer.feeding.suppliers.*')],
        ['label' => __('Schedules'), 'route' => 'farmer.feeding.schedules.index', 'active' => request()->routeIs('farmer.feeding.schedules.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-bucha-primary text-white' => $item['active'], 'text-slate-600 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
    @endforeach
</nav>
