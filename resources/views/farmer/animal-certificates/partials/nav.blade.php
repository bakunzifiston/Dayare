@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.certificates.hub', 'active' => request()->routeIs('farmer.certificates.hub')],
        ['label' => __('Animal certificates'), 'route' => 'farmer.certificates.animal-certificates.index', 'active' => request()->routeIs('farmer.certificates.animal-certificates.*')],
        ['label' => __('Ownership transfers'), 'route' => 'farmer.certificates.ownership-transfers.index', 'active' => request()->routeIs('farmer.certificates.ownership-transfers.*')],
        ['label' => __('History & logs'), 'route' => 'farmer.certificates.logs.index', 'active' => request()->routeIs('farmer.certificates.logs.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-bucha-primary text-white' => $item['active'], 'text-slate-600 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
    @endforeach
</nav>
