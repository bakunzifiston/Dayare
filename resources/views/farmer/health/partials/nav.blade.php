@php
    $items = [
        ['label' => __('Overview'), 'route' => 'farmer.health.hub', 'active' => request()->routeIs('farmer.health.hub')],
        ['label' => __('Vaccinations'), 'route' => 'farmer.health.vaccinations.index', 'active' => request()->routeIs('farmer.health.vaccinations.*')],
        ['label' => __('Treatments'), 'route' => 'farmer.health.treatments.index', 'active' => request()->routeIs('farmer.health.treatments.*')],
        ['label' => __('Diseases'), 'route' => 'farmer.health.diseases.index', 'active' => request()->routeIs('farmer.health.diseases.*')],
        ['label' => __('Vet visits'), 'route' => 'farmer.health.vet-visits.index', 'active' => request()->routeIs('farmer.health.vet-visits.*')],
        ['label' => __('Mortality'), 'route' => 'farmer.health.mortalities.index', 'active' => request()->routeIs('farmer.health.mortalities.*')],
        ['label' => __('Timeline'), 'route' => 'farmer.health.timeline.index', 'active' => request()->routeIs('farmer.health.timeline.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2 rounded-bucha border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            @class([
                'rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-bucha-primary text-white' => $item['active'],
                'text-slate-600 hover:bg-slate-50' => ! $item['active'],
            ])
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
