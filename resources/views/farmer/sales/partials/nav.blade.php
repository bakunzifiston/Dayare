@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.sales.hub', 'active' => request()->routeIs('farmer.sales.hub')],
        ['label' => __('Sales'), 'route' => 'farmer.sales.records.index', 'active' => request()->routeIs('farmer.sales.records.*')],
        ['label' => __('Sale animals'), 'route' => 'farmer.sales.animals.index', 'active' => request()->routeIs('farmer.sales.animals.*')],
        ['label' => __('Buyers'), 'route' => 'farmer.sales.buyers.index', 'active' => request()->routeIs('farmer.sales.buyers.*')],
        ['label' => __('Payments'), 'route' => 'farmer.sales.payments.index', 'active' => request()->routeIs('farmer.sales.payments.*')],
        ['label' => __('Documents'), 'route' => 'farmer.sales.documents.index', 'active' => request()->routeIs('farmer.sales.documents.*')],
        ['label' => __('History & logs'), 'route' => 'farmer.sales.logs.index', 'active' => request()->routeIs('farmer.sales.logs.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-bucha-primary text-white' => $item['active'], 'text-slate-600 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
    @endforeach
</nav>
