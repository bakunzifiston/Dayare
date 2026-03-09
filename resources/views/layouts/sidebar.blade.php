@php
    $isSuperAdmin = Auth::user()?->isSuperAdmin();

    // Super Admin sees only platform-level items (no tenant Operations / CRM modules)
    $navGroups = $isSuperAdmin
        ? [
            ['label' => __('Platform dashboard'), 'route' => 'super-admin.dashboard', 'icon' => 'shield'],
            ['label' => __('Settings'), 'route' => 'settings.edit', 'icon' => 'settings'],
        ]
        : [
            ['label' => __('Dashboard'), 'route' => 'dashboard', 'icon' => 'dashboard'],
            [
                'group' => __('Operations'),
                'icon' => 'box',
                'children' => [
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
                ],
            ],
            [
                'group' => __('CRM & HR'),
                'icon' => 'user',
                'children' => [
                    ['label' => __('CRM'), 'route' => 'crm.dashboard', 'icon' => 'dashboard'],
                    ['label' => __('Employees'), 'route' => 'employees.index', 'icon' => 'user'],
                    ['label' => __('Suppliers'), 'route' => 'suppliers.index', 'icon' => 'building'],
                    ['label' => __('Contracts'), 'route' => 'contracts.index', 'icon' => 'clipboard'],
                    ['label' => __('Clients'), 'route' => 'clients.index', 'icon' => 'user'],
                    ['label' => __('Demand'), 'route' => 'demands.index', 'icon' => 'clipboard-list'],
                ],
            ],
            ['label' => __('Settings'), 'route' => 'settings.edit', 'icon' => 'settings'],
        ];
@endphp
<aside
    id="sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-5 pb-4 flex flex-col bg-[#1E3A8A] text-white border-r border-black/10 transition-transform duration-200 ease-out -translate-x-full lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
    aria-label="Sidebar"
>
    <div class="flex items-center justify-between px-5 mb-6">
        <a href="{{ $isSuperAdmin ? route('super-admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2.5">
            <x-application-logo class="h-8 w-auto fill-white" />
            <span class="text-lg font-semibold text-white">{{ config('app.name') }}{{ $isSuperAdmin ? ' · ' . __('Admin') : '' }}</span>
        </a>
        <button @click="sidebarOpen = false" type="button" class="lg:hidden p-2 rounded-lg text-white/80 hover:text-white hover:bg-[#1D4ED8]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 space-y-0.5">
        @foreach ($navGroups as $item)
            @if (isset($item['group']))
                @php
                    $childRoutes = array_column($item['children'], 'route');
                    $groupActive = false;
                    foreach ($childRoutes as $r) {
                        $pattern = str_replace('.index', '.*', $r);
                        if (request()->routeIs($r) || request()->routeIs($pattern)) { $groupActive = true; break; }
                    }
                @endphp
                <div class="mb-1" x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-white/90 hover:bg-[#1D4ED8] hover:text-white transition-colors">
                        @include('layouts.partials.sidebar-icon', ['icon' => $item['icon'] ?? ''])
                        <span class="flex-1 text-left">{{ $item['group'] }}</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition class="ml-2 mt-0.5 space-y-0.5 border-l border-white/20 pl-2">
                        @foreach ($item['children'] as $child)
                            @php $routePattern = str_replace('.index', '.*', $child['route']); $active = request()->routeIs($child['route']) || request()->routeIs($routePattern); @endphp
                            <a href="{{ route($child['route']) }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-[#3B82F6] text-white' : 'text-white/85 hover:bg-[#1D4ED8] hover:text-white' }}">
                                @include('layouts.partials.sidebar-icon', ['icon' => $child['icon'] ?? ''])
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php $routePattern = str_replace('.index', '.*', $item['route']); $active = request()->routeIs($item['route']) || request()->routeIs($routePattern); @endphp
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-[#3B82F6] text-white border-l-2 border-[#DBEAFE]' : 'text-white/90 hover:bg-[#1D4ED8] hover:text-white' }}">
                    @include('layouts.partials.sidebar-icon', ['icon' => $item['icon'] ?? ''])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
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
