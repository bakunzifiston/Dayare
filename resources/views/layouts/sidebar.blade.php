@php
    $nav = [
        ['label' => __('Dashboard'), 'route' => 'dashboard', 'icon' => 'dashboard'],
        ['label' => __('Businesses'), 'route' => 'businesses.index', 'icon' => 'building'],
        ['label' => __('Inspectors'), 'route' => 'inspectors.index', 'icon' => 'user'],
        ['label' => __('Animal intake'), 'route' => 'animal-intakes.index', 'icon' => 'intake'],
        ['label' => __('Slaughter planning'), 'route' => 'slaughter-plans.index', 'icon' => 'calendar'],
        ['label' => __('Ante-mortem'), 'route' => 'ante-mortem-inspections.index', 'icon' => 'clipboard-list'],
        ['label' => __('Slaughter execution'), 'route' => 'slaughter-executions.index', 'icon' => 'play'],
        ['label' => __('Batches'), 'route' => 'batches.index', 'icon' => 'box'],
        ['label' => __('Post-mortem'), 'route' => 'post-mortem-inspections.index', 'icon' => 'clipboard'],
        ['label' => __('Certificates'), 'route' => 'certificates.index', 'icon' => 'certificate'],
        ['label' => __('Warehouse'), 'route' => 'warehouse-storages.index', 'icon' => 'box'],
        ['label' => __('Transport'), 'route' => 'transport-trips.index', 'icon' => 'truck'],
        ['label' => __('Delivery confirmation'), 'route' => 'delivery-confirmations.index', 'icon' => 'check'],
        ['label' => __('Compliance'), 'route' => 'compliance.index', 'icon' => 'shield'],
        // New CRM / HR modules
        ['label' => __('CRM'), 'route' => 'crm.dashboard', 'icon' => 'dashboard'],
        ['label' => __('Employees'), 'route' => 'employees.index', 'icon' => 'user'],
        ['label' => __('Suppliers'), 'route' => 'suppliers.index', 'icon' => 'building'],
        ['label' => __('Contracts'), 'route' => 'contracts.index', 'icon' => 'clipboard'],
        ['label' => __('Clients'), 'route' => 'clients.index', 'icon' => 'user'],
        ['label' => __('Demand'), 'route' => 'demands.index', 'icon' => 'clipboard-list'],
        ['label' => __('Settings'), 'route' => 'settings.edit', 'icon' => 'settings'],
    ];
@endphp
<aside
    id="sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-5 pb-4 flex flex-col bg-[#1E3A8A] border-r border-black/10 transition-transform duration-200 ease-out -translate-x-full lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
    aria-label="Sidebar"
>
    <div class="flex items-center justify-between px-5 mb-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <x-application-logo class="h-8 w-auto fill-white" />
            <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
        </a>
        <button @click="sidebarOpen = false" type="button" class="lg:hidden p-2 rounded-lg text-white/80 hover:text-white hover:bg-[#1D4ED8]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 space-y-0.5">
        @foreach ($nav as $item)
            @php $routePattern = str_replace('.index', '.*', $item['route']); $active = request()->routeIs($item['route']) || request()->routeIs($routePattern); @endphp
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-[#3B82F6] text-white border-l-2 border-[#DBEAFE]' : 'text-white/90 hover:bg-[#1D4ED8] hover:text-white' }}"
            >
                @if (($item['icon'] ?? '') === 'dashboard')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                @elseif (($item['icon'] ?? '') === 'building')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                @elseif (($item['icon'] ?? '') === 'user')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                @elseif (($item['icon'] ?? '') === 'calendar')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                @elseif (($item['icon'] ?? '') === 'play')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif (($item['icon'] ?? '') === 'box')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                @elseif (($item['icon'] ?? '') === 'clipboard' || ($item['icon'] ?? '') === 'clipboard-list')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                @elseif (($item['icon'] ?? '') === 'certificate')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 00-4.438 0 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 000-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                @elseif (($item['icon'] ?? '') === 'truck')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1h1m-1-1V6a1 1 0 011-1h2a1 1 0 011 1v10m-1 1h1"/></svg>
                @elseif (($item['icon'] ?? '') === 'check')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif (($item['icon'] ?? '') === 'shield')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                @elseif (($item['icon'] ?? '') === 'intake')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                @elseif (($item['icon'] ?? '') === 'settings')
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37A1.724 1.724 0 0010.325 4.317z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                @else
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                @endif
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="mt-auto px-3 pt-3 border-t border-white/10">
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-white">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#DBEAFE] text-sm font-medium text-[#1E3A8A]">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-white/70 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <div class="mt-2 flex gap-1">
            <a href="{{ route('profile.edit') }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-white/80 hover:bg-[#1D4ED8] hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('Profile') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-white/80 hover:bg-[#1D4ED8] hover:text-red-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</aside>
